<?php
session_start();

// Include DB.php for database connection
include('DB.php');

// Temporary bypass for development (remove comments below to re-enable login requirement)
/*
if (!isset($_SESSION['loggedIN']) || !$_SESSION['loggedIN']) {
    echo "You are not logged in. <a href='indexp.php'>Login</a>";
    exit;
}
*/

$user = $_SESSION['userData']['name'] ?? 'Guest';
$role = $_SESSION['userData']['type'] ?? 'guest'; // guest, user, or admin

// Base query
$sql = "SELECT * FROM Swim WHERE 1=1";
$params = [];

// Handle search filters
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!empty($_POST['eventName'])) {
        $sql .= " AND eventName LIKE ?";
        $params[] = '%' . $_POST['eventName'] . '%';
    }
    if (!empty($_POST['meetName'])) {
        $sql .= " AND meetName LIKE ?";
        $params[] = '%' . $_POST['meetName'] . '%';
    }
    if (!empty($_POST['meetDate'])) {
        $sql .= " AND meetDate LIKE ?";
        $params[] = '%' . $_POST['meetDate'] . '%';
    }
    if (!empty($_POST['swimmerID'])) {
        $sql .= " AND swimmerID = ?";
        $params[] = $_POST['swimmerID'];
    }
    if (!empty($_POST['time'])) {
        $sql .= " AND time LIKE ?";
        $params[] = '%' . $_POST['time'] . '%';
    }
}

// Prepare and execute the query
$stmt = $conn->prepare($sql);

// Bind the parameters dynamically
if (!empty($params)) {
    $types = str_repeat("s", count($params)); // All parameters as strings
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$filteredSwims = $result->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Search Swims</title>
</head>
<body>
    <h1>Search Swims</h1>

    <form method="POST">
        <input type="text" name="eventName" placeholder="Search by Event Name" value="<?= htmlspecialchars($_POST['eventName'] ?? '') ?>">
        <input type="text" name="meetName" placeholder="Search by Meet Name" value="<?= htmlspecialchars($_POST['meetName'] ?? '') ?>">
        <input type="text" name="meetDate" placeholder="Search by Meet Date (YYYY-MM-DD)" value="<?= htmlspecialchars($_POST['meetDate'] ?? '') ?>">
        <input type="text" name="swimmerID" placeholder="Search by Swimmer ID" value="<?= htmlspecialchars($_POST['swimmerID'] ?? '') ?>">
        <input type="text" name="time" placeholder="Search by Time" value="<?= htmlspecialchars($_POST['time'] ?? '') ?>">
        <button type="submit">Search</button>
    </form>

    <table border="1">
        <thead>
            <tr>
                <th>Event Name</th>
                <th>Meet Name</th>
                <th>Meet Date</th>
                <th>Swimmer ID</th>
                <th>Time</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($filteredSwims)): ?>
                <tr>
                    <td colspan="5">No results found</td>
                </tr>
            <?php else: ?>
                <?php foreach ($filteredSwims as $swim): ?>
                    <tr>
                        <td><?= htmlspecialchars($swim['eventName']) ?></td>
                        <td><?= htmlspecialchars($swim['meetName']) ?></td>
                        <td><?= htmlspecialchars($swim['meetDate']) ?></td>
                        <td><?= htmlspecialchars($swim['swimmerID']) ?></td>
                        <td><?= htmlspecialchars($swim['time']) ?></td>
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

<?php
$conn->close();
?>
