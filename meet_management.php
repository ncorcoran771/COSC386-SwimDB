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
            <h1>Meet Management</h1>

            <?php
            // Show message if any
            if (isset($_SESSION['message'])) {
                echo showMessage($_SESSION['message']);
                unset($_SESSION['message']);
            }

            // Handle delete action
            if ($action === 'delete') {
                // Get parameters either from URL or POST
                $meetName = isset($_REQUEST['name']) ? sanitize($_REQUEST['name']) : '';
                $meetDate = isset($_REQUEST['date']) ? sanitize($_REQUEST['date']) : '';
                $deleteRecords = isset($_REQUEST['deleteRecords']) && $_REQUEST['deleteRecords'] === 'yes';
                
                // Debug output
                echo "<div class='message'>Debug: Delete action triggered for meet: '$meetName' on '$meetDate'</div>";
                
                if (empty($meetName) || empty($meetDate)) {
                    echo showMessage("Error: Missing meet name or date for deletion", true);
                } else {
                    // Check if the meet exists
                    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM Meet WHERE meetName = ? AND date = ?");
                    $stmt->bind_param('ss', $meetName, $meetDate);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $row = $result->fetch_assoc();
                    
                    if ($row['count'] == 0) {
                        echo showMessage("Error: Meet not found", true);
                    } else {
                        // Check if meet has any swim records
                        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM Swim WHERE meetName = ? AND meetDate = ?");
                        $stmt->bind_param('ss', $meetName, $meetDate);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $row = $result->fetch_assoc();
                        
                        if ($row['count'] > 0 && !$deleteRecords) {
                            // Show confirmation form for meets with records
                            echo "<div class='message warning'>";
                            echo "<p>This meet has {$row['count']} swim records. These records must be deleted first.</p>";
                            echo "<p>Would you like to delete this meet and all its associated swim records?</p>";
                            echo "<div style='display: flex; gap: 10px;'>";
                            echo "<a href='?action=delete&name=" . urlencode($meetName) . "&date=" . urlencode($meetDate) . "&deleteRecords=yes' class='button' style='background-color: #ff6347;'>Yes, delete meet and all records</a>";
                            echo "<a href='meet_management.php' class='button'>Cancel</a>";
                            echo "</div>";
                            echo "</div>";
                        } else {
                            // Process deletion
                            $conn->begin_transaction();
                            
                            try {
                                if ($row['count'] > 0) {
                                    // Delete swim records first
                                    $stmt = $conn->prepare("DELETE FROM Swim WHERE meetName = ? AND meetDate = ?");
                                    $stmt->bind_param('ss', $meetName, $meetDate);
                                    if (!$stmt->execute()) {
                                        throw new Exception("Failed to delete swim records: " . $stmt->error);
                                    }
                                    echo "<div class='message'>Debug: Deleted {$stmt->affected_rows} swim records</div>";
                                }
                                
                                // Now delete the meet
                                $stmt = $conn->prepare("DELETE FROM Meet WHERE meetName = ? AND date = ?");
                                $stmt->bind_param('ss', $meetName, $meetDate);
                                if (!$stmt->execute()) {
                                    throw new Exception("Failed to delete meet: " . $stmt->error);
                                }
                                
                                if ($stmt->affected_rows > 0) {
                                    $conn->commit();
                                    echo showMessage("Meet successfully deleted");
                                } else {
                                    throw new Exception("No meet was deleted. Meet may no longer exist.");
                                }
                            } catch (Exception $e) {
                                $conn->rollback();
                                echo showMessage("Error: " . $e->getMessage(), true);
                            }
                        }
                    }
                }
            }

            // Handle add meet action
            if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $meetName = sanitize($_POST['meetName']);
                $location = sanitize($_POST['location']);
                $date = sanitize($_POST['date']);

                // First, check if the meet already exists
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM Meet WHERE meetName = ? AND date = ?");
                $stmt->bind_param('ss', $meetName, $date);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();

                if ($row['count'] > 0) {
                    echo showMessage("A meet with this name and date already exists.", true);
                } else {
                    $stmt = $conn->prepare("INSERT INTO Meet (meetName, location, date) VALUES (?, ?, ?)");
                    $stmt->bind_param('sss', $meetName, $location, $date);

                    if ($stmt->execute()) {
                        echo showMessage("Meet added successfully.");
                    } else {
                        echo showMessage("Error adding meet: " . $stmt->error, true);
                    }
                }
            }

            // Handle edit meet action
            if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $oldMeetName = sanitize($_POST['oldMeetName']);
                $oldDate = sanitize($_POST['oldDate']);
                $meetName = sanitize($_POST['meetName']);
                $location = sanitize($_POST['location']);
                $date = sanitize($_POST['date']);

                // Check if new name/date combination already exists (and is different from the old one)
                if (($oldMeetName != $meetName || $oldDate != $date) && 
                    !(empty($meetName) || empty($date))) {
                    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM Meet WHERE meetName = ? AND date = ?");
                    $stmt->bind_param('ss', $meetName, $date);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $row = $result->fetch_assoc();

                    if ($row['count'] > 0) {
                        echo showMessage("A meet with this name and date already exists.", true);
                        $action = 'list';  // Reset to list view
                    }
                }

                // If validation passed, proceed with the update
                if ($action === 'edit') {
                    $conn->begin_transaction();
                    
                    try {
                        // Update the meet information
                        $stmt = $conn->prepare("UPDATE Meet SET meetName = ?, location = ?, date = ? WHERE meetName = ? AND date = ?");
                        $stmt->bind_param('sssss', $meetName, $location, $date, $oldMeetName, $oldDate);
                        
                        if (!$stmt->execute()) {
                            throw new Exception("Failed to update meet: " . $stmt->error);
                        }
                        
                        // If meet name or date changed, update references in Swim table
                        if (($oldMeetName !== $meetName || $oldDate !== $date) && $stmt->affected_rows > 0) {
                            $stmt = $conn->prepare("UPDATE Swim SET meetName = ?, meetDate = ? WHERE meetName = ? AND meetDate = ?");
                            $stmt->bind_param('ssss', $meetName, $date, $oldMeetName, $oldDate);
                            
                            if (!$stmt->execute()) {
                                throw new Exception("Failed to update swim records: " . $stmt->error);
                            }
                            
                            echo "<div class='message'>Debug: Updated {$stmt->affected_rows} swim records</div>";
                        }
                        
                        $conn->commit();
                        echo showMessage("Meet updated successfully");
                    } catch (Exception $e) {
                        $conn->rollback();
                        echo showMessage("Error: " . $e->getMessage(), true);
                    }
                }
                
                // Reset action to list view
                $action = 'list';
            }

            // Show edit form if requested
            if ($action === 'edit_form' && isset($_GET['name']) && isset($_GET['date'])) {
                $meetName = sanitize($_GET['name']);
                $date = sanitize($_GET['date']);
                
                // Get meet data
                $stmt = $conn->prepare("SELECT * FROM Meet WHERE meetName = ? AND date = ?");
                $stmt->bind_param('ss', $meetName, $date);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($row = $result->fetch_assoc()) {
                    ?>
                    <div class="edit-form" style="margin: 20px 0; background: #f5f5f5; padding: 15px; border-radius: 5px;">
                        <h3>Edit Meet: <?= htmlspecialchars($meetName) ?> - <?= htmlspecialchars($date) ?></h3>
                        <form method="post" action="?action=edit">
                            <input type="hidden" name="oldMeetName" value="<?= htmlspecialchars($meetName) ?>">
                            <input type="hidden" name="oldDate" value="<?= htmlspecialchars($date) ?>">
                            
                            <div>
                                <label for="meetName">Meet Name:</label>
                                <input type="text" name="meetName" value="<?= htmlspecialchars($row['meetName']) ?>" required>
                            </div>
                            <div>
                                <label for="location">Location:</label>
                                <input type="text" name="location" value="<?= htmlspecialchars($row['location']) ?>" required>
                            </div>
                            <div>
                                <label for="date">Date:</label>
                                <input type="date" name="date" value="<?= htmlspecialchars($row['date']) ?>" required>
                            </div>
                            <div style="margin-top: 15px;">
                                <button type="submit" class="button">Update Meet</button>
                                <a href="meet_management.php" class="button">Cancel</a>
                            </div>
                        </form>
                    </div>
                    <?php
                } else {
                    echo showMessage("Meet not found", true);
                }
            }

            // Display the meet management UI if not in edit mode or delete confirmation
            if ($action !== 'edit_form' && 
                !($action === 'delete' && 
                  isset($_REQUEST['name']) && 
                  isset($_REQUEST['date']) && 
                  !isset($_REQUEST['deleteRecords']))) {
            ?>

            <!-- Add Meet Button and Toggle Form -->
            <div style="margin: 20px 0;">
                <button onclick="toggleAddForm()" class="button">Add New Meet</button>

                <div id="addMeetForm" style="display: none; margin-top: 15px; background: #f5f5f5; padding: 15px; border-radius: 5px;">
                    <h3>Add Meet</h3>
                    <form method="post" action="?action=add">
                        <div>
                            <label for="meetName">Meet Name:</label>
                            <input type="text" name="meetName" required>
                        </div>
                        <div>
                            <label for="location">Location:</label>
                            <input type="text" name="location" required>
                        </div>
                        <div>
                            <label for="date">Date:</label>
                            <input type="date" name="date" required>
                        </div>
                        <button type="submit" class="button">Add Meet</button>
                    </form>
                </div>
            </div>

            <!-- Search Form -->
            <div style="margin: 20px 0; background: #f5f5f5; padding: 15px; border-radius: 5px;">
                <h3>Search Meets</h3>
                <form method="get">
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <div>
                            <label for="searchName">Name:</label>
                            <input type="text" name="searchName" id="searchName" value="<?= htmlspecialchars($_GET['searchName'] ?? '') ?>">
                        </div>
                        <div>
                            <label for="searchLocation">Location:</label>
                            <input type="text" name="searchLocation" id="searchLocation" value="<?= htmlspecialchars($_GET['searchLocation'] ?? '') ?>">
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
                            <a href="meet_management.php" class="button">Reset</a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Meets Table -->
            <div class="table-container">
                <h3>Meets</h3>

                <?php
                // Build search query based on filters
                $whereConditions = [];
                $params = [];
                $types = '';

                if (!empty($_GET['searchName'])) {
                    $whereConditions[] = "meetName LIKE ?";
                    $params[] = '%' . $_GET['searchName'] . '%';
                    $types .= 's';
                }

                if (!empty($_GET['searchLocation'])) {
                    $whereConditions[] = "location LIKE ?";
                    $params[] = '%' . $_GET['searchLocation'] . '%';
                    $types .= 's';
                }

                if (!empty($_GET['searchStartDate'])) {
                    $whereConditions[] = "date >= ?";
                    $params[] = $_GET['searchStartDate'];
                    $types .= 's';
                }

                if (!empty($_GET['searchEndDate'])) {
                    $whereConditions[] = "date <= ?";
                    $params[] = $_GET['searchEndDate'];
                    $types .= 's';
                }

                $query = "SELECT m.*, (SELECT COUNT(*) FROM Swim sw WHERE sw.meetName = m.meetName AND sw.meetDate = m.date) as swimCount FROM Meet m";
                if (!empty($whereConditions)) {
                    $query .= " WHERE " . implode(" AND ", $whereConditions);
                }
                $query .= " ORDER BY date DESC, meetName";

                // Execute query
                if (empty($params)) {
                    $result = $conn->query($query);
                } else {
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param($types, ...$params);
                    $stmt->execute();
                    $result = $stmt->get_result();
                }

                if ($result && $result->num_rows > 0) {
                    echo "<table>";
                    echo "<tr>
                            <th>Meet Name</th>
                            <th>Location</th>
                            <th>Date</th>
                            <th>Swim Records</th>
                            <th>Actions</th>
                        </tr>";

                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['meetName']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['location']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['date']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['swimCount']) . "</td>";
                        echo "<td>
                                <a href='meet_profile.php?name=" . urlencode($row['meetName']) . "&date=" . urlencode($row['date']) . "' class='button'>View Profile</a>
                                <a href='operations.php?action=insert&entity=swim&meet=" . urlencode($row['meetName']) . "&date=" . urlencode($row['date']) . "' class='button'>Add Times</a>
                                <a href='?action=edit_form&name=" . urlencode($row['meetName']) . "&date=" . urlencode($row['date']) . "' class='button'>Edit</a>
                                <a href='?action=delete&name=" . urlencode($row['meetName']) . "&date=" . urlencode($row['date']) . "' class='button' onclick='return confirm(\"Are you sure you want to delete this meet?\")'>Delete</a>
                            </td>";
                        echo "</tr>";
                    }

                    echo "</table>";
                } else {
                    echo "<p>No meets found matching your criteria</p>";
                }
                ?>
            </div>

            <script>
            // Toggle add meet form
            function toggleAddForm() {
                const form = document.getElementById('addMeetForm');
                form.style.display = form.style.display === 'none' ? 'block' : 'none';
            }
            </script>

            <p><a href="home.php">Back to Home</a></p>

            <?php 
            }
            include 'includes/footer.php'; 
            ?>
        </div>
    </div>
</body>