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
            <h1>Conference Management</h1>

            <?php
            // Show message if any
            if (isset($_SESSION['message'])) {
                echo showMessage($_SESSION['message']);
                unset($_SESSION['message']);
            }

            // Handle delete action
            if ($action === 'delete') {
                // Get parameters either from URL or POST
                $name = isset($_REQUEST['name']) ? sanitize($_REQUEST['name']) : '';
                $state = isset($_REQUEST['state']) ? sanitize($_REQUEST['state']) : '';
                
                // Debug output
                echo "<div class='message'>Debug: Delete action triggered for conference: '$name' in state '$state'</div>";
                
                if (empty($name) || empty($state)) {
                    echo showMessage("Error: Missing conference name or state for deletion", true);
                } else {
                    // Check if the conference exists
                    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM Conference WHERE name = ? AND state = ?");
                    $stmt->bind_param('ss', $name, $state);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $row = $result->fetch_assoc();
                    
                    if ($row['count'] == 0) {
                        echo showMessage("Error: Conference not found", true);
                    } else {
                        // Check if conference is used by any teams
                        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM Team WHERE confName = ? AND confState = ?");
                        $stmt->bind_param('ss', $name, $state);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $row = $result->fetch_assoc();
                        
                        if ($row['count'] > 0) {
                            echo showMessage("Cannot delete conference: It is used by {$row['count']} teams. You must update those teams first.", true);
                        } else {
                            // Delete conference
                            $stmt = $conn->prepare("DELETE FROM Conference WHERE name = ? AND state = ?");
                            $stmt->bind_param('ss', $name, $state);
                            
                            if ($stmt->execute() && $stmt->affected_rows > 0) {
                                echo showMessage("Conference deleted successfully");
                            } else {
                                echo showMessage("Error deleting conference: " . $stmt->error, true);
                            }
                        }
                    }
                }
            }

            // Handle add conference action
            if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $name = sanitize($_POST['name']);
                $state = sanitize($_POST['state']);

                // Validate state code (should be 2 characters)
                if (strlen($state) !== 2) {
                    echo showMessage("State code must be exactly 2 characters", true);
                } else {
                    // First, check if the conference already exists
                    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM Conference WHERE name = ? AND state = ?");
                    $stmt->bind_param('ss', $name, $state);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $row = $result->fetch_assoc();

                    if ($row['count'] > 0) {
                        echo showMessage("A conference with this name and state already exists.", true);
                    } else {
                        $stmt = $conn->prepare("INSERT INTO Conference (name, state) VALUES (?, ?)");
                        $stmt->bind_param('ss', $name, $state);

                        if ($stmt->execute()) {
                            echo showMessage("Conference added successfully.");
                        } else {
                            echo showMessage("Error adding conference: " . $stmt->error, true);
                        }
                    }
                }
            }

            // Handle edit conference action
            if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $oldName = sanitize($_POST['oldName']);
                $oldState = sanitize($_POST['oldState']);
                $name = sanitize($_POST['name']);
                $state = sanitize($_POST['state']);

                // Validate state code (should be 2 characters)
                if (strlen($state) !== 2) {
                    echo showMessage("State code must be exactly 2 characters", true);
                } else {
                    // Check if new name/state combination already exists (and is different from the old one)
                    if (($oldName != $name || $oldState != $state) && 
                        !(empty($name) || empty($state))) {
                        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM Conference WHERE name = ? AND state = ?");
                        $stmt->bind_param('ss', $name, $state);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $row = $result->fetch_assoc();

                        if ($row['count'] > 0) {
                            echo showMessage("A conference with this name and state already exists.", true);
                            $action = 'list';  // Reset to list view
                        }
                    }

                    // If validation passed, proceed with the update
                    if ($action === 'edit') {
                        // Begin transaction for safety
                        $conn->begin_transaction();
                        
                        try {
                            // Update the conference information
                            $stmt = $conn->prepare("UPDATE Conference SET name = ?, state = ? WHERE name = ? AND state = ?");
                            $stmt->bind_param('ssss', $name, $state, $oldName, $oldState);
                            
                            if (!$stmt->execute()) {
                                throw new Exception("Failed to update conference: " . $stmt->error);
                            }
                            
                            // Update references in Team table
                            $stmt = $conn->prepare("UPDATE Team SET confName = ?, confState = ? WHERE confName = ? AND confState = ?");
                            $stmt->bind_param('ssss', $name, $state, $oldName, $oldState);
                            
                            if (!$stmt->execute()) {
                                throw new Exception("Failed to update team references: " . $stmt->error);
                            }
                            
                            echo "<div class='message'>Debug: Updated {$stmt->affected_rows} team references</div>";
                            
                            $conn->commit();
                            echo showMessage("Conference updated successfully");
                        } catch (Exception $e) {
                            $conn->rollback();
                            echo showMessage("Error: " . $e->getMessage(), true);
                        }
                    }
                }
                
                // Reset action to list view
                $action = 'list';
            }

            // Show edit form if requested
            if ($action === 'edit_form' && isset($_GET['name']) && isset($_GET['state'])) {
                $name = sanitize($_GET['name']);
                $state = sanitize($_GET['state']);
                
                // Get conference data
                $stmt = $conn->prepare("SELECT * FROM Conference WHERE name = ? AND state = ?");
                $stmt->bind_param('ss', $name, $state);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($row = $result->fetch_assoc()) {
                    ?>
                    <div class="edit-form" style="margin: 20px 0; background: #f5f5f5; padding: 15px; border-radius: 5px;">
                        <h3>Edit Conference: <?= htmlspecialchars($name) ?> (<?= htmlspecialchars($state) ?>)</h3>
                        <form method="post" action="?action=edit">
                            <input type="hidden" name="oldName" value="<?= htmlspecialchars($name) ?>">
                            <input type="hidden" name="oldState" value="<?= htmlspecialchars($state) ?>">
                            
                            <div>
                                <label for="name">Conference Name:</label>
                                <input type="text" name="name" value="<?= htmlspecialchars($row['name']) ?>" required>
                            </div>
                            <div>
                                <label for="state">State (2-letter code):</label>
                                <input type="text" name="state" value="<?= htmlspecialchars($row['state']) ?>" maxlength="2" required>
                            </div>
                            <div style="margin-top: 15px;">
                                <button type="submit" class="button">Update Conference</button>
                                <a href="conference_management.php" class="button">Cancel</a>
                            </div>
                        </form>
                    </div>
                    <?php
                } else {
                    echo showMessage("Conference not found", true);
                }
            }

            // Display the conference management UI if not in edit mode
            if ($action !== 'edit_form') {
            ?>

            <!-- Add Conference Button and Toggle Form -->
            <div style="margin: 20px 0;">
                <button onclick="toggleAddForm()" class="button">Add New Conference</button>

                <div id="addConferenceForm" style="display: none; margin-top: 15px; background: #f5f5f5; padding: 15px; border-radius: 5px;">
                    <h3>Add Conference</h3>
                    <form method="post" action="?action=add">
                        <div>
                            <label for="name">Conference Name:</label>
                            <input type="text" name="name" required>
                        </div>
                        <div>
                            <label for="state">State (2-letter code):</label>
                            <input type="text" name="state" maxlength="2" required>
                            <small>Example: CA for California, NY for New York</small>
                        </div>
                        <button type="submit" class="button">Add Conference</button>
                    </form>
                </div>
            </div>

            <!-- Search Form -->
            <div style="margin: 20px 0; background: #f5f5f5; padding: 15px; border-radius: 5px;">
                <h3>Search Conferences</h3>
                <form method="get">
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <div>
                            <label for="searchName">Name:</label>
                            <input type="text" name="searchName" id="searchName" value="<?= htmlspecialchars($_GET['searchName'] ?? '') ?>">
                        </div>
                        <div>
                            <label for="searchState">State:</label>
                            <input type="text" name="searchState" id="searchState" maxlength="2" value="<?= htmlspecialchars($_GET['searchState'] ?? '') ?>">
                        </div>
                        <div>
                            <label style="visibility: hidden;">Search</label>
                            <button type="submit" class="button">Search</button>
                            <a href="conference_management.php" class="button">Reset</a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Conferences Table -->
            <div class="table-container">
                <h3>Conferences</h3>

                <?php
                // Build search query based on filters
                $whereConditions = [];
                $params = [];
                $types = '';

                if (!empty($_GET['searchName'])) {
                    $whereConditions[] = "c.name LIKE ?";
                    $params[] = '%' . $_GET['searchName'] . '%';
                    $types .= 's';
                }

                if (!empty($_GET['searchState'])) {
                    $whereConditions[] = "c.state LIKE ?";
                    $params[] = '%' . $_GET['searchState'] . '%';
                    $types .= 's';
                }

                $query = "SELECT c.*, 
                         (SELECT COUNT(*) FROM Team t WHERE t.confName = c.name AND t.confState = c.state) as teamCount
                         FROM Conference c";
                
                if (!empty($whereConditions)) {
                    $query .= " WHERE " . implode(" AND ", $whereConditions);
                }
                $query .= " ORDER BY c.name, c.state";

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
                            <th>Conference Name</th>
                            <th>State</th>
                            <th>Teams</th>
                            <th>Actions</th>
                        </tr>";

                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['state']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['teamCount']) . "</td>";
                        echo "<td>";
                        echo "<a href='?action=edit_form&name=" . urlencode($row['name']) . "&state=" . urlencode($row['state']) . "' class='button'>Edit</a> ";
                        
                        // Only show delete option if conference has no teams
                        if ($row['teamCount'] == 0) {
                            echo "<a href='?action=delete&name=" . urlencode($row['name']) . "&state=" . urlencode($row['state']) . "' class='button' onclick='return confirm(\"Are you sure you want to delete this conference?\")'>Delete</a>";
                        } else {
                            echo "<button class='button' disabled title='Cannot delete: Conference has teams'>Delete</button>";
                        }
                        
                        echo "</td>";
                        echo "</tr>";
                    }

                    echo "</table>";
                } else {
                    echo "<p>No conferences found matching your criteria</p>";
                }
                ?>
            </div>

            <script>
            // Toggle add conference form
            function toggleAddForm() {
                const form = document.getElementById('addConferenceForm');
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