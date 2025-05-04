<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Add debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Basic execution test
echo "<div style='background:#f8d7da; border:1px solid #f5c6cb; padding:10px; margin:10px 0;'>";
echo "<h3>Basic Debug Information</h3>";

// Get team name from URL parameter
$teamName = isset($_GET['team']) ? sanitize($_GET['team']) : '';
echo "<p>Team name parameter: '" . htmlspecialchars($teamName) . "'</p>";

// Check database connection
echo "<p>Database connection: " . ($conn ? "Working" : "FAILED") . "</p>";

// Test if team exists
if (!empty($teamName)) {
    $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM Team WHERE teamName = ?");
    $checkStmt->bind_param('s', $teamName);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $checkRow = $checkResult->fetch_assoc();
    echo "<p>Team exists in database: " . ($checkRow['count'] > 0 ? "Yes" : "NO - TEAM NOT FOUND") . "</p>";
}

echo "</div>";

// Redirect if no team specified
if (empty($teamName)) {
    redirect('view.php?entity=teams', 'No team specified');
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
            
            // Get team details
            $stmt = $conn->prepare("SELECT t.*, c.name as conferenceName 
                                   FROM Team t 
                                   LEFT JOIN Conference c ON t.confName = c.name AND t.confState = c.state 
                                   WHERE t.teamName = ?");
            $stmt->bind_param('s', $teamName);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                // Team found, display profile
                $location = htmlspecialchars($row['location']);
                $confName = htmlspecialchars($row['confName']);
                $confState = htmlspecialchars($row['confState']);
                
                // Get all swimmers on this team
                $swimmerStmt = $conn->prepare("SELECT * FROM Swimmer WHERE team = ? ORDER BY name");
                $swimmerStmt->bind_param('s', $teamName);
                $swimmerStmt->execute();
                $swimmersResult = $swimmerStmt->get_result();
                $swimmerCount = $swimmersResult->num_rows;
                
                // Get team performance data - FIXED QUERY
                $performanceStmt = $conn->prepare(
                    "SELECT sw.eventName, COUNT(*) as eventCount, AVG(sw.time) as avgTime, MIN(sw.time) as bestTime 
                     FROM Swimmer s 
                     JOIN Swim sw ON s.swimmerID = sw.swimmerID 
                     WHERE s.team = ? 
                     GROUP BY sw.eventName 
                     ORDER BY eventCount DESC"
                );
                $performanceStmt->bind_param('s', $teamName);
                $performanceStmt->execute();
                $performanceResult = $performanceStmt->get_result();
                
                // Process performance data for charts
                $eventLabels = [];
                $eventCounts = [];
                $eventAvgTimes = [];
                $eventBestTimes = [];
                
                while ($perfRow = $performanceResult->fetch_assoc()) {
                    $eventLabels[] = $perfRow['eventName'];
                    $eventCounts[] = $perfRow['eventCount'];
                    $eventAvgTimes[] = $perfRow['avgTime'];
                    $eventBestTimes[] = $perfRow['bestTime'];
                }
                
                // Add detailed diagnostic information
                echo "<div style='background:#f8f8f8; border:1px solid #ddd; padding:10px; margin:10px 0;'>";
                echo "<h3>Diagnostic Information</h3>";
                echo "<p>Team data retrieved: " . ($row ? "Yes" : "No") . "</p>";
                if ($row) {
                    echo "<p>Team Name: " . htmlspecialchars($row['teamName']) . "</p>";
                    echo "<p>Location: " . htmlspecialchars($row['location']) . "</p>";
                    echo "<p>Conference: " . htmlspecialchars($row['confName']) . "</p>";
                }
                echo "<p>Swimmer count: " . $swimmerCount . "</p>";
                echo "<p>Performance data points: " . count($eventLabels) . "</p>";
                if (count($eventLabels) > 0) {
                    echo "<p>Events: " . implode(", ", $eventLabels) . "</p>";
                } else {
                    echo "<p>No performance data available. Check if swimmers have swim records.</p>";
                }
                echo "</div>";
                
                // Display team header info
                ?>
                <div class="profile-header">
                    <h1><?= htmlspecialchars($teamName) ?></h1>
                    <div class="profile-stats">
                        <div class="stat-box">
                            <span class="stat-label">Location</span>
                            <span class="stat-value"><?= $location ?></span>
                        </div>
                        <div class="stat-box">
                            <span class="stat-label">Conference</span>
                            <span class="stat-value"><?= "$confName ($confState)" ?></span>
                        </div>
                        <div class="stat-box">
                            <span class="stat-label">Swimmers</span>
                            <span class="stat-value"><?= $swimmerCount ?></span>
                        </div>
                    </div>
                </div>
                
                <?php if (isAdmin()): ?>
                <div class="admin-controls">
                    <a href="team_management.php?action=edit_form&team=<?= urlencode($teamName) ?>" class="button">Edit Team</a>
                </div>
                <?php endif; ?>
                
                <!-- Team Performance Visualizations -->
                <?php if (count($eventLabels) > 0): ?>
                <div class="performance-visualizations">
                    <h2>Team Performance Analysis</h2>
                    
                    <div class="visualization-container">
                        <div class="chart-container">
                            <h3>Events Distribution</h3>
                            <canvas id="eventsDistributionChart"></canvas>
                        </div>
                        
                        <div class="chart-container">
                            <h3>Average Times by Event</h3>
                            <canvas id="avgTimesChart"></canvas>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="message">
                    <p>No performance data is available for this team. Add swim times for team members to see performance visualizations.</p>
                </div>
                <?php endif; ?>
                
                <!-- Swimmers Table -->
                <div class="team-swimmers">
                    <h2>Team Roster</h2>
                    <?php if ($swimmerCount > 0): ?>
                    <table>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Gender</th>
                            <th>Hometown</th>
                            <th>Power Index</th>
                            <th>Actions</th>
                        </tr>
                        <?php while ($swimmer = $swimmersResult->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($swimmer['swimmerID']) ?></td>
                            <td><?= htmlspecialchars($swimmer['name']) ?></td>
                            <td><?= htmlspecialchars($swimmer['gender']) ?></td>
                            <td><?= htmlspecialchars($swimmer['hometown']) ?></td>
                            <td><?= htmlspecialchars($swimmer['powerIndex']) ?></td>
                            <td>
                                <a href="swimmer_profile.php?id=<?= $swimmer['swimmerID'] ?>" class="button">View Profile</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </table>
                    <?php else: ?>
                    <p>No swimmers found for this team.</p>
                    <?php endif; ?>
                </div>
                
                <!-- JavaScript for Charts -->
                <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    <?php if (count($eventLabels) > 0): ?>
                    // Event distribution chart
                    const eventLabels = <?= json_encode($eventLabels) ?>;
                    const eventCounts = <?= json_encode($eventCounts) ?>;
                    const colors = generateColors(eventLabels.length);
                    
                    new Chart(document.getElementById('eventsDistributionChart'), {
                        type: 'bar',
                        data: {
                            labels: eventLabels,
                            datasets: [{
                                label: 'Number of Swims',
                                data: eventCounts,
                                backgroundColor: colors
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
                                }
                            }
                        }
                    });
                    
                    // Average times chart
                    const avgTimes = <?= json_encode($eventAvgTimes) ?>;
                    const bestTimes = <?= json_encode($eventBestTimes) ?>;
                    
                    new Chart(document.getElementById('avgTimesChart'), {
                        type: 'bar',
                        data: {
                            labels: eventLabels,
                            datasets: [
                                {
                                    label: 'Average Time',
                                    data: avgTimes,
                                    backgroundColor: 'rgba(0, 121, 107, 0.7)'
                                },
                                {
                                    label: 'Best Time',
                                    data: bestTimes,
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
                // Team not found
                echo showMessage("Team not found", true);
            }
            ?>

            <p><a href="view.php?entity=teams" class="button">Back to Teams</a></p>

            <?php include 'includes/footer.php'; ?>
        </div>
    </div>
    
    <!-- Add custom CSS for profile page (same as swimmer profile) -->
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
    
    .admin-controls {
        margin: 20px 0;
    }
    </style>
</body>