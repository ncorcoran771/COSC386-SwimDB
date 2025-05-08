<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Get meet info from URL parameters
$meetName = isset($_GET['name']) ? sanitize($_GET['name']) : '';
$meetDate = isset($_GET['date']) ? sanitize($_GET['date']) : '';

// Redirect if no valid meet info provided
if (empty($meetName) || empty($meetDate)) {
    redirect('view.php?entity=meets', 'No meet specified');
}

$user = getCurrentUser();

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<body>
    <div class="main">
        <div class="container">
            <?php
            // Show message if any
            if (isset($_SESSION['message'])) {
                echo showMessage($_SESSION['message']);
                unset($_SESSION['message']);
            }

            // Get meet details
            $stmt = $conn->prepare("SELECT * FROM Meet WHERE meetName = ? AND date = ?");
            $stmt->bind_param('ss', $meetName, $meetDate);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                // Meet found, display profile
                $location = htmlspecialchars($row['location']);
                $date = htmlspecialchars($row['date']);

                // Get all swim records for this meet
                $swimStmt = $conn->prepare("SELECT s.*, sw.name AS swimmerName, sw.team AS swimmerTeam, 
                                         sw.gender AS swimmerGender, sw.powerIndex 
                                      FROM Swim s
                                      JOIN Swimmer sw ON s.swimmerID = sw.swimmerID
                                      WHERE s.meetName = ? AND s.meetDate = ?
                                      ORDER BY s.eventName, s.time ASC");
                $swimStmt->bind_param('ss', $meetName, $meetDate);
                $swimStmt->execute();
                $swimResult = $swimStmt->get_result();
                $totalSwims = $swimResult->num_rows;

                // Get participating teams
                $teamStmt = $conn->prepare("SELECT DISTINCT sw.team, t.location, t.confName, t.confState, 
                                         COUNT(s.swimmerID) as swimmerCount
                                      FROM Swim s
                                      JOIN Swimmer sw ON s.swimmerID = sw.swimmerID
                                      LEFT JOIN Team t ON sw.team = t.teamName
                                      WHERE s.meetName = ? AND s.meetDate = ?
                                      GROUP BY sw.team
                                      ORDER BY swimmerCount DESC");
                $teamStmt->bind_param('ss', $meetName, $meetDate);
                $teamStmt->execute();
                $teamsResult = $teamStmt->get_result();
                $teamCount = $teamsResult->num_rows;
                
                // Get top events with most participation
                $topEventsStmt = $conn->prepare("SELECT eventName, COUNT(*) as count
                                             FROM Swim
                                             WHERE meetName = ? AND meetDate = ?
                                             GROUP BY eventName
                                             ORDER BY count DESC
                                             LIMIT 5");
                $topEventsStmt->bind_param('ss', $meetName, $meetDate);
                $topEventsStmt->execute();
                $topEventsResult = $topEventsStmt->get_result();
                $topEvents = [];
                while ($eventRow = $topEventsResult->fetch_assoc()) {
                    $topEvents[] = $eventRow['eventName'];
                }

                // Process swim records and prepare data for charts
                $eventData = [];
                $teamEventData = [];
                $genderDistribution = ['M' => 0, 'F' => 0];
                $swimmers = [];
                
                // Team performance comparison data
                $teamPerformance = [];
                
                // Time distribution data for most popular event
                $mostPopularEvent = !empty($topEvents) ? $topEvents[0] : null;
                $timeDistribution = [];

                if ($totalSwims > 0) {
                    $swimResult->data_seek(0); // Reset result pointer
                    while ($swimRow = $swimResult->fetch_assoc()) {
                        // Count events for chart
                        $event = $swimRow['eventName'];
                        if (!isset($eventData[$event])) {
                            $eventData[$event] = 0;
                        }
                        $eventData[$event]++;

                        // Track team events
                        $team = $swimRow['swimmerTeam'];
                        if (!isset($teamEventData[$team])) {
                            $teamEventData[$team] = [];
                        }
                        if (!isset($teamEventData[$team][$event])) {
                            $teamEventData[$team][$event] = 0;
                        }
                        $teamEventData[$team][$event]++;

                        // Gender distribution
                        $gender = $swimRow['swimmerGender'];
                        if (isset($genderDistribution[$gender])) {
                            $genderDistribution[$gender]++;
                        }

                        // Team performance tracking
                        if (!isset($teamPerformance[$team])) {
                            $teamPerformance[$team] = [];
                        }
                        if (!isset($teamPerformance[$team][$event])) {
                            $teamPerformance[$team][$event] = ['times' => [], 'avg' => 0, 'best' => 9999999];
                        }
                        $teamPerformance[$team][$event]['times'][] = $swimRow['time'];
                        $teamPerformance[$team][$event]['best'] = min($teamPerformance[$team][$event]['best'], $swimRow['time']);
                        
                        // Time distribution for most popular event
                        if ($event === $mostPopularEvent) {
                            $timeDistribution[] = $swimRow['time'];
                        }

                        // Track unique swimmers
                        $swimmerID = $swimRow['swimmerID'];
                        if (!isset($swimmers[$swimmerID])) {
                            $swimmers[$swimmerID] = [
                                'id' => $swimmerID,
                                'name' => $swimRow['swimmerName'],
                                'team' => $swimRow['swimmerTeam'],
                                'gender' => $swimRow['swimmerGender'],
                                'powerIndex' => $swimRow['powerIndex'],
                                'events' => []
                            ];
                        }
                        // Add event to swimmer's list
                        $swimmers[$swimmerID]['events'][] = [
                            'event' => $event,
                            'time' => $swimRow['time']
                        ];
                    }
                    
                    // Calculate average times for team performance
                    foreach ($teamPerformance as $team => $events) {
                        foreach ($events as $event => $data) {
                            if (!empty($data['times'])) {
                                $teamPerformance[$team][$event]['avg'] = array_sum($data['times']) / count($data['times']);
                            }
                        }
                    }
                }

                // Display meet header info
                ?>
                <div class="profile-header">
                    <h1><?= htmlspecialchars($meetName) ?></h1>
                    <div class="profile-stats">
                        <div class="stat-box">
                            <span class="stat-label">Date</span>
                            <span class="stat-value"><?= $date ?></span>
                        </div>
                        <div class="stat-box">
                            <span class="stat-label">Location</span>
                            <span class="stat-value"><?= $location ?></span>
                        </div>
                        <div class="stat-box">
                            <span class="stat-label">Teams</span>
                            <span class="stat-value"><?= $teamCount ?></span>
                        </div>
                        <div class="stat-box">
                            <span class="stat-label">Swimmers</span>
                            <span class="stat-value"><?= count($swimmers) ?></span>
                        </div>
                        <div class="stat-box">
                            <span class="stat-label">Total Swims</span>
                            <span class="stat-value"><?= $totalSwims ?></span>
                        </div>
                    </div>
                </div>

                <?php if (isAdmin()): ?>
                <div class="admin-controls">
                    <a href="operations.php?action=insert&entity=swim&meet=<?= urlencode($meetName) ?>&date=<?= urlencode($date) ?>" class="button">Add Swim Time</a>
                </div>
                <?php endif; ?>

                <!-- Meet Visualizations -->
                <?php if ($totalSwims > 0): ?>
                <div class="performance-visualizations">
                    <h2>Meet Analysis</h2>

                    <div class="visualization-container">
                        <div class="chart-container">
                            <h3>Event Distribution</h3>
                            <canvas id="eventDistributionChart"></canvas>
                        </div>

                        <div class="chart-container">
                            <h3>Gender Distribution</h3>
                            <canvas id="genderDistributionChart"></canvas>
                        </div>
                        
                        <div class="chart-container">
                            <h3>Team Participation</h3>
                            <canvas id="teamParticipationChart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Enhanced visualizations -->
                    <h2>Performance Insights</h2>
                    
                    <div class="visualization-container">
                        <?php if ($mostPopularEvent && !empty($timeDistribution)): ?>
                        <div class="chart-container">
                            <h3>Distribution of Most Popular Event: <?= htmlspecialchars($mostPopularEvent) ?></h3>
                            <canvas id="timeDistributionChart"></canvas>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($topEvents)): ?>
                        <div class="chart-container">
                            <h3>Team Performance Comparison</h3>
                            <div class="event-selector">
                                <label for="eventCompareSelect">Select Event:</label>
                                <select id="eventCompareSelect">
                                    <?php foreach ($topEvents as $event): ?>
                                    <option value="<?= htmlspecialchars($event) ?>"><?= htmlspecialchars($event) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <canvas id="teamPerformanceChart"></canvas>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="visualization-container">
                        <div class="chart-container">
                            <h3>Top Performers by Event</h3>
                            <div class="event-selector">
                                <label for="topPerformersSelect">Select Event:</label>
                                <select id="topPerformersSelect">
                                    <?php foreach (array_keys($eventData) as $event): ?>
                                    <option value="<?= htmlspecialchars($event) ?>"><?= htmlspecialchars($event) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <canvas id="topPerformersChart"></canvas>
                        </div>
                        
                        <div class="chart-container">
                            <h3>Team Medal Count</h3>
                            <canvas id="medalCountChart"></canvas>
                            <p class="chart-note">Medal count based on top 3 finishers in each event</p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Participating Teams -->
                <div class="participating-teams">
                    <h2>Participating Teams</h2>
                    <?php if ($teamCount > 0): ?>
                    <table>
                        <tr>
                            <th>Team</th>
                            <th>Location</th>
                            <th>Conference</th>
                            <th>Swimmers</th>
                            <th>Actions</th>
                        </tr>
                        <?php 
                        $teamsResult->data_seek(0); // Reset result pointer
                        while ($team = $teamsResult->fetch_assoc()): 
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($team['team']) ?></td>
                            <td><?= htmlspecialchars($team['location'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars(($team['confName'] ?? 'N/A') . ' (' . ($team['confState'] ?? 'N/A') . ')') ?></td>
                            <td><?= htmlspecialchars($team['swimmerCount']) ?></td>
                            <td>
                                <a href="team_profile.php?team=<?= urlencode($team['team']) ?>" class="button">View Team</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </table>
                    <?php else: ?>
                    <p>No teams found for this meet.</p>
                    <?php endif; ?>
                </div>

                <!-- Swimmers and Results -->
                <div class="swim-results">
                    <h2>Results by Event</h2>
                    <?php if ($totalSwims > 0): ?>
                        <?php
                        // Group results by event
                        $eventResults = [];
                        $swimResult->data_seek(0); // Reset result pointer
                        
                        while ($swim = $swimResult->fetch_assoc()) {
                            $event = $swim['eventName'];
                            if (!isset($eventResults[$event])) {
                                $eventResults[$event] = [];
                            }
                            $eventResults[$event][] = $swim;
                        }
                        
                        // Display each event
                        foreach ($eventResults as $event => $swims):
                        ?>
                        <div class="event-section">
                            <h3><?= htmlspecialchars($event) ?></h3>
                            <table>
                                <tr>
                                    <th>Place</th>
                                    <th>Swimmer</th>
                                    <th>Team</th>
                                    <th>Time</th>
                                    <th>Actions</th>
                                </tr>
                                <?php 
                                // Sort swims by time
                                usort($swims, function($a, $b) {
                                    return $a['time'] <=> $b['time'];
                                });
                                
                                foreach ($swims as $index => $swim): 
                                ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($swim['swimmerName']) ?></td>
                                    <td><?= htmlspecialchars($swim['swimmerTeam']) ?></td>
                                    <td><?= secondsToTime($swim['time']) ?></td>
                                    <td>
                                        <a href="swimmer_profile.php?id=<?= $swim['swimmerID'] ?>" class="button">View Swimmer</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                    <p>No results found for this meet.</p>
                    <?php endif; ?>
                </div>

                <!-- JavaScript for Charts -->
                <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    <?php if ($totalSwims > 0): ?>
                    // Event distribution pie chart
                    const eventLabels = <?= json_encode(array_keys($eventData)) ?>;
                    const eventCounts = <?= json_encode(array_values($eventData)) ?>;
                    const eventColors = generateColors(eventLabels.length);

                    new Chart(document.getElementById('eventDistributionChart'), {
                        type: 'pie',
                        data: {
                            labels: eventLabels,
                            datasets: [{
                                data: eventCounts,
                                backgroundColor: eventColors
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    position: 'right'
                                }
                            }
                        }
                    });

                    // Gender distribution chart
                    const genderData = <?= json_encode($genderDistribution) ?>;
                    
                    new Chart(document.getElementById('genderDistributionChart'), {
                        type: 'pie',
                        data: {
                            labels: ['Male', 'Female'],
                            datasets: [{
                                data: [genderData['M'], genderData['F']],
                                backgroundColor: ['#36a2eb', '#ff6384']
                            }]
                        },
                        options: {
                            responsive: true
                        }
                    });
                    
                    // Team participation chart
                    const teamNames = <?= json_encode(array_keys($teamEventData)) ?>;
                    const teamCounts = teamNames.map(team => {
                        return Object.values(<?= json_encode($teamEventData) ?>[team])
                                   .reduce((sum, count) => sum + count, 0);
                    });
                    
                    new Chart(document.getElementById('teamParticipationChart'), {
                        type: 'bar',
                        data: {
                            labels: teamNames,
                            datasets: [{
                                label: 'Number of Entries',
                                data: teamCounts,
                                backgroundColor: generateColors(teamNames.length)
                            }]
                        },
                        options: {
                            responsive: true,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    title: {
                                        display: true,
                                        text: 'Number of Swims'
                                    }
                                }
                            }
                        }
                    });
                    
                    // NEW: Time distribution histogram for most popular event
                    <?php if ($mostPopularEvent && !empty($timeDistribution)): ?>
                    // Get time distribution data
                    const timeData = <?= json_encode($timeDistribution) ?>;
                    
                    // Create bins for histogram (10 bins)
                    const minTime = Math.min(...timeData);
                    const maxTime = Math.max(...timeData);
                    const binWidth = (maxTime - minTime) / 10;
                    const bins = Array(10).fill(0);
                    const binLabels = [];
                    
                    // Create bin labels
                    for (let i = 0; i < 10; i++) {
                        const start = minTime + i * binWidth;
                        const end = minTime + (i + 1) * binWidth;
                        binLabels.push(formatTime(start) + ' - ' + formatTime(end));
                    }
                    
                    // Fill bins
                    timeData.forEach(time => {
                        const binIndex = Math.min(Math.floor((time - minTime) / binWidth), 9);
                        bins[binIndex]++;
                    });
                    
                    // Create histogram
                    new Chart(document.getElementById('timeDistributionChart'), {
                        type: 'bar',
                        data: {
                            labels: binLabels,
                            datasets: [{
                                label: 'Number of Swimmers',
                                data: bins,
                                backgroundColor: '#00796b'
                            }]
                        },
                        options: {
                            responsive: true,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    title: {
                                        display: true,
                                        text: 'Count'
                                    }
                                },
                                x: {
                                    title: {
                                        display: true,
                                        text: 'Time Range'
                                    }
                                }
                            }
                        }
                    });
                    
                    // Helper function to format time for display
                    function formatTime(seconds) {
                        const mins = Math.floor(seconds / 60);
                        const secs = Math.floor(seconds % 60);
                        const ms = Math.round((seconds % 1) * 100);
                        return `${mins}:${secs.toString().padStart(2, '0')}:${ms.toString().padStart(2, '0')}`;
                    }
                    <?php endif; ?>
                    
                    // NEW: Team performance comparison
                    <?php if (!empty($topEvents)): ?>
                    // Team performance data
                    const teamPerformanceData = <?= json_encode($teamPerformance) ?>;
                    const allTeams = <?= json_encode(array_keys($teamPerformance)) ?>;
                    const allEvents = <?= json_encode(array_keys($eventData)) ?>;
                    
                    // Initial chart with first event
                    let teamPerformanceChart = null;
                    
                    function updateTeamPerformanceChart(selectedEvent) {
                        // Collect average times for each team for this event
                        const teams = [];
                        const avgTimes = [];
                        const bestTimes = [];
                        
                        allTeams.forEach(team => {
                            if (teamPerformanceData[team] && 
                                teamPerformanceData[team][selectedEvent] && 
                                teamPerformanceData[team][selectedEvent].times.length > 0) {
                                teams.push(team);
                                avgTimes.push(teamPerformanceData[team][selectedEvent].avg);
                                bestTimes.push(teamPerformanceData[team][selectedEvent].best);
                            }
                        });
                        
                        // If no data, show a message
                        if (teams.length === 0) {
                            if (teamPerformanceChart) {
                                teamPerformanceChart.destroy();
                                teamPerformanceChart = null;
                            }
                            document.getElementById('teamPerformanceChart').style.display = 'none';
                            return;
                        }
                        
                        document.getElementById('teamPerformanceChart').style.display = 'block';
                        
                        // Sort teams by average time
                        const sortedIndices = avgTimes.map((time, i) => i)
                                              .sort((a, b) => avgTimes[a] - avgTimes[b]);
                        
                        const sortedTeams = sortedIndices.map(i => teams[i]);
                        const sortedAvgTimes = sortedIndices.map(i => avgTimes[i]);
                        const sortedBestTimes = sortedIndices.map(i => bestTimes[i]);
                        
                        if (teamPerformanceChart) {
                            teamPerformanceChart.destroy();
                        }
                        
                        // Create chart
                        teamPerformanceChart = new Chart(document.getElementById('teamPerformanceChart'), {
                            type: 'bar',
                            data: {
                                labels: sortedTeams,
                                datasets: [
                                    {
                                        label: 'Average Time',
                                        data: sortedAvgTimes,
                                        backgroundColor: 'rgba(0, 121, 107, 0.7)'
                                    },
                                    {
                                        label: 'Best Time',
                                        data: sortedBestTimes,
                                        backgroundColor: 'rgba(0, 77, 64, 0.7)'
                                    }
                                ]
                            },
                            options: {
                                responsive: true,
                                scales: {
                                    y: {
                                        reverse: true, // Lower times are better
                                        title: {
                                            display: true,
                                            text: 'Time (seconds)'
                                        }
                                    }
                                }
                            }
                        });
                    }
                    
                    // Initialize with first event
                    updateTeamPerformanceChart(document.getElementById('eventCompareSelect').value);
                    
                    // Add event listener for event selection
                    document.getElementById('eventCompareSelect').addEventListener('change', function() {
                        updateTeamPerformanceChart(this.value);
                    });
                    <?php endif; ?>
                    
                    // NEW: Top Performers Chart
                    const eventResults = <?= json_encode($eventResults) ?>;
                    let topPerformersChart = null;
                    
                    function updateTopPerformersChart(selectedEvent) {
                        if (!eventResults[selectedEvent]) {
                            if (topPerformersChart) {
                                topPerformersChart.destroy();
                                topPerformersChart = null;
                            }
                            document.getElementById('topPerformersChart').style.display = 'none';
                            return;
                        }
                        
                        document.getElementById('topPerformersChart').style.display = 'block';
                        
                        // Sort results by time
                        const sortedResults = [...eventResults[selectedEvent]].sort((a, b) => a.time - b.time);
                        
                        // Take top 10 or all if less than 10
                        const topResults = sortedResults.slice(0, Math.min(10, sortedResults.length));
                        const names = topResults.map(r => r.swimmerName);
                        const times = topResults.map(r => r.time);
                        const teams = topResults.map(r => r.swimmerTeam);
                        
                        // Create a color for each team
                        const teamColors = {};
                        const uniqueTeams = [...new Set(teams)];
                        const teamColorPalette = generateColors(uniqueTeams.length);
                        
                        uniqueTeams.forEach((team, index) => {
                            teamColors[team] = teamColorPalette[index];
                        });
                        
                        const barColors = teams.map(team => teamColors[team]);
                        
                        if (topPerformersChart) {
                            topPerformersChart.destroy();
                        }
                        
                        // Create chart
                        topPerformersChart = new Chart(document.getElementById('topPerformersChart'), {
                            type: 'bar',
                            data: {
                                labels: names,
                                datasets: [{
                                    label: 'Time (seconds)',
                                    data: times,
                                    backgroundColor: barColors
                                }]
                            },
                            options: {
                                responsive: true,
                                scales: {
                                    y: {
                                        reverse: true, // Lower times are better
                                        title: {
                                            display: true,
                                            text: 'Time (seconds)'
                                        }
                                    }
                                },
                                plugins: {
                                    tooltip: {
                                        callbacks: {
                                            afterLabel: function(context) {
                                                return 'Team: ' + teams[context.dataIndex];
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    }
                    
                    // Initialize with first event
                    if (document.getElementById('topPerformersSelect')) {
                        updateTopPerformersChart(document.getElementById('topPerformersSelect').value);
                        
                        // Add event listener for event selection
                        document.getElementById('topPerformersSelect').addEventListener('change', function() {
                            updateTopPerformersChart(this.value);
                        });
                    }
                    
                    // NEW: Medal Count Chart
                    // Calculate medal counts for each team
                    const medalCount = {};
                    
                    // Loop through all events
                    Object.keys(eventResults).forEach(event => {
                        // Sort results by time
                        const sortedResults = [...eventResults[event]].sort((a, b) => a.time - b.time);
                        
                        // Take top 3
                        const medalists = sortedResults.slice(0, Math.min(3, sortedResults.length));
                        
                        // Assign medals to teams
                        medalists.forEach((result, index) => {
                            const team = result.swimmerTeam;
                            
                            if (!medalCount[team]) {
                                medalCount[team] = { gold: 0, silver: 0, bronze: 0, total: 0 };
                            }
                            
                            if (index === 0) medalCount[team].gold++;
                            else if (index === 1) medalCount[team].silver++;
                            else if (index === 2) medalCount[team].bronze++;
                            
                            medalCount[team].total++;
                        });
                    });
                    
                    // Sort teams by total medals
                    const medalTeams = Object.keys(medalCount).sort((a, b) => medalCount[b].total - medalCount[a].total);
                    
                    // Create datasets
                    const goldMedals = medalTeams.map(team => medalCount[team].gold);
                    const silverMedals = medalTeams.map(team => medalCount[team].silver);
                    const bronzeMedals = medalTeams.map(team => medalCount[team].bronze);
                    
                    new Chart(document.getElementById('medalCountChart'), {
                        type: 'bar',
                        data: {
                            labels: medalTeams,
                            datasets: [
                                {
                                    label: 'Gold',
                                    data: goldMedals,
                                    backgroundColor: '#FFD700'
                                },
                                {
                                    label: 'Silver',
                                    data: silverMedals,
                                    backgroundColor: '#C0C0C0'
                                },
                                {
                                    label: 'Bronze',
                                    data: bronzeMedals,
                                    backgroundColor: '#CD7F32'
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            scales: {
                                x: {
                                    stacked: true,
                                    grid: {
                                        display: false //no x axis grid needed here
                                    }
                                },
                                y: {
                                    stacked: true,
                                    title: {
                                        display: true,
                                        text: 'Medal Count'
                                    },
                                    ticks: {
                                        precision: 0,
                                        stepSize: 1 //Only whole numbers
                                    },
                                    grid: {
                                        display: false //no y axis grid lines
                                    }
                                }
                            }
                        }
                    });

                    // Helper function to generate colors
                    function generateColors(count) {
                        const colors = [];
                        for (let i = 0; i < count; i++) {
                            const hue = (i * 137) % 360; // Golden angle approximation
                            colors.push(`hsl(${hue}, 70%, 60%)`);
                        }
                        return colors;
                    }
                    <?php endif; ?>
                });
                </script>

                <?php
            } else {
                // Meet not found
                echo showMessage("Meet not found", true);
            }
            ?>

            <p><a href="view.php?entity=meets" class="button">Back to Meets</a></p>

            <?php include 'includes/footer.php'; ?>
        </div>
    </div>

    <!-- Add custom CSS for profile page -->
    <style>
    .profile-header {
        background-color: #f5f5f5;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
    }

    .profile-stats {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-top: 15px;
    }

    .stat-box {
        background-color: white;
        border: 1px solid #ddd;
        border-radius: 5px;
        padding: 10px;
        min-width: 100px;
        text-align: center;
    }

    .stat-label {
        display: block;
        font-size: 0.8em;
        color: #777;
        margin-bottom: 5px;
    }

    .stat-value {
        display: block;
        font-size: 1.2em;
        font-weight: bold;
        color: #00796b;
    }

    .performance-visualizations {
        margin: 30px 0;
    }

    .visualization-container {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        margin-top: 20px;
        margin-bottom: 30px;
    }

    .chart-container {
        flex: 1;
        min-width: 300px;
        background-color: white;
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 20px;
    }

    .event-section {
        margin-bottom: 30px;
    }

    .admin-controls {
        margin: 20px 0;
    }
    
    .event-selector {
        margin-bottom: 15px;
    }
    
    .event-selector select {
        padding: 5px;
        border-radius: 4px;
        border: 1px solid #ddd;
        background-color: white;
    }
    
    .chart-note {
        font-size: 0.8em;
        color: #777;
        text-align: center;
        margin-top: 10px;
    }
    </style>
</body>