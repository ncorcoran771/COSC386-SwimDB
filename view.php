<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Entity must come from GET parameters only
$entity = $_GET['entity'] ?? 'swimmer';
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
            
            // Generic table viewer
            $tableMap = [
                'conferences' => 'Conference',
                'meets' => 'Meet',
                'swims' => 'Swim',
                'teams' => 'Team',
                "swimmers" => "Swimmer"
            ];

            $dbTable = $tableMap[$entity] ?? '';

            if (empty($dbTable)) {
                echo showMessage("Invalid table specified", true);
                include 'includes/footer.php';
                exit;
            }

            // Build query based on entity
            if ($dbTable === 'Swim') {
                $query = "SELECT s.eventName, s.meetName, s.meetDate, s.swimmerID, 
                            sw.name AS swimmerName, s.time 
                            FROM Swim s 
                            JOIN Swimmer sw ON s.swimmerID = sw.swimmerID WHERE 1=1";
            } else {
                $query = "SELECT * FROM $dbTable WHERE 1=1";
            }
            ?>

            <h2><?= htmlspecialchars($dbTable) ?> Search</h2>
                <form method="POST" action="view.php?entity=<?= htmlspecialchars($entity) ?>">
                    
                    <?php if ($dbTable === 'Conference'): ?>
                        <input type="text" name="name" placeholder="Search by Conference Name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                        <input type="text" name="state" placeholder="Search by State" value="<?= htmlspecialchars($_POST['state'] ?? '') ?>">
                    <?php elseif ($dbTable === 'Meet'): ?>
                        <input type="text" name="name" placeholder="Search by Meet Name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                        <input type="text" name="location" placeholder="Search by Location" value="<?= htmlspecialchars($_POST['location'] ?? '') ?>">
                    <?php elseif ($dbTable === 'Swimmer'): ?>
                        <input type="text" name="name" placeholder="Search by Name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                        <input type="number" name="swimmerID" placeholder="Search by Swimmer ID" value="<?= htmlspecialchars($_POST['swimmerID'] ?? '') ?>">
                        <select name="gender">
                            <option value="">Any Gender</option>
                            <option value="M" <?= ($_POST['gender'] ?? '') === 'M' ? 'selected' : '' ?>>Male</option>
                            <option value="F" <?= ($_POST['gender'] ?? '') === 'F' ? 'selected' : '' ?>>Female</option>
                        </select>
                        <input type="text" name="hometown" placeholder="Search by Hometown" value="<?= htmlspecialchars($_POST['hometown'] ?? '') ?>">
                        <input type="text" name="team" placeholder="Search by Team" value="<?= htmlspecialchars($_POST['team'] ?? '') ?>">
                    <?php elseif ($dbTable === 'Swim'): ?>
                        <input type="text" name="eventName" placeholder="Search by Event Name" value="<?= htmlspecialchars($_POST['eventName'] ?? '') ?>">
                        <input type="text" name="meetName" placeholder="Search by Meet Name" value="<?= htmlspecialchars($_POST['meetName'] ?? '') ?>">
                        <input type="text" name="meetDate" placeholder="Search by Meet Date (YYYY-MM-DD)" value="<?= htmlspecialchars($_POST['meetDate'] ?? '') ?>">
                        <input type="text" name="swimmerID" placeholder="Search by Swimmer ID" value="<?= htmlspecialchars($_POST['swimmerID'] ?? '') ?>">
                        <input type="text" name="time" placeholder="Search by Time" value="<?= htmlspecialchars($_POST['time'] ?? '') ?>">
                    <?php elseif ($dbTable === 'Team'): ?>
                        <input type="text" name="teamName" placeholder="Search by Meet Name" value="<?= htmlspecialchars($_POST['teamName'] ?? '') ?>">
                        <input type="text" name="location" placeholder="Search by Location" value="<?= htmlspecialchars($_POST['location'] ?? '') ?>">
                        <input type="text" name="confName" placeholder="Search by Conference Name" value="<?= htmlspecialchars($_POST['confName'] ?? '') ?>">
                        <input type="text" name="state" placeholder="Search by Conference State" value="<?= htmlspecialchars($_POST['state'] ?? '') ?>">
                    <?php endif; ?>
                    
                    <input type="submit" value="Search">
                </form>
            <?php
            // Process POST parameters for the current search
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $params = [];
                
                if ($dbTable === 'Conference') {
                    if (!empty($_POST['name'])) {
                        $query .= " AND name LIKE ?";
                        $params[] = '%' . $_POST['name'] . '%';
                    }
                    if (!empty($_POST['state'])) {
                        $query .= " AND state = ?";
                        $params[] = $_POST['state'];
                    }
                } else if ($dbTable === 'Meet') {
                    if (!empty($_POST['name'])) {
                        $query .= " AND meetName LIKE ?";
                        $params[] = '%' . $_POST['name'] . '%';
                    }
                    if (!empty($_POST['location'])) {
                        $query .= " AND location = ?";
                        $params[] = $_POST['location'];
                    }
                } elseif ($dbTable === 'Swimmer') {
                    // Handle Swimmer table specific filters
                    if (!empty($_POST['name'])) {
                        $query .= " AND name LIKE ?";
                        $params[] = '%' . $_POST['name'] . '%';
                    }
                    if (!empty($_POST['swimmerID'])) {
                        $query .= " AND swimmerID = ?";
                        $params[] = $_POST['swimmerID'];
                    }
                    if (!empty($_POST['gender'])) {
                        $query .= " AND gender = ?";
                        $params[] = $_POST['gender'];
                    }
                    if (!empty($_POST['hometown'])) {
                        $query .= " AND hometown LIKE ?";
                        $params[] = '%' . $_POST['hometown'] . '%';
                    }
                    if (!empty($_POST['team'])) {
                        $query .= " AND team LIKE ?";
                        $params[] = '%' . $_POST['team'] . '%';
                    }
                } else if ($dbTable === 'Swim') {
                    // Handle Swim table specific filters
                    if (!empty($_POST['eventName'])) {
                        $query .= " AND s.eventName LIKE ?";
                        $params[] = '%' . $_POST['eventName'] . '%';
                    }
                    if (!empty($_POST['meetName'])) {
                        $query .= " AND s.meetName LIKE ?";
                        $params[] = '%' . $_POST['meetName'] . '%';
                    }
                    if (!empty($_POST['meetDate'])) {
                        $query .= " AND s.meetDate LIKE ?";
                        $params[] = '%' . $_POST['meetDate'] . '%';
                    }
                    if (!empty($_POST['swimmerID'])) {
                        $query .= " AND s.swimmerID = ?";
                        $params[] = $_POST['swimmerID'];
                    }
                    if (!empty($_POST['time'])) {
                        $query .= " AND s.time LIKE ?";
                        $params[] = '%' . $_POST['time'] . '%';
                    }
                } else if ($dbTable === 'Team') {
                    if (!empty($_POST['teamName'])) {
                        $query .= " AND teamName LIKE ?";
                        $params[] = '%' . $_POST['teamName'] . '%';
                    }
                    if (!empty($_POST['location'])) {
                        $query .= " AND location = ?";
                        $params[] = $_POST['location'];
                    }
                    if (!empty($_POST['confName'])) {
                        $query .= " AND confName LIKE ?";
                        $params[] = '%' . $_POST['confName'] . '%';
                    }
                    if (!empty($_POST['state'])) {
                        $query .= " AND confState = ?";
                        $params[] = $_POST['state'];
                    }
                }
            }
            
            if ($dbTable === 'Swim') {
                $query .= " ORDER BY s.meetDate DESC, s.eventName";
            }

            // Prepare and execute the query
            $stmt = $conn->prepare($query);

            // Bind the parameters dynamically
            if (!empty($params)) {
                $types = str_repeat("s", count($params)); // All parameters as strings
                $stmt->bind_param($types, ...$params);
            }

            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {
                
                echo "<table>";
                
                // Get column names from the first row
                $firstRow = $result->fetch_assoc();
                echo "<tr>";
                foreach (array_keys($firstRow) as $column) {
                    echo "<th>" . htmlspecialchars($column) . "</th>";
                }
                echo "</tr>";
                
                // Reset result pointer
                $result->data_seek(0);
                
                // Display data
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    foreach ($row as $column => $value) {
                        // Format time for better readability if this is the time column
                        if ($column === 'time' && $dbTable === 'Swim') {
                            echo "<td>" . secondsToTime($value) . "</td>";
                        } else {
                            echo "<td>" . htmlspecialchars($value) . "</td>";
                        }
                    }
                    echo "</tr>";
                }
                
                echo "</table>";
            } else {
                echo showMessage("No data found");
            }
            ?>

            <?php include 'includes/footer.php'; ?>
        </div>
    </div>
</body>