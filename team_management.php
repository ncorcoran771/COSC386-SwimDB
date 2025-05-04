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
?>
<body>
    <div class='main'>
        <div class='container'>
            <h1>Team Management</h1>

            <?php
            // Show message if any
            if (isset($_SESSION['message'])) {
                echo showMessage($_SESSION['message']);
                unset($_SESSION['message']);
            }

            // Handle edit team action
            if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $oldTeamName = sanitize($_POST['oldTeamName']);
                $teamName = sanitize($_POST['teamName']);
                $location = sanitize($_POST['location']);
                $confName = sanitize($_POST['confName']);
                $confState = sanitize($_POST['confState']);
                
                // Check if the new conference exists, if not create it
                $stmt = $conn->prepare("SELECT * FROM Conference WHERE name = ? AND state = ?");
                $stmt->bind_param('ss', $confName, $confState);
                $stmt->execute();
                
                if ($stmt->get_result()->num_rows === 0) {
                    // Insert new conference
                    $stmt = $conn->prepare("INSERT INTO Conference (name, state) VALUES (?, ?)");
                    $stmt->bind_param('ss', $confName, $confState);
                    
                    if (!$stmt->execute()) {
                        echo showMessage("Error creating conference: " . $stmt->error, true);
                        // Don't proceed if conference creation failed
                        $action = 'list';
                    }
                }
                
                // Now update the team
                if ($action === 'edit') {
                    $stmt = $conn->prepare("UPDATE Team SET teamName = ?, location = ?, confName = ?, confState = ? WHERE teamName = ?");
                    $stmt->bind_param('sssss', $teamName, $location, $confName, $confState, $oldTeamName);
                    
                    if ($stmt->execute()) {
                        // If team name changed, update references in Swimmer table
                        if ($oldTeamName !== $teamName) {
                            $stmt = $conn->prepare("UPDATE Swimmer SET team = ? WHERE team = ?");
                            $stmt->bind_param('ss', $teamName, $oldTeamName);
                            $stmt->execute();
                        }
                        
                        echo showMessage("Team updated successfully");
                    } else {
                        echo showMessage("Error updating team: " . $stmt->error, true);
                    }
                }
                
                // Reset action to list view
                $action = 'list';
            }

            // Handle delete action if requested
            if ($action === 'delete' && isset($_POST['teamName'])) {
                $teamName = sanitize($_POST['teamName']);
                
                // Check if team is used by any swimmers
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM Swimmer WHERE team = ?");
                $stmt->bind_param('s', $teamName);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                
                if ($row['count'] > 0) {
                    echo showMessage("Cannot delete team: $teamName is used by {$row['count']} swimmers", true);
                } else {
                    $stmt = $conn->prepare("DELETE FROM Team WHERE teamName = ?");
                    $stmt->bind_param('s', $teamName);
                    
                    if ($stmt->execute()) {
                        echo showMessage("Team deleted successfully");
                    } else {
                        echo showMessage("Error deleting team: " . $stmt->error, true);
                    }
                }
            }

            // Handle add team action
            if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $teamName = sanitize($_POST['teamName']);
                $location = sanitize($_POST['location']);
                $confName = sanitize($_POST['confName']);
                $confState = sanitize($_POST['confState']);
                
                // First check if conference exists, if not create it
                $stmt = $conn->prepare("SELECT * FROM Conference WHERE name = ? AND state = ?");
                $stmt->bind_param('ss', $confName, $confState);
                $stmt->execute();
                
                if ($stmt->get_result()->num_rows === 0) {
                    // Insert new conference
                    $stmt = $conn->prepare("INSERT INTO Conference (name, state) VALUES (?, ?)");
                    $stmt->bind_param('ss', $confName, $confState);
                    
                    if (!$stmt->execute()) {
                        echo showMessage("Error creating conference: " . $stmt->error, true);
                        $action = 'list'; // Reset action to prevent team creation
                    }
                }
                
                // Now create the team
                if ($action === 'add') { // Only proceed if conference creation was successful
                    $stmt = $conn->prepare("INSERT INTO Team (teamName, location, confName, confState) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param('ssss', $teamName, $location, $confName, $confState);
                    
                    if ($stmt->execute()) {
                        echo showMessage("Team added successfully.");
                    } else {
                        echo showMessage("Error adding team: " . $stmt->error, true);
                    }
                }
            }

            // Handle "fix missing teams" action
            if ($action === 'fix_missing') {
                // Get all unique teams from Swimmer table that don't exist in Team table
                $query = "SELECT DISTINCT s.team FROM Swimmer s 
                        LEFT JOIN Team t ON s.team = t.teamName 
                        WHERE t.teamName IS NULL";
                $result = $conn->query($query);
                
                $teamsFixed = 0;
                $teamsFailed = 0;
                
                if ($result && $result->num_rows > 0) {
                    // Default conference for fixing teams
                    $defaultConfName = "Default Conference";
                    $defaultConfState = "DC";
                    
                    // Ensure default conference exists
                    $stmt = $conn->prepare("SELECT * FROM Conference WHERE name = ? AND state = ?");
                    $stmt->bind_param('ss', $defaultConfName, $defaultConfState);
                    $stmt->execute();
                    
                    if ($stmt->get_result()->num_rows === 0) {
                        $stmt = $conn->prepare("INSERT INTO Conference (name, state) VALUES (?, ?)");
                        $stmt->bind_param('ss', $defaultConfName, $defaultConfState);
                        $stmt->execute();
                    }
                    
                    // Now add each missing team to the Team table
                    while ($row = $result->fetch_assoc()) {
                        $teamName = $row['team'];
                        $defaultLocation = "Unknown"; // Default location
                        
                        $stmt = $conn->prepare("INSERT INTO Team (teamName, location, confName, confState) VALUES (?, ?, ?, ?)");
                        $stmt->bind_param('ssss', $teamName, $defaultLocation, $defaultConfName, $defaultConfState);
                        
                        if ($stmt->execute()) {
                            $teamsFixed++;
                        } else {
                            $teamsFailed++;
                        }
                    }
                    
                    if ($teamsFixed > 0) {
                        echo showMessage("Fixed $teamsFixed teams by adding them to the Team table with default conference info.");
                    }
                    
                    if ($teamsFailed > 0) {
                        echo showMessage("Failed to fix $teamsFailed teams. Please check database constraints.", true);
                    }
                } else {
                    echo showMessage("No missing teams found. All teams used by swimmers exist in the Team table.");
                }
            }

            // Show edit form if requested
            if ($action === 'edit_form' && isset($_GET['team'])) {
                $teamName = sanitize($_GET['team']);
                
                // Get team data
                $stmt = $conn->prepare("SELECT * FROM Team WHERE teamName = ?");
                $stmt->bind_param('s', $teamName);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($row = $result->fetch_assoc()) {
                    ?>
                    <div class="edit-form" style="margin: 20px 0; background: #f5f5f5; padding: 15px; border-radius: 5px;">
                        <h3>Edit Team: <?= htmlspecialchars($teamName) ?></h3>
                        <form method="post" action="?action=edit">
                            <input type="hidden" name="oldTeamName" value="<?= htmlspecialchars($teamName) ?>">
                            
                            <div>
                                <label for="teamName">Team Name:</label>
                                <input type="text" name="teamName" value="<?= htmlspecialchars($row['teamName']) ?>" required>
                            </div>
                            <div>
                                <label for="location">Location:</label>
                                <input type="text" name="location" value="<?= htmlspecialchars($row['location']) ?>" required>
                            </div>
                            <div>
                                <label for="confName">Conference Name:</label>
                                <select name="confName">
                                    <?php
                                    // Get all conferences
                                    $confResult = $conn->query("SELECT DISTINCT name FROM Conference ORDER BY name");
                                    while ($confRow = $confResult->fetch_assoc()) {
                                        $selected = ($confRow['name'] === $row['confName']) ? 'selected' : '';
                                        echo "<option value='" . htmlspecialchars($confRow['name']) . "' $selected>" . 
                                            htmlspecialchars($confRow['name']) . "</option>";
                                    }
                                    ?>
                                    <option value="new">Add New Conference</option>
                                </select>
                            </div>
                            <div id="newConfNameSection" style="display: none;">
                                <label for="newConfName">New Conference Name:</label>
                                <input type="text" name="newConfName" id="newConfName">
                            </div>
                            <div>
                                <label for="confState">Conference State:</label>
                                <select name="confState">
                                    <?php
                                    // Get all states
                                    $stateResult = $conn->query("SELECT DISTINCT state FROM Conference ORDER BY state");
                                    while ($stateRow = $stateResult->fetch_assoc()) {
                                        $selected = ($stateRow['state'] === $row['confState']) ? 'selected' : '';
                                        echo "<option value='" . htmlspecialchars($stateRow['state']) . "' $selected>" . 
                                            htmlspecialchars($stateRow['state']) . "</option>";
                                    }
                                    ?>
                                    <option value="new">Add New State</option>
                                </select>
                            </div>
                            <div id="newConfStateSection" style="display: none;">
                                <label for="newConfState">New Conference State (2 letters):</label>
                                <input type="text" name="newConfState" id="newConfState" maxlength="2">
                            </div>
                            <div style="margin-top: 15px;">
                                <button type="submit" class="button">Update Team</button>
                                <a href="team_management.php" class="button">Cancel</a>
                            </div>
                        </form>
                    </div>
                    <?php
                } else {
                    echo showMessage("Team not found", true);
                }
            }

            // Display the team management UI if not in edit mode
            if ($action !== 'edit_form') {
            ?>

            <!-- Add Team Button and Toggle Form -->
            <div style="margin: 20px 0;">
                <button onclick="toggleAddForm()" class="button">Add New Team</button>
                <a href="?action=fix_missing" class="button">Fix Missing Teams</a>
                
                <div id="addTeamForm" style="display: none; margin-top: 15px; background: #f5f5f5; padding: 15px; border-radius: 5px;">
                    <h3>Add Team</h3>
                    <form method="post" action="?action=add">
                        <div>
                            <label for="teamName">Team Name:</label>
                            <input type="text" name="teamName" required>
                        </div>
                        <div>
                            <label for="location">Location:</label>
                            <input type="text" name="location" required>
                        </div>
                        <div>
                            <label for="confName">Conference Name:</label>
                            <select name="confName" id="confName">
                                <?php
                                // Get unique conference names
                                $result = $conn->query("SELECT DISTINCT name FROM Conference ORDER BY name");
                                while($row = $result->fetch_assoc()) {
                                    echo "<option value='" . htmlspecialchars($row['name']) . "'>" . 
                                        htmlspecialchars($row['name']) . "</option>";
                                }
                                ?>
                                <option value="new">Add New Conference</option>
                            </select>
                        </div>
                        <div id="newConfNameSection" style="display: none;">
                            <label for="newConfName">New Conference Name:</label>
                            <input type="text" name="newConfName" id="newConfName">
                        </div>
                        <div>
                            <label for="confState">Conference State:</label>
                            <select name="confState" id="confState">
                                <?php
                                // Get unique conference states
                                $result = $conn->query("SELECT DISTINCT state FROM Conference ORDER BY state");
                                while($row = $result->fetch_assoc()) {
                                    echo "<option value='" . htmlspecialchars($row['state']) . "'>" . 
                                        htmlspecialchars($row['state']) . "</option>";
                                }
                                ?>
                                <option value="new">Add New State</option>
                            </select>
                        </div>
                        <div id="newConfStateSection" style="display: none;">
                            <label for="newConfState">New Conference State (2 letters):</label>
                            <input type="text" name="newConfState" id="newConfState" maxlength="2">
                        </div>
                        <button type="submit" class="button">Add Team</button>
                    </form>
                </div>
            </div>

            <!-- Teams Table -->
            <div class="table-container">
                <h3>Teams</h3>
                
                <?php
                $query = "SELECT t.teamName, t.location, t.confName, t.confState, COUNT(s.swimmerID) as swimmerCount 
                        FROM Team t
                        LEFT JOIN Swimmer s ON t.teamName = s.team
                        GROUP BY t.teamName, t.location, t.confName, t.confState
                        ORDER BY t.teamName";
                        
                $result = $conn->query($query);
                
                if ($result && $result->num_rows > 0) {
                    echo "<table>";
                    echo "<tr>
                            <th>Team Name</th>
                            <th>Location</th>
                            <th>Conference</th>
                            <th>State</th>
                            <th>Swimmers</th>
                            <th>Actions</th>
                        </tr>";
                        
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['teamName']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['location']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['confName']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['confState']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['swimmerCount']) . "</td>";
                        echo "<td>";
                        echo "<a href='team_profile.php?team=" . urlencode($row['teamName']) . "' class='button'>View Profile</a> ";
                        echo "<a href='?action=edit_form&team=" . urlencode($row['teamName']) . "' class='button'>Edit</a> ";
                        
                        // Only show delete option if team has no swimmers
                        if ($row['swimmerCount'] == 0) {
                            echo "<button onclick='confirmDelete(\"" . htmlspecialchars(addslashes($row['teamName'])) . "\")' class='button'>Delete</button>";
                        }
                        
                        echo "</td>";
                        echo "</tr>";
                    }
                    
                    echo "</table>";
                } else {
                    echo "<p>No teams found</p>";
                }
                
                // Show orphaned teams (teams referenced by swimmers but not in Team table)
                $query = "SELECT s.team, COUNT(*) as count 
                        FROM Swimmer s 
                        LEFT JOIN Team t ON s.team = t.teamName 
                        WHERE t.teamName IS NULL 
                        GROUP BY s.team";
                $result = $conn->query($query);
                
                if ($result && $result->num_rows > 0) {
                    echo "<h3 style='color: red;'>Missing Teams (Referenced by Swimmers)</h3>";
                    echo "<table>";
                    echo "<tr><th>Team Name</th><th>Swimmer Count</th><th>Actions</th></tr>";
                    
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['team']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['count']) . "</td>";
                        echo "<td><a href='?action=fix_missing' class='button'>Fix All</a></td>";
                        echo "</tr>";
                    }
                    
                    echo "</table>";
                }
            }
            ?>
            </div>

            <!-- Hidden delete form for submission -->
            <form id="deleteForm" method="post" action="?action=delete" style="display: none;">
                <input type="hidden" name="teamName" id="deleteTeamName">
            </form>

            <script>
            // Toggle add team form
            function toggleAddForm() {
                const form = document.getElementById('addTeamForm');
                form.style.display = form.style.display === 'none' ? 'block' : 'none';
            }

            // Conference selection handler
            document.addEventListener('DOMContentLoaded', function() {
                // Handle conference name selection
                const confNameSelect = document.getElementById('confName');
                const newConfNameSection = document.getElementById('newConfNameSection');
                const newConfNameInput = document.getElementById('newConfName');
                
                if (confNameSelect && newConfNameSection && newConfNameInput) {
                    confNameSelect.addEventListener('change', function() {
                        if (this.value === 'new') {
                            newConfNameSection.style.display = 'block';
                            newConfNameInput.setAttribute('required', 'required');
                        } else {
                            newConfNameSection.style.display = 'none';
                            newConfNameInput.removeAttribute('required');
                        }
                    });
                }
                
                // Handle conference state selection
                const confStateSelect = document.getElementById('confState');
                const newConfStateSection = document.getElementById('newConfStateSection');
                const newConfStateInput = document.getElementById('newConfState');
                
                if (confStateSelect && newConfStateSection && newConfStateInput) {
                    confStateSelect.addEventListener('change', function() {
                        if (this.value === 'new') {
                            newConfStateSection.style.display = 'block';
                            newConfStateInput.setAttribute('required', 'required');
                        } else {
                            newConfStateSection.style.display = 'none';
                            newConfStateInput.removeAttribute('required');
                        }
                    });
                }
                
                // Handle form submission to use custom conference if specified
                const form = document.querySelector('form[action="?action=add"]');
                if (form) {
                    form.addEventListener('submit', function(e) {
                        // Handle new conference name
                        if (confNameSelect.value === 'new' && newConfNameInput.value.trim()) {
                            confNameSelect.name = 'oldConfName'; // Rename original select
                            
                            // Create hidden input with the new conference name
                            const hiddenConfName = document.createElement('input');
                            hiddenConfName.type = 'hidden';
                            hiddenConfName.name = 'confName';
                            hiddenConfName.value = newConfNameInput.value.trim();
                            
                            // Add this hidden input to the form
                            this.appendChild(hiddenConfName);
                        }
                        
                        // Handle new conference state
                        if (confStateSelect.value === 'new' && newConfStateInput.value.trim()) {
                            confStateSelect.name = 'oldConfState'; // Rename original select
                            
                            // Create hidden input with the new state
                            const hiddenConfState = document.createElement('input');
                            hiddenConfState.type = 'hidden';
                            hiddenConfState.name = 'confState';
                            hiddenConfState.value = newConfStateInput.value.trim().toUpperCase();
                            
                            // Add this hidden input to the form
                            this.appendChild(hiddenConfState);
                        }
                    });
                }
                
                // Apply the same logic to the edit form if it exists
                const editForm = document.querySelector('form[action="?action=edit"]');
                if (editForm) {
                    const editConfNameSelect = editForm.querySelector('select[name="confName"]');
                    const editNewConfNameSection = document.getElementById('newConfNameSection');
                    const editNewConfNameInput = document.getElementById('newConfName');
                    
                    if (editConfNameSelect && editNewConfNameSection && editNewConfNameInput) {
                        editConfNameSelect.addEventListener('change', function() {
                            if (this.value === 'new') {
                                editNewConfNameSection.style.display = 'block';
                                editNewConfNameInput.setAttribute('required', 'required');
                            } else {
                                editNewConfNameSection.style.display = 'none';
                                editNewConfNameInput.removeAttribute('required');
                            }
                        });
                    }
                    
                    const editConfStateSelect = editForm.querySelector('select[name="confState"]');
                    const editNewConfStateSection = document.getElementById('newConfStateSection');
                    const editNewConfStateInput = document.getElementById('newConfState');
                    
                    if (editConfStateSelect && editNewConfStateSection && editNewConfStateInput) {
                        editConfStateSelect.addEventListener('change', function() {
                            if (this.value === 'new') {
                                editNewConfStateSection.style.display = 'block';
                                editNewConfStateInput.setAttribute('required', 'required');
                            } else {
                                editNewConfStateSection.style.display = 'none';
                                editNewConfStateInput.removeAttribute('required');
                            }
                        });
                    }
                    
                    // Process edit form submission
                    editForm.addEventListener('submit', function(e) {
                        // Handle new conference name
                        if (editConfNameSelect.value === 'new' && editNewConfNameInput.value.trim()) {
                            editConfNameSelect.name = 'oldConfName'; // Rename original select
                            
                            // Create hidden input with the new conference name
                            const hiddenConfName = document.createElement('input');
                            hiddenConfName.type = 'hidden';
                            hiddenConfName.name = 'confName';
                            hiddenConfName.value = editNewConfNameInput.value.trim();
                            
                            // Add this hidden input to the form
                            this.appendChild(hiddenConfName);
                        }
                        
                        // Handle new conference state
                        if (editConfStateSelect.value === 'new' && editNewConfStateInput.value.trim()) {
                            editConfStateSelect.name = 'oldConfState'; // Rename original select
                            
                            // Create hidden input with the new state
                            const hiddenConfState = document.createElement('input');
                            hiddenConfState.type = 'hidden';
                            hiddenConfState.name = 'confState';
                            hiddenConfState.value = editNewConfStateInput.value.trim().toUpperCase();
                            
                            // Add this hidden input to the form
                            this.appendChild(hiddenConfState);
                        }
                    });
                }
            });

            // Delete confirmation
            function confirmDelete(teamName) {
                if (confirm(`Are you sure you want to delete the team: ${teamName}?`)) {
                    const form = document.getElementById('deleteForm');
                    const teamNameField = document.getElementById('deleteTeamName');
                    teamNameField.value = teamName;
                    form.submit();
                }
            }
            </script>

            <p><a href="home.php">Back to Home</a></p>

            <?php include 'includes/footer.php'; ?>

        </div>
    </div>
</body>