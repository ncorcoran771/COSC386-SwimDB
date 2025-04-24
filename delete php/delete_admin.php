<?php
session_start();
include('DB.php'); // Ensure DB connection

$role = $_SESSION['role'] ?? 'guest';
$user = $_SESSION['userData']['name'] ?? 'Guest';

// Temporary admin elevation for guest testing
if ($role === 'guest') {
    $role = 'admin';
}

if ($role === 'admin') {
    if (isset($_GET['adminID'])) {
        $adminID = $_GET['adminID'];

        $query = "DELETE FROM Admin WHERE adminID = $adminID";
        if ($conn->query($query)) {
            echo "Admin deleted successfully.";
        } else {
            echo "Error: " . $conn->error;
        }
    }
} else {
    echo "You do not have permission to delete admin records. Please log in as an admin.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delete Admin</title>
</head>
<body>
    <h1>Delete Admin</h1>

    <?php if ($role === 'admin'): ?>
    <form method="GET">
        <label for="adminID">Admin ID:</label><br>
        <input type="number" name="adminID" required><br><br>
        <input type="submit" value="Delete Admin">
    </form>
    <?php endif; ?>

    <p><a href="home.php"> Back to Home</a></p>
</body>
</html>
