<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Include the DB connection file
include('DB.php'); 

// Check the database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Temporary bypass for development (remove comments below to re-enable login requirement)
/*
if (!isset($_SESSION['loggedIN']) || !$_SESSION['loggedIN']) {
    echo "You are not logged in. <a href='indexp.php'>Login</a>";
    exit;
}
*/

$user = $_SESSION['userData']['name'] ?? 'Guest';
$role = $_SESSION['userData']['type'] ?? 'guest'; // guest, user, or admin

// Handle search filters
$searchTeam = $_POST['teamName'] ?? '';
$searchLocation = $_POST['location'] ?? '';
$searchConfName = $_POST['confName'] ?? '';
$searchConfState = $_POST['confState'] ?? '';

// Prepare the SQL query with dynamic filters
$sql = "SELECT teamName, location, confName, confState FROM Team WHERE 1=1";

if ($searchTeam) {
    $sql .= " AND teamName LIKE ?";
}
if ($searchLocation) {
    $sql .= " AND location LIKE ?";
}
if ($searchConfName) {
    $sql .= " AND confName LIKE ?";
}
if ($searchConfState) {
    $sql .= " AND confState LIKE ?";
}

// Prepare the statement
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}

// Bind parameters if filters are set
$params = [];
$types = '';

if ($searchTeam) {
    $params[] = "%$searchTeam%";
    $types .= 's';  // 's' indicates string type
}
if ($searchLocation) {
    $params[] = "%$searchLocation%";
    $types .= 's';
}
if ($searchConfName) {
    $params[] = "%$searchConfName%";
    $types .= 's';
}
if ($searchConfState) {
    $params[] = "%$searchConfState%";
    $types .= 's';
}

// Bind the parameters dynamically
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

// Execute the statement
$stmt->execute();

// Get the result
$result = $stmt->get_result();

// Fetch all results
$teams = [];
while ($row = $result->fetch_assoc()) {
    $teams[] = $row;
}

// Close the statement and connection
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Search Teams</title>
</head>
<body>
    <h1>Search Teams</h1>

    <form method="POST">
        <input type="text" name="teamName" placeholder="Search by Team Name" value="<?= htmlspecialchars($searchTeam ?? '') ?>">
        <input type="text" name="location" placeholder="Search by Location" value="<?= htmlspecialchars($searchLocation ?? '') ?>">
        <input type="text" name="confName" placeholder="Search by Conference Name" value="<?= htmlspecialchars($searchConfName ?? '') ?>">
        <input type="text" name="confState" placeholder="Search by Conference State" value="<?= htmlspecialchars($searchConfState ?? '') ?>">
        <button type="submit">Search</button>
    </form>

    <table border="1">
        <thead>
            <tr>
                <th>Team Name</th>
                <th>Location</th>
                <th>Conference Name</th>
                <th>Conference State</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($teams)): ?>
                <tr>
                    <td colspan="4">No results found</td>
                </tr>
            <?php else: ?>
                <?php foreach ($teams as $team): ?>
                    <tr>
                        <td><?= htmlspecialchars($team['teamName']) ?></td>
                        <td><?= htmlspecialchars($team['location']) ?></td>
                        <td><?= htmlspecialchars($team['confName']) ?></td>
                        <td><?= htmlspecialchars($team['confState']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div>
        <a href="home.php">Back to Home</a> | 
        <a href="logout.php">Logout</a>
    </div>
</body>
</html>

