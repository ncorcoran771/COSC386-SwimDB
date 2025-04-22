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
$role = $_SESSION['userData']['type'] ?? 'guest'; // Get role from session data
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Swim Data Home</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #e0f7fa;
            padding: 20px;
        }
        h1, h2 {
            color: #00796b;
        }
        .nav {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 20px;
        }
        .nav a {
            padding: 10px 15px;
            background: #00796b;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        .nav a:hover {
            background-color: #004d40;
        }
    </style>
</head>
<body>
    <h1>Welcome to Swim Data, <?= htmlspecialchars($user) ?>!</h1>
    <p>Choose what you would like to do:</p>

    <?php if ($role === 'swimmer' || $role === 'admin'): ?>
        <div class="nav">
            <h2>Swimmer Options</h2>
            <a href="search_swimmer.php">Search Swimmer</a>
        </div>
    <?php endif; ?>

    <?php if ($role === 'admin'): ?>
        <div class="nav">
            <h2>Administrator Tools</h2>
            <a href="insert_swimmer.php">Insert Swimmer</a>
            <a href="delete_swimmer.php">Delete Swimmer</a>
            <a href="search_admin.php">Search Admin</a>
            <a href="insert_admin.php">Insert Admin</a>
            <a href="delete_admin.php">Delete Admin</a>
        </div>

        <div class="nav">
            <h2>Other Tables</h2>
            <a href="view_conferences.php">View Conferences</a>
            <a href="view_meets.php">View Meets</a>
            <a href="view_swims.php">View Swims</a>
            <a href="view_teams.php">View Teams</a>
        </div>
    <?php endif; ?>

    <p><a href="logout.php">Logout</a></p>
</body>
</html>
