<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Check if user is admin
if (!isAdmin()) {
    redirect('home.php', 'Unauthorized access');
}

$action = $_REQUEST['action'] ?? 'list';
$user = getCurrentUser();

include 'includes/header.php';
include 'includes/sidebar.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>

<body>
    <div class='main'>
        <div class='container'>
            <h1>Swim Record Management</h1>

            <?php
            // Show message if any
            if (isset($_SESSION['message'])) {
                echo showMessage($_SESSION['message']);
                unset($_SESSION['message']);
            }

            // Handle add swim time
            if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $swimmerID = sanitize($_POST['swimmerID']);
                $eventName = sanitize($_POST['eventName']);
                $meetName = sanitize($_POST['meetName']);
                $meetDate = sanitize($_POST['meetDate']);
                
                // IMPROVED DATE VALIDATION
                $meetDate = trim($meetDate);
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $meetDate)) {
                    $timestamp = strtotime($meetDate);
                    if ($timestamp === false) {
                        echo showMessage("Invalid date format. Please use YYYY-MM-DD format.", true);
                    } else {
                        $meetDate = date('Y-m-d', $timestamp);
                    }
                }
                
                $timeStr = sanitize($_POST['time']);
                $timeInSeconds = timeToSeconds($timeStr);
                
                // Check if meet exists, if not create it
                $stmt = $conn->prepare("SELECT * FROM Meet WHERE meetName = ? AND date = ?");
                $stmt->bind_param('ss', $meetName, $meetDate);
                $stmt->execute();
                
                if ($stmt->get_result()->num_rows === 0) {
                    $location = sanitize($_POST['meetLocation'] ?? 'Unknown');
                    
                    $stmt = $conn->prepare("INSERT INTO Meet (meetName, location, date) VALUES (?, ?, ?)");
                    $stmt->bind_param('sss', $meetName, $location, $meetDate);
                    $stmt->execute();
                }
                
                // Insert swim record
                $stmt = $conn->prepare("INSERT INTO Swim (eventName, meetName, meetDate, swimmerID, time) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param('sssid', $eventName, $meetName, $meetDate, $swimmerID, $timeInSeconds);
                
                if ($stmt->execute()) {
                    echo showMessage("Swim record added successfully");
                } else {
                    echo showMessage("Error adding swim record: " . $stmt->error, true);
                }
            }

            // Handle delete action
            if ($action === 'delete') {
                // Get parameters either from URL or POST
                $swimmerID = isset($_REQUEST['swimmerID']) ? intval($_REQUEST['swimmerID']) : 0;
                $eventName = isset($_REQUEST['eventName']) ? sanitize($_REQUEST['eventName']) : '';
                $meetName = isset($_REQUEST['meetName']) ? sanitize($_REQUEST['meetName']) : '';
                $meetDate = isset($_REQUEST['meetDate']) ? sanitize($_REQUEST['meetDate']) : '';
                
                if (empty($swimmerID) || empty($eventName) || empty($meetName) || empty($meetDate)) {
                    echo showMessage("Error: Missing swim record information for deletion", true);
                } else {
                    // Check if the swim record exists
                    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM Swim WHERE swimmerID = ? AND eventName = ? AND meetName = ? AND meetDate = ?");
                    $stmt->bind_param('isss', $swimmerID, $eventName, $meetName, $meetDate);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $row = $result->fetch_assoc();
                    
                    if ($row['count'] == 0) {
                        echo showMessage("Error: Swim record not found", true);
                    } else {
                        // Delete the swim record
                        $stmt = $conn->prepare("DELETE FROM Swim WHERE swimmerID = ? AND eventName = ? AND meetName = ? AND meetDate = ?");
                        $stmt->bind_param('isss', $swimmerID, $eventName, $meetName, $meetDate);
                        
                        if ($stmt->execute() && $stmt->affected_rows > 0) {
                            echo showMessage("Swim record deleted successfully");
                        } else {
                            echo showMessage("Error deleting swim record: " . $stmt->error, true);
                        }
                    }
                }
            }

            // Handle edit swim action
            if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $oldSwimmerID = sanitize($_POST['oldSwimmerID']);
                $oldEventName = sanitize($_POST['oldEventName']);
                $oldMeetName = sanitize($_POST['oldMeetName']);
                $oldMeetDate = sanitize($_POST['oldMeetDate']);
                
                $swimmerID = sanitize($_POST['swimmerID']);
                $eventName = sanitize($_POST['eventName']);
                $meetName = sanitize($_POST['meetName']);
                $meetDate = sanitize($_POST['meetDate']);
                $timeStr = sanitize($_POST['time']);
                
                // Convert time to seconds for DB storage
                $timeInSeconds = timeToSeconds($timeStr);
                
                // Check if the meet exists, if not create it
                $stmt = $conn->prepare("SELECT * FROM Meet WHERE meetName = ? AND date = ?");
                $stmt->bind_param('ss', $meetName, $meetDate);
                $stmt->execute();
                
                if ($stmt->get_result()->num_rows === 0) {
                    // Default location if not provided
                    $location = sanitize($_POST['meetLocation'] ?? 'Unknown');
                    
                    // Insert new meet
                    $stmt = $conn->prepare("INSERT INTO Meet (meetName, location, date) VALUES (?, ?, ?)");
                    $stmt->bind_param('sss', $meetName, $location, $meetDate);
                    
                    if (!$stmt->execute()) {
                        echo showMessage("Error creating meet: " . $stmt->error, true);
                    }
                }
                
                // Begin transaction for safety
                $conn->begin_transaction();
                
                try {
                    // Delete old record
                    $stmt = $conn->prepare("DELETE FROM Swim WHERE swimmerID = ? AND eventName = ? AND meetName = ? AND meetDate = ?");
                    $stmt->bind_param('isss', $oldSwimmerID, $oldEventName, $oldMeetName, $oldMeetDate);
                    
                    if (!$stmt->execute()) {
                        throw new Exception("Failed to delete old swim record: " . $stmt->error);
                    }
                    
                    // Insert new record
                    $stmt = $conn->prepare("INSERT INTO Swim (swimmerID, eventName, meetName, meetDate, time) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param('isssd', $swimmerID, $eventName, $meetName, $meetDate, $timeInSeconds);
                    
                    if (!$stmt->execute()) {
                        throw new Exception("Failed to insert new swim record: " . $stmt->error);
                    }
                    
                    $conn->commit();
                    echo showMessage("Swim record updated successfully");
                } catch (Exception $e) {
                    $conn->rollback();
                    echo showMessage("Error updating swim record: " . $e->getMessage(), true);
                }
                
                // Reset action to list view
                $action = 'list';
            }

            // Show edit form if requested
            if ($action === 'edit_form' && isset($_GET['swimmerID']) && isset($_GET['eventName']) && isset($_GET['meetName']) && isset($_GET['meetDate'])) {
                $swimmerID = sanitize($_GET['swimmerID']);
                $eventName = sanitize($_GET['eventName']);
                $meetName = sanitize($_GET['meetName']);
                $meetDate = sanitize($_GET['meetDate']);
                
                // Get swim record data
                $stmt = $conn->prepare("SELECT s.*, sw.name AS swimmerName, m.location AS meetLocation 
                                      FROM Swim s
                                      JOIN Swimmer sw ON s.swimmerID = sw.swimmerID
                                      JOIN Meet m ON s.meetName = m.meetName AND s.meetDate = m.date
                                      WHERE s.swimmerID = ? AND s.eventName = ? AND s.meetName = ? AND s.meetDate = ?");
                $stmt->bind_param('isss', $swimmerID, $eventName, $meetName, $meetDate);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($row = $result->fetch_assoc()) {
                    // Format time for display
                    $formattedTime = secondsToTime($row['time']);
                    ?>
                    <div class="edit-form" style="margin: 20px 0; background: #f5f5f5; padding: 15px; border-radius: 5px;">
                        <h3>Edit Swim Record</h3>
                        <p><strong>Swimmer:</strong> <?= htmlspecialchars($row['swimmerName']) ?> (ID: <?= $swimmerID ?>)</p>
                        <p><strong>Original Event:</strong> <?= htmlspecialchars($eventName) ?></p>
                        <p><strong>Original Meet:</strong> <?= htmlspecialchars($meetName) ?> on <?= htmlspecialchars($meetDate) ?></p>
                        
                        <form method="post" action="?action=edit">
                            <input type="hidden" name="oldSwimmerID" value="<?= $swimmerID ?>">
                            <input type="hidden" name="oldEventName" value="<?= htmlspecialchars($eventName) ?>">
                            <input type="hidden" name="oldMeetName" value="<?= htmlspecialchars($meetName) ?>">
                            <input type="hidden" name="oldMeetDate" value="<?= htmlspecialchars($meetDate) ?>">
                            
                            <div>
                                <label for="swimmerID">Swimmer:</label>
                                <select name="swimmerID" required>
                                    <?php
                                    // Get all swimmers
                                    $swimmersResult = $conn->query("SELECT swimmerID, name FROM Swimmer ORDER BY name");
                                    while ($swimmer = $swimmersResult->fetch_assoc()) {
                                        $selected = ($swimmer['swimmerID'] == $swimmerID) ? 'selected' : '';
                                        echo "<option value='" . $swimmer['swimmerID'] . "' $selected>" . 
                                            htmlspecialchars($swimmer['name']) . " (ID: " . $swimmer['swimmerID'] . ")</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div>
                                <label for="eventName">Event:</label>
                                <select name="eventName" required>
                                    <option value="">Select Event</option>
                                    <optgroup label="Freestyle">
                                        <?php
                                        $freestyleEvents = ["50y Freestyle", "100y Freestyle", "200y Freestyle", "500y Freestyle", "1000y Freestyle", "1650y Freestyle"];
                                        foreach ($freestyleEvents as $event) {
                                            $selected = ($event === $eventName) ? 'selected' : '';
                                            echo "<option value=\"$event\" $selected>$event</option>";
                                        }
                                        ?>
                                    </optgroup>
                                    <optgroup label="Backstroke">
                                        <?php
                                        $backstrokeEvents = ["50y Backstroke", "100y Backstroke", "200y Backstroke"];
                                        foreach ($backstrokeEvents as $event) {
                                            $selected = ($event === $eventName) ? 'selected' : '';
                                            echo "<option value=\"$event\" $selected>$event</option>";
                                        }
                                        ?>
                                    </optgroup>
                                    <optgroup label="Butterfly">
                                        <?php
                                        $butterflyEvents = ["50y Butterfly", "100y Butterfly", "200y Butterfly"];
                                        foreach ($butterflyEvents as $event) {
                                            $selected = ($event === $eventName) ? 'selected' : '';
                                            echo "<option value=\"$event\" $selected>$event</option>";
                                        }
                                        ?>
                                    </optgroup>
                                    <optgroup label="Breaststroke">
                                        <?php
                                        $breaststrokeEvents = ["50y Breaststroke", "100y Breaststroke", "200y Breaststroke"];
                                        foreach ($breaststrokeEvents as $event) {
                                            $selected = ($event === $eventName) ? 'selected' : '';
                                            echo "<option value=\"$event\" $selected>$event</option>";
                                        }
                                        ?>
                                    </optgroup>
                                    <optgroup label="IM">
                                        <?php
                                        $imEvents = ["100y IM", "200y IM", "400y IM"];
                                        foreach ($imEvents as $event) {
                                            $selected = ($event === $eventName) ? 'selected' : '';
                                            echo "<option value=\"$event\" $selected>$event</option>";
                                        }
                                        ?>
                                    </optgroup>
                                </select>
                            </div>
                            
                            <div>
                                <label for="time">Time (mm:ss:ms):</label>
                                <input type="text" name="time" value="<?= htmlspecialchars($formattedTime) ?>" placeholder="e.g., 01:23:45" required>
                                <span class="help">Format: minutes:seconds:milliseconds</span>
                            </div>
                            
                            <!-- Meet Selection Dropdown -->
                            <div>
                                <label for="meetSelector">Select Existing Meet (Optional):</label>
                                <select id="meetSelector" onchange="populateMeetData()">
                                    <option value="">-- Create New Meet --</option>
                                    <?php
                                    // Get all meets, ordered by most recent first
                                    $meetsResult = $conn->query("SELECT meetName, location, date FROM Meet ORDER BY date DESC, meetName");
                                    while ($meetRow = $meetsResult->fetch_assoc()) {
                                        $selected = ($meetRow['meetName'] === $meetName && $meetRow['date'] === $meetDate) ? 'selected' : '';
                                        $meetJson = htmlspecialchars(json_encode($meetRow));
                                        echo "<option value='$meetJson' $selected>" . 
                                            htmlspecialchars($meetRow['meetName']) . " - " . 
                                            htmlspecialchars($meetRow['date']) . " (" . 
                                            htmlspecialchars($meetRow['location']) . ")</option>";
                                    }
                                    ?>
                                </select>
                                <span class="help">Select an existing meet or create a new one</span>
                            </div>
                            
                            <div>
                                <label for="meetName">Meet Name:</label>
                                <input type="text" id="meetNameField" name="meetName" value="<?= htmlspecialchars($meetName) ?>" required>
                            </div>
                            
                            <div>
                                <label for="meetLocation">Meet Location:</label>
                                <input type="text" id="meetLocationField" name="meetLocation" value="<?= htmlspecialchars($row['meetLocation']) ?>" required>
                            </div>
                            
                            <div>
                                <label for="meetDate">Meet Date:</label>
                                <input type="date" id="meetDateField" name="meetDate" value="<?= htmlspecialchars($meetDate) ?>" required>
                            </div>
                            
                            <div style="margin-top: 15px;">
                                <button type="submit" class="button">Update Swim Record</button>
                                <a href="swim_management.php" class="button">Cancel</a>
                            </div>
                        </form>
                    </div>
                    
                    <script>
                    function populateMeetData() {
                        const meetSelector = document.getElementById('meetSelector');
                        const meetNameField = document.getElementById('meetNameField');
                        const meetLocationField = document.getElementById('meetLocationField');
                        const meetDateField = document.getElementById('meetDateField');
                        
                        if (meetSelector.value) {
                            const meetData = JSON.parse(meetSelector.value);
                            
                            if (meetNameField) meetNameField.value = meetData.meetName;
                            if (meetLocationField) meetLocationField.value = meetData.location;
                            if (meetDateField) meetDateField.value = meetData.date;
                        } else {
                            if (meetNameField) meetNameField.value = '';
                            if (meetLocationField) meetLocationField.value = '';
                            if (meetDateField) meetDateField.value = '';
                        }
                    }
                    </script>
                    <?php
                } else {
                    echo showMessage("Swim record not found", true);
                }
            }

            // Display Add Swim Form if action is 'add_form'
            if ($action === 'add_form') {
                ?>
                <div class="edit-form" style="margin: 20px 0; background: #f5f5f5; padding: 15px; border-radius: 5px;">
                    <h3>Add New Swim Record</h3>
                    
                    <form method="post" action="?action=add">
                        <div>
                            <label for="swimmerID">Swimmer:</label>
                            <select name="swimmerID" required>
                                <option value="">Select Swimmer</option>
                                <?php
                                // Get all swimmers
                                $swimmersResult = $conn->query("SELECT swimmerID, name FROM Swimmer ORDER BY name");
                                while ($swimmer = $swimmersResult->fetch_assoc()) {
                                    echo "<option value='" . $swimmer['swimmerID'] . "'>" . 
                                        htmlspecialchars($swimmer['name']) . " (ID: " . $swimmer['swimmerID'] . ")</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div>
                            <label for="eventName">Event:</label>
                            <select name="eventName" required>
                                <option value="">Select Event</option>
                                <optgroup label="Freestyle">
                                    <?php
                                    $freestyleEvents = ["50y Freestyle", "100y Freestyle", "200y Freestyle", "500y Freestyle", "1000y Freestyle", "1650y Freestyle"];
                                    foreach ($freestyleEvents as $event) {
                                        echo "<option value=\"$event\">$event</option>";
                                    }
                                    ?>
                                </optgroup>
                                <optgroup label="Backstroke">
                                    <?php
                                    $backstrokeEvents = ["50y Backstroke", "100y Backstroke", "200y Backstroke"];
                                    foreach ($backstrokeEvents as $event) {
                                        echo "<option value=\"$event\">$event</option>";
                                    }
                                    ?>
                                </optgroup>
                                <optgroup label="Butterfly">
                                    <?php
                                    $butterflyEvents = ["50y Butterfly", "100y Butterfly", "200y Butterfly"];
                                    foreach ($butterflyEvents as $event) {
                                        echo "<option value=\"$event\">$event</option>";
                                    }
                                    ?>
                                </optgroup>
                                <optgroup label="Breaststroke">
                                    <?php
                                    $breaststrokeEvents = ["50y Breaststroke", "100y Breaststroke", "200y Breaststroke"];
                                    foreach ($breaststrokeEvents as $event) {
                                        echo "<option value=\"$event\">$event</option>";
                                    }
                                    ?>
                                </optgroup>
                                <optgroup label="IM">
                                    <?php
                                    $imEvents = ["100y IM", "200y IM", "400y IM"];
                                    foreach ($imEvents as $event) {
                                        echo "<option value=\"$event\">$event</option>";
                                    }
                                    ?>
                                </optgroup>
                            </select>
                        </div>
                        
                        <div>
                            <label for="time">Time (mm:ss:ms):</label>
                            <input type="text" name="time" placeholder="e.g., 01:23:45" required>
                            <span class="help">Format: minutes:seconds:milliseconds</span>
                        </div>
                        
                        <!-- Meet Selection Dropdown -->
                        <div>
                            <label for="meetSelector">Select Existing Meet (Optional):</label>
                            <select id="meetSelector" onchange="populateMeetData()">
                                <option value="">-- Create New Meet --</option>
                                <?php
                                // Get all meets, ordered by most recent first
                                $meetsResult = $conn->query("SELECT meetName, location, date FROM Meet ORDER BY date DESC, meetName");
                                while ($meetRow = $meetsResult->fetch_assoc()) {
                                    $meetJson = htmlspecialchars(json_encode($meetRow));
                                    echo "<option value='$meetJson'>" . 
                                        htmlspecialchars($meetRow['meetName']) . " - " . 
                                        htmlspecialchars($meetRow['date']) . " (" . 
                                        htmlspecialchars($meetRow['location']) . ")</option>";
                                }
                                ?>
                            </select>
                            <span class="help">Select an existing meet or create a new one</span>
                        </div>
                        
                        <div>
                            <label for="meetName">Meet Name:</label>
                            <input type="text" id="meetNameField" name="meetName" required>
                        </div>
                        
                        <div>
                            <label for="meetLocation">Meet Location:</label>
                            <input type="text" id="meetLocationField" name="meetLocation" required>
                        </div>
                        
                        <div>
                            <label for="meetDate">Meet Date:</label>
                            <input type="date" id="meetDateField" name="meetDate" required>
                        </div>
                        
                        <div style="margin-top: 15px;">
                            <button type="submit" class="button">Add Swim Record</button>
                            <a href="swim_management.php" class="button">Cancel</a>
                        </div>
                    </form>
                </div>
                
                <script>
                function populateMeetData() {
                    const meetSelector = document.getElementById('meetSelector');
                    const meetNameField = document.getElementById('meetNameField');
                    const meetLocationField = document.getElementById('meetLocationField');
                    const meetDateField = document.getElementById('meetDateField');
                    
                    if (meetSelector.value) {
                        const meetData = JSON.parse(meetSelector.value);
                        
                        if (meetNameField) meetNameField.value = meetData.meetName;
                        if (meetLocationField) meetLocationField.value = meetData.location;
                        if (meetDateField) meetDateField.value = meetData.date;
                    } else {
                        if (meetNameField) meetNameField.value = '';
                        if (meetLocationField) meetLocationField.value = '';
                        if (meetDateField) meetDateField.value = '';
                    }
                }
                </script>
                <?php
            }

            // Display the swim record management UI if not in edit or add mode
            if ($action === 'list') {
            ?>

            <!-- Add Swim Time Button -->
            <div style="margin: 20px 0;">
                <a href="?action=add_form" class="button">Add New Swim Time</a>
            </div>

            <!-- Search Form -->
            <div style="margin: 20px 0; background: #f5f5f5; padding: 15px; border-radius: 5px;">
                <h3>Search Swim Records</h3>
                <form method="get">
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <div>
                            <label for="searchSwimmer">Swimmer:</label>
                            <input type="text" name="searchSwimmer" id="searchSwimmer" value="<?= htmlspecialchars($_GET['searchSwimmer'] ?? '') ?>">
                        </div>
                        <div>
                            <label for="searchEvent">Event:</label>
                            <select name="searchEvent" id="searchEvent">
                                <option value="">All Events</option>
                                <optgroup label="Freestyle">
                                    <?php
                                    $freestyleEvents = ["50y Freestyle", "100y Freestyle", "200y Freestyle", "500y Freestyle", "1000y Freestyle", "1650y Freestyle"];
                                    foreach ($freestyleEvents as $event) {
                                        $selected = (isset($_GET['searchEvent']) && $_GET['searchEvent'] === $event) ? 'selected' : '';
                                        echo "<option value=\"$event\" $selected>$event</option>";
                                    }
                                    ?>
                                </optgroup>
                                <optgroup label="Backstroke">
                                    <?php
                                    $backstrokeEvents = ["50y Backstroke", "100y Backstroke", "200y Backstroke"];
                                    foreach ($backstrokeEvents as $event) {
                                        $selected = (isset($_GET['searchEvent']) && $_GET['searchEvent'] === $event) ? 'selected' : '';
                                        echo "<option value=\"$event\" $selected>$event</option>";
                                    }
                                    ?>
                                </optgroup>
                                <optgroup label="Butterfly">
                                    <?php
                                    $butterflyEvents = ["50y Butterfly", "100y Butterfly", "200y Butterfly"];
                                    foreach ($butterflyEvents as $event) {
                                        $selected = (isset($_GET['searchEvent']) && $_GET['searchEvent'] === $event) ? 'selected' : '';
                                        echo "<option value=\"$event\" $selected>$event</option>";
                                    }
                                    ?>
                                </optgroup>
                                <optgroup label="Breaststroke">
                                    <?php
                                    $breaststrokeEvents = ["50y Breaststroke", "100y Breaststroke", "200y Breaststroke"];
                                    foreach ($breaststrokeEvents as $event) {
                                        $selected = (isset($_GET['searchEvent']) && $_GET['searchEvent'] === $event) ? 'selected' : '';
                                        echo "<option value=\"$event\" $selected>$event</option>";
                                    }
                                    ?>
                                </optgroup>
                                <optgroup label="IM">
                                    <?php
                                    $imEvents = ["100y IM", "200y IM", "400y IM"];
                                    foreach ($imEvents as $event) {
                                        $selected = (isset($_GET['searchEvent']) && $_GET['searchEvent'] === $event) ? 'selected' : '';
                                        echo "<option value=\"$event\" $selected>$event</option>";
                                    }
                                    ?>
                                </optgroup>
                            </select>
                        </div>
                        <div>
                            <label for="searchMeet">Meet:</label>
                            <input type="text" name="searchMeet" id="searchMeet" value="<?= htmlspecialchars($_GET['searchMeet'] ?? '') ?>">
                        </div>
                        <div>
                            <label for="searchStartDate">Start Date:</label>
                            <input type="date" name="searchStartDate" id="searchStartDate" value="<?= htmlspecialchars($_GET['searchStartDate'] ?? '') ?>">
                        </div>
                        <div>
                            <label for="searchEndDate">End Date:</label>
                            <input type="date" name="searchEndDate" id="searchEndDate" value="<?= htmlspecialchars($_GET['searchEndDate'] ?? '') ?>">
                        </div>
                        <div>
                            <label style="visibility: hidden;">Search</label>
                            <button type="submit" class="button">Search</button>
                            <a href="swim_management.php" class="button">Reset</a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Swim Records Table -->
            <div class="table-container">
                <h3>Swim Records</h3>

                <?php
                // Build search query based on filters
                $whereConditions = [];
                $params = [];
                $types = '';

                if (!empty($_GET['searchSwimmer'])) {
                    $whereConditions[] = "sw.name LIKE ?";
                    $params[] = '%' . $_GET['searchSwimmer'] . '%';
                    $types .= 's';
                }

                if (!empty($_GET['searchEvent'])) {
                    $whereConditions[] = "s.eventName = ?";
                    $params[] = $_GET['searchEvent'];
                    $types .= 's';
                }

                if (!empty($_GET['searchMeet'])) {
                    $whereConditions[] = "s.meetName LIKE ?";
                    $params[] = '%' . $_GET['searchMeet'] . '%';
                    $types .= 's';
                }

                if (!empty($_GET['searchStartDate'])) {
                    $whereConditions[] = "s.meetDate >= ?";
                    $params[] = $_GET['searchStartDate'];
                    $types .= 's';
                }

                if (!empty($_GET['searchEndDate'])) {
                    $whereConditions[] = "s.meetDate <= ?";
                    $params[] = $_GET['searchEndDate'];
                    $types .= 's';
                }

                $query = "SELECT s.*, sw.name AS swimmerName, m.location AS meetLocation
                         FROM Swim s
                         JOIN Swimmer sw ON s.swimmerID = sw.swimmerID
                         JOIN Meet m ON s.meetName = m.meetName AND s.meetDate = m.date";
                
                if (!empty($whereConditions)) {
                    $query .= " WHERE " . implode(" AND ", $whereConditions);
                }
                
                $query .= " ORDER BY s.meetDate DESC, s.eventName, s.time ASC";
                
                // Add limit for pagination
                $recordsPerPage = 50;
                $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
                $offset = ($page - 1) * $recordsPerPage;
                
                $countQuery = str_replace("SELECT s.*, sw.name AS swimmerName, m.location AS meetLocation", "SELECT COUNT(*) as count", $query);
                $limitedQuery = $query . " LIMIT $offset, $recordsPerPage";

                // Get total count for pagination
                if (empty($params)) {
                    $countResult = $conn->query($countQuery);
                    $countRow = $countResult->fetch_assoc();
                    $totalRecords = $countRow['count'];
                    
                    $result = $conn->query($limitedQuery);
                } else {
                    $countStmt = $conn->prepare($countQuery);
                    $countStmt->bind_param($types, ...$params);
                    $countStmt->execute();
                    $countResult = $countStmt->get_result();
                    $countRow = $countResult->fetch_assoc();
                    $totalRecords = $countRow['count'];
                    
                    $stmt = $conn->prepare($limitedQuery);
                    $stmt->bind_param($types, ...$params);
                    $stmt->execute();
                    $result = $stmt->get_result();
                }
                
                $totalPages = ceil($totalRecords / $recordsPerPage);

                if ($result && $result->num_rows > 0) {
                    echo "<p>Showing " . ($offset + 1) . " to " . min($offset + $recordsPerPage, $totalRecords) . " of $totalRecords records</p>";
                    
                    echo "<table>";
                    echo "<tr>
                            <th>Swimmer</th>
                            <th>Event</th>
                            <th>Time</th>
                            <th>Meet</th>
                            <th>Location</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>";

                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['swimmerName']) . " (ID: " . $row['swimmerID'] . ")</td>";
                        echo "<td>" . htmlspecialchars($row['eventName']) . "</td>";
                        echo "<td>" . secondsToTime($row['time']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['meetName']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['meetLocation']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['meetDate']) . "</td>";
                        echo "<td>
                                <a href='?action=edit_form&swimmerID=" . $row['swimmerID'] . "&eventName=" . urlencode($row['eventName']) . "&meetName=" . urlencode($row['meetName']) . "&meetDate=" . urlencode($row['meetDate']) . "' class='button'>Edit</a>
                                <a href='?action=delete&swimmerID=" . $row['swimmerID'] . "&eventName=" . urlencode($row['eventName']) . "&meetName=" . urlencode($row['meetName']) . "&meetDate=" . urlencode($row['meetDate']) . "' class='button' onclick='return confirm(\"Are you sure you want to delete this swim record?\")'>Delete</a>
                            </td>";
                        echo "</tr>";
                    }

                    echo "</table>";
                    
                    // Pagination controls
                    if ($totalPages > 1) {
                        echo "<div class='pagination'>";
                        
                        // Previous page link
                        if ($page > 1) {
                            echo "<a href='?" . http_build_query(array_merge($_GET, ['page' => $page - 1])) . "' class='button'>&laquo; Previous</a>";
                        } else {
                            echo "<span class='button disabled'>&laquo; Previous</span>";
                        }
                        
                        // Page numbers
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        
                        for ($i = $startPage; $i <= $endPage; $i++) {
                            if ($i == $page) {
                                echo "<span class='button current'>$i</span>";
                            } else {
                                echo "<a href='?" . http_build_query(array_merge($_GET, ['page' => $i])) . "' class='button'>$i</a>";
                            }
                        }
                        
                        // Next page link
                        if ($page < $totalPages) {
                            echo "<a href='?" . http_build_query(array_merge($_GET, ['page' => $page + 1])) . "' class='button'>Next &raquo;</a>";
                        } else {
                            echo "<span class='button disabled'>Next &raquo;</span>";
                        }
                        
                        echo "</div>";
                    }
                } else {
                    echo "<p>No swim records found matching your criteria</p>";
                }
                ?>
            </div>

            <style>
            .pagination {
                margin: 20px 0;
                display: flex;
                justify-content: center;
                gap: 5px;
            }
            
            .pagination .button {
                padding: 5px 10px;
                text-decoration: none;
                border: 1px solid #ddd;
                color: #00796b;
                background-color: white;
            }
            
            .pagination .button:hover {
                background-color: #e0f7fa;
            }
            
            .pagination .current {
                background-color: #00796b;
                color: white;
                border-color: #00796b;
            }
            
            .pagination .disabled {
                color: #aaa;
                cursor: not-allowed;
            }
            
            .message.warning {
                background-color: #fff3cd;
                border-color: #ffecb5;
                color: #856404;
            }
            </style>

            <?php 
            }
            ?>

            <p><a href="home.php">Back to Home</a></p>

            <?php include 'includes/footer.php'; ?>
        </div>
    </div>
</body>