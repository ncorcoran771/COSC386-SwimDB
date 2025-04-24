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

// Mock data for teams (you can replace this with actual database data)
$teams = [
    ['teamName' => 'Barracudas', 'location' => 'Baytown', 'confName' => 'West Coast', 'confState' => 'WC'],
    ['teamName' => 'Dolphins', 'location' => 'Seaside', 'confName' => 'Gulf Coast', 'confState' => 'GC'],
    ['teamName' => 'Sharks', 'location' => 'Oceanville', 'confName' => 'East Coast', 'confState' => 'EC'],
];

// Handle search filters
$filteredTeams = $teams;
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $searchTeam = $_POST['teamName'] ?? '';
    $searchLocation = $_POST['location'] ?? '';
    $searchConfName = $_POST['confName'] ?? '';
    $searchConfState = $_POST['confState'] ?? '';

    $filteredTeams = array_filter($teams, function($team) use ($searchTeam, $searchLocation, $searchConfName, $searchConfState) {
        return (empty($searchTeam) || stripos($team['teamName'], $searchTeam) !== false) &&
               (empty($searchLocation) || stripos($team['location'], $searchLocation) !== false) &&
               (empty($searchConfName) || stripos($team['confName'], $searchConfName) !== false) &&
               (empty($searchConfState) || stripos($team['confState'], $searchConfState) !== false);
    });
}
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
        <input type="text" name="teamName" placeholder="Search by Team Name" value="<?= htmlspecialchars($_POST['teamName'] ?? '') ?>">
        <input type="text" name="location" placeholder="Search by Location" value="<?= htmlspecialchars($_POST['location'] ?? '') ?>">
        <input type="text" name="confName" placeholder="Search by Conference Name" value="<?= htmlspecialchars($_POST['confName'] ?? '') ?>">
        <input type="text" name="confState" placeholder="Search by Conference State" value="<?= htmlspecialchars($_POST['confState'] ?? '') ?>">
        <button type="submit">Search</button>
    </form>

    <table>
        <thead>
            <tr>
                <th>Team Name</th>
                <th>Location</th>
                <th>Conference Name</th>
                <th>Conference State</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($filteredTeams)): ?>
                <tr>
                    <td colspan="4">No results found</td>
                </tr>
            <?php else: ?>
                <?php foreach ($filteredTeams as $team): ?>
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
