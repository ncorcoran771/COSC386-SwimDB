<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Get swimmer ID from URL parameter
$swimmerID = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Redirect if no valid swimmer ID provided
if ($swimmerID <= 0) {
    redirect('view.php?entity=swimmers', 'No swimmer specified');
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
            
            // Get swimmer details
            $stmt = $conn->prepare("SELECT * FROM Swimmer WHERE swimmerID = ?");
            $stmt->bind_param('i', $swimmerID);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                // Swimmer found, display profile
                $swimmerName = htmlspecialchars($row['name']);
                $gender = htmlspecialchars($row['gender']);
                $hometown = htmlspecialchars($row['hometown']);
                $team = htmlspecialchars($row['team']);
                $powerIndex = htmlspecialchars($row['powerIndex']);
                
                // Get team details
                $teamInfo = null;
                $teamStmt = $conn->prepare("SELECT t.*, c.name as conferenceName FROM Team t 
                                          LEFT JOIN Conference c ON t.confName = c.name AND t.confState = c.state
                                          WHERE t.teamName = ?");
                $teamStmt->bind_param('s', $row['team']);
                $teamStmt->execute();
                $teamResult = $teamStmt->get_result();
                if ($teamRow = $teamResult->fetch_assoc()) {
                    $teamInfo = $teamRow;
                }
                
                // Get all swim records for this swimmer
                $swimStmt = $conn->prepare("SELECT s.*, m.location as meetLocation 
                                          FROM Swim s
                                          LEFT JOIN Meet m ON s.meetName = m.meetName AND s.meetDate = m.date
                                          WHERE s.swimmerID = ?
                                          ORDER BY s.meetDate ASC, s.eventName ASC");
                $swimStmt->bind_param('i', $swimmerID);
                $swimStmt->execute();
                $swimResult = $swimStmt->get_result();
                
                // Count events and calculate statistics
                $totalEvents = $swimResult->num_rows;
                $swimRecords = [];
                $eventCounts = [];
                $eventTimes = [];
                
                // Process swim records and prepare data for charts
                if ($totalEvents > 0) {
                    $swimResult->data_seek(0); // Reset result pointer
                    while ($swimRow = $swimResult->fetch_assoc()) {
                        // Store the record
                        $swimRecords[] = $swimRow;
                        
                        // Count events for pie chart
                        $event = $swimRow['eventName'];
                        if (!isset($eventCounts[$event])) {
                            $eventCounts[$event] = 0;
                        }
                        $eventCounts[$event]++;
                        
                        // Store times for line chart (by event)
                        if (!isset($eventTimes[$event])) {
                            $eventTimes[$event] = [];
                        }
                        $eventTimes[$event][] = [
                            'date' => $swimRow['meetDate'],
                            'time' => $swimRow['time']
                        ];
                    }
                }
                
                // Display swimmer header info
                ?>
                <div class="profile-header">
                    <h1><?= $swimmerName ?></h1>
                    <div class="profile-stats">
                        <div class="stat-box">
                            <span class="stat-label">ID</span>
                            <span class="stat-value"><?= $swimmerID ?></span>
                        </div>
                        <div class="stat-box">
                            <span class="stat-label">Gender</span>
                            <span class="stat-value"><?= $gender ?></span>
                        </div>
                        <div class="stat-box">
                            <span class="stat-label">Hometown</span>
                            <span class="stat-value"><?= $hometown ?></span>
                        </div>
                        <div class="stat-box">
                            <span class="stat-label">Team</span>
                            <span class="stat-value">
                                <a href="team_profile.php?team=<?= urlencode($team) ?>"><?= $team ?></a>
                            </span>
                        </div>
                        <div class="stat-box">
                            <span class="stat-label">Power Index</span>
                            <span class="stat-value"><?= $powerIndex ?></span>
                        </div>
                        <div class="stat-box">
                            <span class="stat-label">Events</span>
                            <span class="stat-value"><?= $totalEvents ?></span>
                        </div>
                    </div>
                </div>
                
                <?php if (isAdmin()): ?>
                <div class="admin-controls">
                    <a href="operations.php?action=insert&entity=swim&swimmer=<?= $swimmerID ?>" class="button">Add Swim Time</a>
                </div>
                <?php endif; ?>
                
                <!-- Performance Visualizations -->
                <?php if ($totalEvents > 0): ?>
                <div class="performance-visualizations">
                    <h2>Performance Analysis</h2>
                    
                    <div class="visualization-container">
                        <div class="chart-container">
                            <h3>Event Distribution</h3>
                            <canvas id="eventDistributionChart"></canvas>
                        </div>
                        
                        <div class="chart-container">
                            <h3>Performance Trends</h3>
                            <div class="event-selector">
                                <label for="eventSelect">Select Event:</label>
                                <select id="eventSelect">
                                    <?php foreach (array_keys($eventTimes) as $event): ?>
                                    <option value="<?= htmlspecialchars($event) ?>"><?= htmlspecialchars($event) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <canvas id="performanceTrendChart"></canvas>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Swim Records Table -->
                <div class="swim-records">
                    <h2>Swim Records</h2>
                    <?php if ($totalEvents > 0): ?>
                    <table>
                        <tr>
                            <th>Event</th>
                            <th>Time</th>
                            <th>Meet</th>
                            <th>Location</th>
                            <th>Date</th>
                        </tr>
                        <?php foreach ($swimRecords as $record): ?>
                        <tr>
                            <td><?= htmlspecialchars($record['eventName']) ?></td>
                            <td><?= secondsToTime($record['time']) ?></td>
                            <td><?= htmlspecialchars($record['meetName']) ?></td>
                            <td><?= htmlspecialchars($record['meetLocation']) ?></td>
                            <td><?= htmlspecialchars($record['meetDate']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                    <?php else: ?>
                    <p>No swim records found for this swimmer.</p>
                    <?php endif; ?>
                </div>
                
                <!-- JavaScript for Charts -->
                <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    <?php if ($totalEvents > 0): ?>
                    // Event distribution pie chart
                    const eventLabels = <?= json_encode(array_keys($eventCounts)) ?>;
                    const eventData = <?= json_encode(array_values($eventCounts)) ?>;
                    const eventColors = generateColors(eventLabels.length);
                    
                    new Chart(document.getElementById('eventDistributionChart'), {
                        type: 'pie',
                        data: {
                            labels: eventLabels,
                            datasets: [{
                                data: eventData,
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
                    
                    // Performance trend line chart
                    const eventTimesData = <?= json_encode($eventTimes) ?>;
                    let currentEvent = document.getElementById('eventSelect').value;
                    let performanceChart = null;
                    
                    function updatePerformanceChart(eventName) {
                        const eventData = eventTimesData[eventName];
                        const dates = eventData.map(item => item.date);
                        const times = eventData.map(item => item.time);
                        
                        if (performanceChart) {
                            performanceChart.destroy();
                        }
                        
                        performanceChart = new Chart(document.getElementById('performanceTrendChart'), {
                            type: 'line',
                            data: {
                                labels: dates,
                                datasets: [{
                                    label: 'Time (seconds)',
                                    data: times,
                                    borderColor: '#00796b',
                                    tension: 0.1
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
                                    },
                                    x: {
                                        title: {
                                            display: true,
                                            text: 'Meet Date'
                                        }
                                    }
                                }
                            }
                        });
                    }
                    
                    // Initialize performance chart
                    updatePerformanceChart(currentEvent);
                    
                    // Event select change handler
                    document.getElementById('eventSelect').addEventListener('change', function() {
                        updatePerformanceChart(this.value);
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
                // Swimmer not found
                echo showMessage("Swimmer not found", true);
            }
            ?>

            <p><a href="view.php?entity=swimmers" class="button">Back to Swimmers</a></p>

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
    }
    
    .chart-container {
        flex: 1;
        min-width: 300px;
        background-color: white;
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 15px;
    }
    
    .event-selector {
        margin-bottom: 15px;
    }
    
    .admin-controls {
        margin: 20px 0;
    }
    </style>
</body>
