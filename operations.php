<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Check if user is admin
if (!isAdmin()) {
    redirect('home.php', 'Unauthorized access');
}

$action = $_GET['action'] ?? 'list';
$entity = $_GET['entity'] ?? 'swimmer';
$user = getCurrentUser();

include 'includes/header.php';
?>

<body>
    <div class='main'>
        <div class='container'>

            <h1>Admin: <?= ucfirst($action) ?> <?= ucfirst($entity) ?></h1>

            <?php
            // Show message if any
            if (isset($_SESSION['message'])) {
                echo showMessage($_SESSION['message']);
                unset($_SESSION['message']);
            }

            // Handle different entity-action combinations
            switch ("$entity:$action") {
                case 'admin:insert':
                    // Insert admin logic (from insert_admin.php)
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $name = sanitize($_POST['name']);
                        $role = sanitize($_POST['role']);
                        
                        // Use prepared statement
                        $stmt = $conn->prepare("INSERT INTO Admin (name, role) VALUES (?, ?)");
                        $stmt->bind_param('ss', $name, $role);
                        
                        if ($stmt->execute()) {
                            $adminID = $conn->insert_id;
                            echo showMessage("Admin added successfully. New Admin ID: $adminID");
                            // Clear form by redirection
                            echo "<script>
                                setTimeout(function(){
                                    window.location.href = 'operations.php?action=insert&entity=admin&success=true';
                                }, 2000);
                            </script>";
                        } else {
                            echo showMessage("Error adding admin: " . $stmt->error, true);
                        }
                    }
                    
                    // Admin insert form
                    ?>
                    <form method="post">
                        <div>
                            <label for="name">Name:</label>
                            <input type="text" name="name" required>
                        </div>
                        <div>
                            <label for="role">Role:</label>
                            <input type="text" name="role" required>
                        </div>
                        <button type="submit">Add Admin</button>
                    </form>
                    <?php
                    break;
                    
                case 'admin:search':
                    // Search admin logic (from search_admin.php)
                    ?>
                    <form method="post">
                        <div>
                            <label for="searchQuery">Search:</label>
                            <input type="text" name="searchQuery" placeholder="Enter admin name">
                        </div>
                        <button type="submit">Search</button>
                    </form>
                    <?php
                    
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $searchQuery = sanitize($_POST['searchQuery'] ?? '');
                        $searchQuery = "%$searchQuery%"; // Add wildcards
                        
                        $stmt = $conn->prepare("SELECT * FROM Admin WHERE name LIKE ?");
                        $stmt->bind_param('s', $searchQuery);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        if ($result->num_rows > 0) {
                            echo "<h2>Results</h2>";
                            echo "<table>";
                            echo "<tr><th>ID</th><th>Name</th><th>Role</th></tr>";
                            
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row['adminID']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['role']) . "</td>";
                                echo "</tr>";
                            }
                            
                            echo "</table>";
                        } else {
                            echo showMessage("No results found");
                        }
                    }
                    break;
                    
                case 'admin:delete':
                    // Delete admin logic (from delete_admin.php)
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $adminID = sanitize($_POST['adminID']);
                        
                        $stmt = $conn->prepare("DELETE FROM Admin WHERE adminID = ?");
                        $stmt->bind_param('i', $adminID);
                        
                        if ($stmt->execute()) {
                            echo showMessage("Admin deleted successfully");
                            // Redirect after success
                            echo "<script>
                                setTimeout(function(){
                                    window.location.href = 'operations.php?action=delete&entity=admin&success=true';
                                }, 2000);
                            </script>";
                        } else {
                            echo showMessage("Error deleting admin: " . $stmt->error, true);
                        }
                    }
                    
                    // Admin delete form
                    ?>
                    <form method="post" onsubmit="return confirmDelete('Are you sure you want to delete this admin?')">
                        <div>
                            <label for="adminID">Admin ID:</label>
                            <input type="number" name="adminID" required>
                        </div>
                        <button type="submit">Delete Admin</button>
                    </form>
                    <?php
                    break;
                    
                case 'swimmer:insert':
                    // Insert swimmer logic (from insert_swimmer.php)
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $name = sanitize($_POST['name']);
                        $powerIndex = sanitize($_POST['powerIndex']);
                        $gender = sanitize($_POST['gender']);
                        $hometown = sanitize($_POST['hometown']);
                        $team = sanitize($_POST['team']);
                        
                        $stmt = $conn->prepare("INSERT INTO Swimmer (name, powerIndex, gender, hometown, team) VALUES (?, ?, ?, ?, ?)");
                        $stmt->bind_param('sisss', $name, $powerIndex, $gender, $hometown, $team);
                        
                        if ($stmt->execute()) {
                            $swimmerID = $conn->insert_id;
                            echo showMessage("Swimmer added successfully. New Swimmer ID: $swimmerID");
                            
                            // Ask if user wants to add swim times
                            echo "<div class='message'>";
                            echo "<p>Would you like to add swim times for this swimmer?</p>";
                            echo "<a href='operations.php?action=insert&entity=swim&swimmer=$swimmerID' class='button'>Yes, add swim times</a> ";
                            echo "<a href='operations.php?action=insert&entity=swimmer&success=true' class='button'>No, add another swimmer</a> ";
                            echo "<a href='../home.php' class='button'>Return to Home</a>"; // Add this line
                            echo "</div>";
                        } else {
                            echo showMessage("Error adding swimmer: " . $stmt->error, true);
                        }
                    } else {
                        // Swimmer insert form
                        ?>
                        <form method="post">
                            <div>
                                <label for="name">Name:</label>
                                <input type="text" name="name" required>
                            </div>
                            <div>
                                <label for="powerIndex">Power Index:</label>
                                <input type="number" name="powerIndex" required>
                            </div>
                            <div>
                                <label for="gender">Gender:</label>
                                <input type="text" name="gender" maxlength="1" required>
                            </div>
                            <div>
                                <label for="hometown">Hometown:</label>
                                <input type="text" name="hometown" required>
                            </div>
                            <div>
                                <label for="team">Team:</label>
                                <input type="text" name="team" required>
                            </div>
                            <button type="submit">Add Swimmer</button>
                        </form>
                        <?php
                    }
                    break;
                    
                case 'swimmer:delete':
                    // Delete swimmer logic (from delete_swimmer.php)
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $swimmerID = sanitize($_POST['swimmerID']);
                        
                        // Check if swimmer has swim records
                        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM Swim WHERE swimmerID = ?");
                        $stmt->bind_param('i', $swimmerID);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $row = $result->fetch_assoc();
                        
                        if ($row['count'] > 0) {
                            // Swimmer has swim records
                            $deleteRecords = isset($_POST['deleteRecords']) && $_POST['deleteRecords'] === 'yes';
                            
                            if ($deleteRecords) {
                                // Delete swim records first
                                $stmt = $conn->prepare("DELETE FROM Swim WHERE swimmerID = ?");
                                $stmt->bind_param('i', $swimmerID);
                                
                                if ($stmt->execute()) {
                                    // Now delete the swimmer
                                    $stmt = $conn->prepare("DELETE FROM Swimmer WHERE swimmerID = ?");
                                    $stmt->bind_param('i', $swimmerID);
                                    
                                    if ($stmt->execute()) {
                                        echo showMessage("Swimmer and associated swim records deleted successfully");
                                    } else {
                                        echo showMessage("Error deleting swimmer: " . $stmt->error, true);
                                    }
                                } else {
                                    echo showMessage("Error deleting swim records: " . $stmt->error, true);
                                }
                            } else {
                                // Show warning and confirmation form
                                echo "<div class='message error'>";
                                echo "<p>This swimmer has {$row['count']} swim records. These must be deleted first.</p>";
                                echo "<form method='post'>";
                                echo "<input type='hidden' name='swimmerID' value='$swimmerID'>";
                                echo "<input type='hidden' name='deleteRecords' value='yes'>";
                                echo "<button type='submit' class='button'>Yes, delete swimmer and all their records</button> ";
                                echo "</form>";
                                echo "</div>";
                                
                                // Still show the deletion form
                                ?>
                                <form method="post">
                                    <div>
                                        <label for="swimmerID">Swimmer ID:</label>
                                        <input type="number" name="swimmerID" required>
                                    </div>
                                    <button type="submit" onsubmit="return confirmDelete('Are you sure you want to delete this swimmer?')">Delete Swimmer</button>
                                </form>
                                <?php
                            }
                        } else {
                            // No swim records, proceed with deleting the swimmer
                            $stmt = $conn->prepare("DELETE FROM Swimmer WHERE swimmerID = ?");
                            $stmt->bind_param('i', $swimmerID);
                            
                            if ($stmt->execute()) {
                                echo showMessage("Swimmer deleted successfully");
                                // Redirect after success
                                echo "<script>
                                    setTimeout(function(){
                                        window.location.href = 'operations.php?action=delete&entity=swimmer&success=true';
                                    }, 2000);
                                </script>";
                            } else {
                                echo showMessage("Error deleting swimmer: " . $stmt->error, true);
                            }
                        }
                    } else {
                        // Swimmer delete form
                        ?>
                        <form method="post" onsubmit="return confirmDelete('Are you sure you want to delete this swimmer?')">
                            <div>
                                <label for="swimmerID">Swimmer ID:</label>
                                <input type="number" name="swimmerID" required>
                            </div>
                            <button type="submit">Delete Swimmer</button>
                        </form>
                        <?php
                    }
                    break;
                    
                // NEW CASE: Add swim time for swimmers
                case 'swim:insert':
                    // Get parameters from URL
                    $swimmerID = isset($_GET['swimmer']) ? intval($_GET['swimmer']) : 0;
                    $meetName = isset($_GET['meet']) ? sanitize($_GET['meet']) : '';
                    $meetDate = isset($_GET['date']) ? sanitize($_GET['date']) : '';
                    
                    // If swimmerID is provided, pre-select that swimmer
                    if ($swimmerID > 0) {
                        // Get swimmer name
                        $stmt = $conn->prepare("SELECT name FROM Swimmer WHERE swimmerID = ?");
                        $stmt->bind_param('i', $swimmerID);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        if ($row = $result->fetch_assoc()) {
                            $swimmerName = htmlspecialchars($row['name']);
                        } else {
                            $swimmerName = "Unknown Swimmer";
                        }
                    }
                    
                    // Process form submission
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $swimmerID = sanitize($_POST['swimmerID']);
                        $eventName = sanitize($_POST['eventName']);
                        $meetName = sanitize($_POST['meetName']);
                        $meetDate = sanitize($_POST['meetDate']);
                        
                        // IMPROVED DATE VALIDATION - Fix for date truncation error
                        // Ensure the date is properly formatted for MySQL
                        $meetDate = trim($meetDate); // Remove any whitespace
                        
                        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $meetDate)) {
                            // Try to convert to correct format if it's in another valid date format
                            $timestamp = strtotime($meetDate);
                            if ($timestamp === false) {
                                echo showMessage("Invalid date format. Please use YYYY-MM-DD format.", true);
                                exit;
                            }
                            $meetDate = date('Y-m-d', $timestamp);
                        }
                        
                        // Validate date range for MySQL (1000-01-01 to 9999-12-31)
                        $year = (int)substr($meetDate, 0, 4);
                        if ($year < 1000 || $year > 9999) {
                            echo showMessage("Date year must be between 1000 and 9999.", true);
                            exit;
                        }
                        
                        $timeStr = sanitize($_POST['time']);
                        
                        // Convert time to seconds for DB storage
                        $timeInSeconds = timeToSeconds($timeStr);
                        
                        // Check if meet exists, if not create it
                        $stmt = $conn->prepare("SELECT * FROM Meet WHERE meetName = ? AND date = ?");
                        $stmt->bind_param('ss', $meetName, $meetDate);
                        $stmt->execute();
                        
                        if ($stmt->get_result()->num_rows === 0) {
                            // Default location if not provided
                            $location = sanitize($_POST['meetLocation'] ?? 'Unknown');
                            
                            // Insert new meet
                            $stmt = $conn->prepare("INSERT INTO Meet (meetName, location, date) VALUES (?, ?, ?)");
                            $stmt->bind_param('sss', $meetName, $location, $meetDate);
                            $stmt->execute();
                        }
                        
                        // Insert swim record
                        $stmt = $conn->prepare("INSERT INTO Swim (eventName, meetName, meetDate, swimmerID, time) VALUES (?, ?, ?, ?, ?)");
                        $stmt->bind_param('sssid', $eventName, $meetName, $meetDate, $swimmerID, $timeInSeconds);
                        
                        if ($stmt->execute()) {
                            echo showMessage("Swim record added successfully");
                            
                            // Ask if user wants to add more swim times for this swimmer
                            echo "<div class='message'>";
                            echo "<p>Would you like to add another swim time?</p>";
                            echo "<a href='operations.php?action=insert&entity=swim&swimmer=$swimmerID' class='button'>Yes, for same swimmer</a> ";
                            echo "<a href='operations.php?action=insert&entity=swim' class='button'>Yes, for different swimmer</a> ";
                            echo "<a href='swim_management.php' class='button'>No, view all swims</a>"; // FIX: Changed link to swim_management.php
                            echo "</div>";
                        } else {
                            echo showMessage("Error adding swim record: " . $stmt->error, true);
                        }
                    } else {
                        // Display form for adding swim times
                        ?>
                        <form method="post">
                            <div>
                                <label for="swimmerID">Swimmer:</label>
                                <?php if ($swimmerID > 0): ?>
                                    <input type="hidden" name="swimmerID" value="<?= $swimmerID ?>">
                                    <p><strong><?= $swimmerName ?> (ID: <?= $swimmerID ?>)</strong></p>
                                <?php else: ?>
                                    <select name="swimmerID" required>
                                        <option value="">Select Swimmer</option>
                                        <?php
                                        // Get all swimmers
                                        $result = $conn->query("SELECT swimmerID, name FROM Swimmer ORDER BY name");
                                        while ($row = $result->fetch_assoc()) {
                                            echo "<option value='".htmlspecialchars($row['swimmerID'])."'>".
                                                htmlspecialchars($row['name'])." (ID: ".htmlspecialchars($row['swimmerID']).")</option>";
                                        }
                                        ?>
                                    </select>
                                <?php endif; ?>
                            </div>
                            
                            <div>
                                <label for="eventName">Event:</label>
                                <select name="eventName" required>
                                    <option value="">Select Event</option>
                                    <optgroup label="Freestyle">
                                        <option value="50y Freestyle">50y Freestyle</option>
                                        <option value="100y Freestyle">100y Freestyle</option>
                                        <option value="200y Freestyle">200y Freestyle</option>
                                        <option value="500y Freestyle">500y Freestyle</option>
                                        <option value="1000y Freestyle">1000y Freestyle</option>
                                        <option value="1650y Freestyle">1650y Freestyle</option>
                                    </optgroup>
                                    <optgroup label="Backstroke">
                                        <option value="50y Backstroke">50y Backstroke</option>
                                        <option value="100y Backstroke">100y Backstroke</option>
                                        <option value="200y Backstroke">200y Backstroke</option>
                                    </optgroup>
                                    <optgroup label="Butterfly">
                                        <option value="50y Butterfly">50y Butterfly</option>
                                        <option value="100y Butterfly">100y Butterfly</option>
                                        <option value="200y Butterfly">200y Butterfly</option>
                                    </optgroup>
                                    <optgroup label="Breaststroke">
                                        <option value="50y Breaststroke">50y Breaststroke</option>
                                        <option value="100y Breaststroke">100y Breaststroke</option>
                                        <option value="200y Breaststroke">200y Breaststroke</option>
                                    </optgroup>
                                    <optgroup label="IM">
                                        <option value="100y IM">100y IM</option>
                                        <option value="200y IM">200y IM</option>
                                        <option value="400y IM">400y IM</option>
                                    </optgroup>
                                </select>
                            </div>
                            
                            <div>
                                <label for="time">Time (mm:ss:ms):</label>
                                <input type="text" name="time" placeholder="e.g., 01:23:45" required>
                                <span class="help">Format: minutes:seconds:milliseconds</span>
                            </div>
                            
                            <!-- NEW: Meet selection and data auto-population -->
                            <div>
                                <label for="meetSelector">Select Existing Meet (Optional):</label>
                                <select id="meetSelector" onchange="populateMeetData()">
                                    <option value="">-- Create New Meet --</option>
                                    <?php
                                    // Get all meets, ordered by most recent first
                                    $meetsResult = $conn->query("SELECT meetName, location, date FROM Meet ORDER BY date DESC, meetName");
                                    while ($meetRow = $meetsResult->fetch_assoc()) {
                                        echo "<option value='".htmlspecialchars(json_encode($meetRow))."'>" . 
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
                                <?php if (!empty($meetName)): ?>
                                    <input type="hidden" name="meetName" value="<?= htmlspecialchars($meetName) ?>">
                                    <p><strong><?= htmlspecialchars($meetName) ?></strong></p>
                                <?php else: ?>
                                    <input type="text" id="meetNameField" name="meetName" required>
                                <?php endif; ?>
                            </div>
                            
                            <div>
                                <label for="meetLocation">Meet Location:</label>
                                <input type="text" id="meetLocationField" name="meetLocation" required>
                            </div>
                            
                            <div>
                                <label for="meetDate">Meet Date:</label>
                                <?php if (!empty($meetDate)): ?>
                                    <input type="hidden" name="meetDate" value="<?= htmlspecialchars($meetDate) ?>">
                                    <p><strong><?= htmlspecialchars($meetDate) ?></strong></p>
                                <?php else: ?>
                                    <input type="date" id="meetDateField" name="meetDate" required>
                                <?php endif; ?>
                            </div>
                            
                            <button type="submit">Add Swim Time</button>
                        </form>
                        
                        <!-- JavaScript to auto-populate meet data -->
                        <script>
                        function populateMeetData() {
                            const meetSelector = document.getElementById('meetSelector');
                            const meetNameField = document.getElementById('meetNameField');
                            const meetLocationField = document.getElementById('meetLocationField');
                            const meetDateField = document.getElementById('meetDateField');
                            
                            // If a meet is selected (not the default "Create New Meet" option)
                            if (meetSelector.value) {
                                const meetData = JSON.parse(meetSelector.value);
                                
                                // Auto-populate fields if they exist
                                if (meetNameField) meetNameField.value = meetData.meetName;
                                if (meetLocationField) meetLocationField.value = meetData.location;
                                if (meetDateField) meetDateField.value = meetData.date;
                            } else {
                                // Clear fields if default option is selected
                                if (meetNameField) meetNameField.value = '';
                                if (meetLocationField) meetLocationField.value = '';
                                if (meetDateField) meetDateField.value = '';
                            }
                        }
                        </script>
                        <?php
                    }
                    break;
                    

                    ?>
                    <form method="post">
                        <div>
                            <label for="searchType">Search By:</label>
                            <select name="searchType" id="searchType" onchange="showAppropriateFields()">
                                <option value="all">List All Swimmers</option>
                                <option value="name">Name</option>
                                <option value="team">Team</option>
                                <option value="hometown">Hometown</option>
                                <option value="id">Swimmer ID</option>
                            </select>
                        </div>
                        
                        <div id="allFields" style="display:none">
                            <p>Click Search to see all swimmers</p>
                        </div>
                        
                        <div id="nameField">
                            <label for="nameQuery">Name:</label>
                            <input type="text" name="nameQuery" placeholder="Enter swimmer name">
                        </div>
                        
                        <div id="teamField" style="display:none">
                            <label for="teamQuery">Team:</label>
                            <input type="text" name="teamQuery" placeholder="Enter team name">
                        </div>
                        
                        <div id="hometownField" style="display:none">
                            <label for="hometownQuery">Hometown:</label>
                            <input type="text" name="hometownQuery" placeholder="Enter hometown">
                        </div>
                        
                        <div id="idField" style="display:none">
                            <label for="idQuery">Swimmer ID:</label>
                            <input type="number" name="idQuery" placeholder="Enter swimmer ID">
                        </div>
                        
                        <button type="submit">Search</button>
                    </form>
                    
                    <script>
                    function showAppropriateFields() {
                        const searchType = document.getElementById('searchType').value;
                        
                        // Hide all fields
                        document.getElementById('nameField').style.display = 'none';
                        document.getElementById('teamField').style.display = 'none';
                        document.getElementById('hometownField').style.display = 'none';
                        document.getElementById('idField').style.display = 'none';
                        document.getElementById('allFields').style.display = 'none';
                        
                        // Show selected field
                        if (searchType === 'all') {
                            document.getElementById('allFields').style.display = 'block';
                        } else {
                            document.getElementById(searchType + 'Field').style.display = 'block';
                        }
                    }
                    
                    // Initialize on page load
                    document.addEventListener('DOMContentLoaded', showAppropriateFields);
                    </script>
                    
                    <?php
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $searchType = sanitize($_POST['searchType'] ?? 'name');
                        $query = '';
                        $param = '';
                        
                        // Build query based on search type
                        switch ($searchType) {
                            case 'all':
                                $query = "SELECT * FROM Swimmer ORDER BY name";
                                break;
                                
                            case 'name':
                                $nameQuery = sanitize($_POST['nameQuery'] ?? '');
                                if (!empty($nameQuery)) {
                                    $query = "SELECT * FROM Swimmer WHERE name LIKE ?";
                                    $param = "%$nameQuery%";
                                }
                                break;
                                
                            case 'team':
                                $teamQuery = sanitize($_POST['teamQuery'] ?? '');
                                if (!empty($teamQuery)) {
                                    $query = "SELECT * FROM Swimmer WHERE team LIKE ?";
                                    $param = "%$teamQuery%";
                                }
                                break;
                                
                            case 'hometown':
                                $hometownQuery = sanitize($_POST['hometownQuery'] ?? '');
                                if (!empty($hometownQuery)) {
                                    $query = "SELECT * FROM Swimmer WHERE hometown LIKE ?";
                                    $param = "%$hometownQuery%";
                                }
                                break;
                                
                            case 'id':
                                $idQuery = sanitize($_POST['idQuery'] ?? '');
                                if (!empty($idQuery)) {
                                    $query = "SELECT * FROM Swimmer WHERE swimmerID = ?";
                                    $param = $idQuery;
                                }
                                break;
                        }
                        
                        if (!empty($query)) {
                            if ($searchType === 'all') {
                                $result = $conn->query($query);
                            } else {
                                if (empty($param)) {
                                    echo showMessage("Please enter search criteria", true);
                                    break;
                                }
                                $stmt = $conn->prepare($query);
                                $stmt->bind_param('s', $param);
                                $stmt->execute();
                                $result = $stmt->get_result();
                            }
                            
                            if (isset($result) && $result->num_rows > 0) {
                                echo "<h2>Results</h2>";
                                echo "<table>";
                                echo "<tr><th>ID</th><th>Name</th><th>Gender</th><th>Hometown</th><th>Team</th><th>Power Index</th><th>Actions</th></tr>";
                                
                                while ($row = $result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row['swimmerID']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['gender']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['hometown']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['team']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['powerIndex']) . "</td>";
                                    echo "<td>
                                        <a href='operations.php?action=insert&entity=swim&swimmer=" . $row['swimmerID'] . "' class='button'>Add Times</a>
                                    </td>";
                                    echo "</tr>";
                                }
                                
                                echo "</table>";
                            } else {
                                echo showMessage("No swimmers found matching your criteria");
                            }
                        } else if ($searchType !== 'all') {
                            echo showMessage("Please enter search criteria", true);
                        }
                    }
                    break;
                    
                default:
                    echo showMessage("Invalid action", true);
            }
            ?>

            <p><a href="home.php">Back to Home</a></p>

            <?php include 'includes/footer.php'; ?>
        </div>
    </div>
</body>