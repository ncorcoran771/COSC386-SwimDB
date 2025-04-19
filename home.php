<?php
session_start();

// TEMPORARY DISABLE LOGIN REQUIREMENT FOR DEVELOPMENT
// if (!isset($_SESSION['loggedIN']) || !$_SESSION['loggedIN']) {
//     echo "You are not logged in. <a href='indexp.php'>Login</a>";
//     exit;
// }

echo "<h2>Welcome to Swim Data, " . ($_SESSION['userData']['name'] ?? 'Guest') . "!</h2>";
echo "<p>User ID: " . htmlspecialchars($_SESSION['userData']['id'] ?? 'guest') . "</p>";
echo "<p>User Type: " . htmlspecialchars($_SESSION['userData']['type'] ?? 'guest') . "</p>";
echo "<a href='logout.php'>Logout</a>";
?>
