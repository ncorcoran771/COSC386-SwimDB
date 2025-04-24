<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Check if user is admin
if (!isAdmin()) {
    redirect('../home.php', 'Unauthorized access');
}

$action = $_GET['action'] ?? 'list';
$entity = $_GET['entity'] ?? 'swimmer';
$user = getCurrentUser();

include '../includes/header.php';
?>

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
                echo showMessage("Admin added successfully");
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
                echo showMessage("Swimmer added successfully");
            } else {
                echo showMessage("Error adding swimmer: " . $stmt->error, true);
            }
        }
        
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
        break;
        
    case 'swimmer:delete':
        // Delete swimmer logic (from delete_swimmer.php)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $swimmerID = sanitize($_POST['swimmerID']);
            
            $stmt = $conn->prepare("DELETE FROM Swimmer WHERE swimmerID = ?");
            $stmt->bind_param('i', $swimmerID);
            
            if ($stmt->execute()) {
                echo showMessage("Swimmer deleted successfully");
            } else {
                echo showMessage("Error deleting swimmer: " . $stmt->error, true);
            }
        }
        
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
        break;
        
    case 'view:conferences':
    case 'view:meets':
    case 'view:swims':
    case 'view:teams':
        // Generic table viewer
        $tableName = str_replace('view:', '', "$entity:$action");
        $tableMap = [
            'conferences' => 'Conference',
            'meets' => 'Meet',
            'swims' => 'Swim',
            'teams' => 'Team'
        ];
        
        $dbTable = $tableMap[$tableName] ?? '';
        
        if (empty($dbTable)) {
            echo showMessage("Invalid table specified", true);
            break;
        }
        
        $result = $conn->query("SELECT * FROM $dbTable");
        
        if ($result->num_rows > 0) {
            echo "<h2>$dbTable Data</h2>";
            echo "<table>";
            
            // Get column names
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
                foreach ($row as $value) {
                    echo "<td>" . htmlspecialchars($value) . "</td>";
                }
                echo "</tr>";
            }
            
            echo "</table>";
        } else {
            echo showMessage("No data in table");
        }
        break;
        
    default:
        echo showMessage("Invalid action", true);
}
?>

<p><a href="../home.php">Back to Home</a></p>

<?php include '../includes/footer.php'; ?>