<?php
session_start();

// Temporary bypass for development (remove comments below to re-enable login requirement)
/*
if (!isset($_SESSION['loggedIN']) || !$_SESSION['loggedIN']) {
    echo "You are not logged in. <a href='indexp.php'>Login</a>";
    exit;
}
*/

$user = $_SESSION['userData']['name'] ?? 'Guest';
$role = $_SESSION['userData']['type'] ?? 'guest'; // guest, user, or admin

// Mock data for swims (you can replace this with actual database data)
$swims = [
    ['eventName' => '100 Back', 'meetName' => 'Bay Classic', 'meetDate' => '20240909'],
    ['eventName' => '100 Breast', 'meetName' => 'Bay Classic', 'meetDate' => '20240909'],
    ['eventName' => '500 Free', 'meetName' => 'Conference Finals', 'meetDate' => '20241120'],
    ['eventName' => '100 Fly', 'meetName' => 'Holiday Splash', 'meetDate' => '20241220'],
    ['eventName' => '200 Free', 'meetName' => 'Holiday Splash', 'meetDate' => '20241220'],
    ['eventName' => '100 Free', 'meetName' => 'State Champs', 'meetDate' => '20241110'],
    ['eventName' => '50 Free', 'meetName' => 'State Champs', 'meetDate' => '20241110'],
    ['eventName' => '200 IM', 'meetName' => 'Winter Invite', 'meetDate' => '20241211'],
];

// Handle search filters
$filteredSwims = $swims;
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $searchEvent = $_POST['eventName'] ?? '';
    $searchMeet = $_POST['meetName'] ?? '';
    $searchDate = $_POST['meetDate'] ?? '';

    $filteredSwims = array_filter($swims, function($swim) use ($searchEvent, $searchMeet, $searchDate) {
        return (empty($searchEvent) || stripos($swim['eventName'], $searchEvent) !== false) &&
               (empty($searchMeet) || stripos($swim['meetName'], $searchMeet) !== false) &&
               (empty($searchDate) || stripos($swim['meetDate'], $searchDate) !== false);
    });
}
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
        <input type="text" name="meetDate" placeholder="Search by Meet Date" value="<?= htmlspecialchars($_POST['meetDate'] ?? '') ?>">
        <button type="submit">Search</button>
    </form>

    <table border="1">
        <thead>
            <tr>
                <th>Event Name</th>
                <th>Meet Name</th>
                <th>Meet Date</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($filteredSwims)): ?>
                <tr>
                    <td colspan="3">No results found</td>
                </tr>
            <?php else: ?>
                <?php foreach ($filteredSwims as $swim): ?>
                    <tr>
                        <td><?= htmlspecialchars($swim['eventName']) ?></td>
                        <td><?= htmlspecialchars($swim['meetName']) ?></td>
                        <td><?= htmlspecialchars($swim['meetDate']) ?></td>
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
