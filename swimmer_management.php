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
            <h1>Swimmer Management</h1>

            <?php
            // Show message if any
            if (isset($_SESSION['message'])) {
                echo showMessage($_SESSION['message']);
                unset($_SESSION['message']);
            }

            // Handle delete action if requested
            if ($action === 'delete' && isset($_POST['swimmerID'])) {
                $swimmerID = sanitize($_POST['swimmerID']);
                
                // First check if swimmer has any swim records
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
                        echo "<p>This swimmer has {$row['count']} swim records. Delete these records first?</p>";
                        echo "<form method='post' action='?action=delete'>";
                        echo "<input type='hidden' name='swimmerID' value='$swimmerID'>";
                        echo "<input type='hidden' name='deleteRecords' value='yes'>";
                        echo "<button type='submit' class='button'>Yes, delete swimmer and all their records</button> ";
                        echo "<a href='swimmer_management.php' class='button'>Cancel</a>";
                        echo "</form>";
                        echo "</div>";
                    }
                } else {
                    // Swimmer has no swim records, proceed with deletion
                    $stmt = $conn->prepare("DELETE FROM Swimmer WHERE swimmerID = ?");
                    $stmt->bind_param('i', $swimmerID);
                    
                    if ($stmt->execute()) {
                        echo showMessage("Swimmer deleted successfully");
                    } else {
                        echo showMessage("Error deleting swimmer: " . $stmt->error, true);
                    }
                }
            }

            // Handle add swimmer action
            if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $name = sanitize($_POST['name']);
                $powerIndex = sanitize($_POST['powerIndex']);
                $gender = sanitize($_POST['gender']);
                $hometown = sanitize($_POST['hometown']);
                $team = sanitize($_POST['team']);
                
                // First, check if the team exists
                $stmt = $conn->prepare("SELECT teamName FROM Team WHERE teamName = ?");
                $stmt->bind_param('s', $team);
                $stmt->execute();
                $result = $stmt->get_result();
                
                // If team doesn't exist, show a warning
                if ($result->num_rows === 0) {
                    echo showMessage("Warning: Team '$team' does not exist in the Team table. Swimmer will be added, but may not appear in team views.", true);
                    // Note: In a more robust implementation, we would offer to create the team here
                }
                
                $stmt = $conn->prepare("INSERT INTO Swimmer (name, powerIndex, gender, hometown, team) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param('sisss', $name, $powerIndex, $gender, $hometown, $team);
                
                if ($stmt->execute()) {
                    $swimmerID = $conn->insert_id;
                    echo showMessage("Swimmer added successfully. New Swimmer ID: $swimmerID");
                } else {
                    echo showMessage("Error adding swimmer: " . $stmt->error, true);
                }
            }
            ?>

            <!-- Add Swimmer Button and Toggle Form -->
            <div style="margin: 20px 0;">
                <button onclick="toggleAddForm()" class="button">Add New Swimmer</button>
                
                <div id="addSwimmerForm" style="display: none; margin-top: 15px; background: #f5f5f5; padding: 15px; border-radius: 5px;">
                    <h3>Add Swimmer</h3>
                    <form method="post" action="?action=add">
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
                            <select name="gender" required>
                                <option value="M">Male</option>
                                <option value="F">Female</option>
                            </select>
                        </div>
                        <div>
                            <label for="hometown">Hometown:</label>
                            <input type="text" name="hometown" required>
                        </div>
                        <div>
                            <label for="team">Team:</label>
                            <select name="team" required>
                                <?php
                                // Get existing teams
                                $result = $conn->query("SELECT teamName FROM Team ORDER BY teamName");
                                while($row = $result->fetch_assoc()) {
                                    echo "<option value='" . htmlspecialchars($row['teamName']) . "'>" . 
                                        htmlspecialchars($row['teamName']) . "</option>";
                                }
                                ?>
                                <option value="other">Other (specify below)</option>
                            </select>
                        </div>
                        <div id="otherTeamSection" style="display: none;">
                            <label for="otherTeam">Specify Team:</label>
                            <input type="text" name="otherTeam" id="otherTeam">
                            <span class="help">Note: Custom teams need to be properly configured with conference info</span>
                        </div>
                        <button type="submit" class="button">Add Swimmer</button>
                    </form>
                </div>
            </div>

            <!-- Search Form -->
            <div style="margin: 20px 0; background: #f5f5f5; padding: 15px; border-radius: 5px;">
                <h3>Search Swimmers</h3>
                <form method="get">
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <div>
                            <label for="searchName">Name:</label>
                            <input type="text" name="searchName" id="searchName" value="<?= htmlspecialchars($_GET['searchName'] ?? '') ?>">
                        </div>
                        <div>
                            <label for="searchTeam">Team:</label>
                            <input type="text" name="searchTeam" id="searchTeam" value="<?= htmlspecialchars($_GET['searchTeam'] ?? '') ?>">
                        </div>
                        <div>
                            <label for="searchHometown">Hometown:</label>
                            <input type="text" name="searchHometown" id="searchHometown" value="<?= htmlspecialchars($_GET['searchHometown'] ?? '') ?>">
                        </div>
                        <div>
                            <label style="visibility: hidden;">Search</label>
                            <button type="submit" class="button">Search</button>
                            <a href="swimmer_management.php" class="button">Reset</a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Swimmers Table -->
            <div class="table-container">
                <h3>Swimmers</h3>
                
                <?php
                // Build search query based on filters
                $whereConditions = [];
                $params = [];
                $types = '';
                
                if (!empty($_GET['searchName'])) {
                    $whereConditions[] = "name LIKE ?";
                    $params[] = '%' . $_GET['searchName'] . '%';
                    $types .= 's';
                }
                
                if (!empty($_GET['searchTeam'])) {
                    $whereConditions[] = "team LIKE ?";
                    $params[] = '%' . $_GET['searchTeam'] . '%';
                    $types .= 's';
                }
                
                if (!empty($_GET['searchHometown'])) {
                    $whereConditions[] = "hometown LIKE ?";
                    $params[] = '%' . $_GET['searchHometown'] . '%';
                    $types .= 's';
                }
                
                $query = "SELECT s.*, (SELECT COUNT(*) FROM Swim sw WHERE sw.swimmerID = s.swimmerID) as swimCount FROM Swimmer s";
                if (!empty($whereConditions)) {
                    $query .= " WHERE " . implode(" AND ", $whereConditions);
                }
                $query .= " ORDER BY name";
                
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
                            <th>ID</th>
                            <th>Name</th>
                            <th>Gender</th>
                            <th>Hometown</th>
                            <th>Team</th>
                            <th>Power Index</th>
                            <th>Swim Records</th>
                            <th>Actions</th>
                        </tr>";
                        
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['swimmerID']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['gender']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['hometown']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['team']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['powerIndex']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['swimCount']) . "</td>";
                        echo "<td>
                                <a href='swimmer_profile.php?id=" . $row['swimmerID'] . "' class='button'>View Profile</a>
                                <a href='operations.php?action=insert&entity=swim&swimmer=" . $row['swimmerID'] . "' class='button'>Add Times</a>
                                <button onclick='confirmDelete(" . $row['swimmerID'] . ", \"" . htmlspecialchars(addslashes($row['name'])) . "\", " . $row['swimCount'] . ")' class='button'>Delete</button>
                            </td>";
                        echo "</tr>";
                    }
                    
                    echo "</table>";
                } else {
                    echo "<p>No swimmers found matching your criteria</p>";
                }
                ?>
            </div>

            <!-- Hidden delete form for submission -->
            <form id="deleteForm" method="post" action="?action=delete" style="display: none;">
                <input type="hidden" name="swimmerID" id="deleteSwimmerID">
            </form>

            <script>
            // Toggle add swimmer form
            function toggleAddForm() {
                const form = document.getElementById('addSwimmerForm');
                form.style.display = form.style.display === 'none' ? 'block' : 'none';
            }

            // Team selection handler
            document.addEventListener('DOMContentLoaded', function() {
                const teamSelect = document.querySelector('select[name="team"]');
                const otherSection = document.getElementById('otherTeamSection');
                const otherInput = document.getElementById('otherTeam');
                
                if (teamSelect && otherSection && otherInput) {
                    teamSelect.addEventListener('change', function() {
                        if (this.value === 'other') {
                            otherSection.style.display = 'block';
                            otherInput.setAttribute('required', 'required');
                        } else {
                            otherSection.style.display = 'none';
                            otherInput.removeAttribute('required');
                        }
                    });
                }
                
                // Handle form submission to use custom team if specified
                const form = document.querySelector('form[action="?action=add"]');
                if (form) {
                    form.addEventListener('submit', function(e) {
                        const teamSelect = this.querySelector('select[name="team"]');
                        const otherInput = this.querySelector('input[name="otherTeam"]');
                        
                        if (teamSelect.value === 'other' && otherInput && otherInput.value.trim()) {
                            e.preventDefault(); // Prevent normal submission
                            
                            // Create hidden input with the custom team value
                            const hiddenTeam = document.createElement('input');
                            hiddenTeam.type = 'hidden';
                            hiddenTeam.name = 'team';
                            hiddenTeam.value = otherInput.value.trim();
                            
                            // Replace the select element with this hidden input
                            teamSelect.parentNode.replaceChild(hiddenTeam, teamSelect);
                            
                            // Submit the form
                            this.submit();
                        }
                    });
                }
            });

            // Delete confirmation with name and swim record check
            function confirmDelete(id, name, swimCount) {
                if (swimCount > 0) {
                    if (confirm(`${name} has ${swimCount} swim records. Are you sure you want to delete this swimmer and all their records?`)) {
                        const form = document.getElementById('deleteForm');
                        const idField = document.getElementById('deleteSwimmerID');
                        idField.value = id;
                        
                        // Add deleteRecords field
                        const deleteRecordsField = document.createElement('input');
                        deleteRecordsField.type = 'hidden';
                        deleteRecordsField.name = 'deleteRecords';
                        deleteRecordsField.value = 'yes';
                        form.appendChild(deleteRecordsField);
                        
                        form.submit();
                    }
                } else {
                    if (confirm(`Are you sure you want to delete ${name}?`)) {
                        const form = document.getElementById('deleteForm');
                        const idField = document.getElementById('deleteSwimmerID');
                        idField.value = id;
                        form.submit();
                    }
                }
            }
            </script>

            <p><a href="home.php">Back to Home</a></p>

            <?php include 'includes/footer.php'; ?>
        </div>
    </div>
</body>