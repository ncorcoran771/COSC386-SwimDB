<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/data.php'; // For events array

// Add debugging if needed
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$user = getCurrentUser();

// Process filters
$gender = isset($_GET['gender']) ? sanitize($_GET['gender']) : '';
$eventCategory = isset($_GET['category']) ? sanitize($_GET['category']) : '';
$teamFilter = isset($_GET['team']) ? sanitize($_GET['team']) : '';
$conferenceFilter = isset($_GET['conference']) ? sanitize($_GET['conference']) : '';

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<body>
    <div class="main">
        <div class="container">
            <h1>Event Records</h1>
            
            <?php
            // Show message if any
            if (isset($_SESSION['message'])) {
                echo showMessage($_SESSION['message']);
                unset($_SESSION['message']);
            }
            ?>
            
            <!-- Filters Section -->
            <div class="filters-section">
                <h2>Filter Records</h2>
                <form method="get" action="event_records.php" class="filter-form">
                    <div class="filter-group">
                        <label for="gender">Gender:</label>
                        <select name="gender" id="gender">
                            <option value="">All</option>
                            <option value="M" <?= $gender === 'M' ? 'selected' : '' ?>>Male</option>
                            <option value="F" <?= $gender === 'F' ? 'selected' : '' ?>>Female</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="category">Event Category:</label>
                        <select name="category" id="category">
                            <option value="">All</option>
                            <?php foreach (array_keys($events) as $category): ?>
                                <option value="<?= htmlspecialchars($category) ?>" <?= $eventCategory === $category ? 'selected' : '' ?>><?= htmlspecialchars($category) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="team">Team:</label>
                        <select name="team" id="team">
                            <option value="">All</option>
                            <?php
                            // Get all teams
                            $teamQuery = "SELECT DISTINCT teamName FROM Team ORDER BY teamName";
                            $teamResult = $conn->query($teamQuery);
                            while ($teamRow = $teamResult->fetch_assoc()):
                            ?>
                                <option value="<?= htmlspecialchars($teamRow['teamName']) ?>" <?= $teamFilter === $teamRow['teamName'] ? 'selected' : '' ?>><?= htmlspecialchars($teamRow['teamName']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="conference">Conference:</label>
                        <select name="conference" id="conference">
                            <option value="">All</option>
                            <?php
                            // Get all conferences
                            $confQuery = "SELECT DISTINCT c.name, c.state FROM Conference c ORDER BY c.name";
                            $confResult = $conn->query($confQuery);
                            while ($confRow = $confResult->fetch_assoc()):
                                $confId = $confRow['name'] . '-' . $confRow['state'];
                            ?>
                                <option value="<?= htmlspecialchars($confId) ?>" <?= $conferenceFilter === $confId ? 'selected' : '' ?>><?= htmlspecialchars($confRow['name']) ?> (<?= htmlspecialchars($confRow['state']) ?>)</option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <button type="submit" class="button">Apply Filters</button>
                        <a href="event_records.php" class="button">Reset</a>
                    </div>
                </form>
            </div>
            
            <!-- Records Display Section -->
            <div class="records-container">
                <?php
                // Process event category filter
                $displayEvents = [];
                if (!empty($eventCategory) && isset($events[$eventCategory])) {
                    $displayEvents = $events[$eventCategory];
                } else {
                    // If no specific category, flatten the array to get all events
                    foreach ($events as $categoryEvents) {
                        $displayEvents = array_merge($displayEvents, $categoryEvents);
                    }
                }
                
                // Display each event's records
                foreach ($displayEvents as $eventName):
                    
                    // Build query to find records for this event
                    $query = "SELECT s.swimmerID, sw.name AS swimmerName, sw.gender, sw.team, 
                              s.time, s.meetName, s.meetDate, t.confName, t.confState
                              FROM Swim s 
                              JOIN Swimmer sw ON s.swimmerID = sw.swimmerID
                              LEFT JOIN Team t ON sw.team = t.teamName
                              WHERE s.eventName = ?";
                    
                    $params = [$eventName];
                    $types = 's';
                    
                    // Apply filters
                    if (!empty($gender)) {
                        $query .= " AND sw.gender = ?";
                        $params[] = $gender;
                        $types .= 's';
                    }
                    
                    if (!empty($teamFilter)) {
                        $query .= " AND sw.team = ?";
                        $params[] = $teamFilter;
                        $types .= 's';
                    }
                    
                    if (!empty($conferenceFilter)) {
                        list($confName, $confState) = explode('-', $conferenceFilter);
                        $query .= " AND t.confName = ? AND t.confState = ?";
                        $params[] = $confName;
                        $params[] = $confState;
                        $types .= 'ss';
                    }
                    
                    // Order by time (ascending) and limit to top 3
                    $query .= " ORDER BY s.time ASC LIMIT 3";
                    
                    // Execute query
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param($types, ...$params);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    // Only display event if there are records
                    if ($result->num_rows > 0):
                ?>
                <div class="event-record-card">
                    <h3><?= htmlspecialchars($eventName) ?></h3>
                    <table class="records-table">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Name</th>
                                <th>Gender</th>
                                <th>Team</th>
                                <th>Time</th>
                                <th>Meet</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $rank = 1;
                            while ($row = $result->fetch_assoc()):
                            ?>
                            <tr class="<?= $rank === 1 ? 'gold-record' : ($rank === 2 ? 'silver-record' : 'bronze-record') ?>">
                                <td><?= $rank ?></td>
                                <td>
                                    <a href="swimmer_profile.php?id=<?= $row['swimmerID'] ?>">
                                        <?= htmlspecialchars($row['swimmerName']) ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($row['gender']) ?></td>
                                <td>
                                    <a href="team_profile.php?team=<?= urlencode($row['team']) ?>">
                                        <?= htmlspecialchars($row['team']) ?>
                                    </a>
                                </td>
                                <td class="time-cell"><?= secondsToTime($row['time']) ?></td>
                                <td>
                                    <a href="meet_profile.php?name=<?= urlencode($row['meetName']) ?>&date=<?= urlencode($row['meetDate']) ?>">
                                        <?= htmlspecialchars($row['meetName']) ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($row['meetDate']) ?></td>
                            </tr>
                            <?php
                            $rank++;
                            endwhile;
                            ?>
                        </tbody>
                    </table>
                </div>
                <?php
                    endif; // End if results found
                endforeach; // End foreach event
                ?>
            </div>
            
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>
    
    <style>
    /* Specific styles for event records page */
    .filters-section {
        background-color: #f5f5f5;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    
    .filter-form {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        align-items: flex-end;
    }
    
    .filter-group {
        flex: 1;
        min-width: 150px;
    }
    
    .filter-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
    }
    
    .filter-group select {
        width: 100%;
        padding: 8px;
        border-radius: 4px;
        border: 1px solid #ddd;
    }
    
    .records-container {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
    }
    
    .event-record-card {
        flex: 1 1 100%;
        background-color: white;
        padding: 15px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 15px;
    }
    
    .event-record-card h3 {
        margin-top: 0;
        color: #00796b;
        border-bottom: 2px solid #00796b;
        padding-bottom: 8px;
    }
    
    .records-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .records-table th, .records-table td {
        padding: 10px;
        text-align: left;
    }
    
    .time-cell {
        font-weight: bold;
        font-family: monospace;
    }
    
    .gold-record {
        background-color: rgba(255, 215, 0, 0.1);
    }
    
    .silver-record {
        background-color: rgba(192, 192, 192, 0.1);
    }
    
    .bronze-record {
        background-color: rgba(205, 127, 50, 0.1);
    }
    
    @media (max-width: 768px) {
        .filter-group {
            flex: 1 1 45%;
        }
    }
    </style>
</body>