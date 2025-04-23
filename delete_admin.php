<?php
session_start();
include('DB.php'); // Ensure DB connection

// Default to 'guest' if not logged in
$role = $_SESSION['role'] ?? 'guest'; // Default to 'guest' if not logged in
$user = $_SESSION['userData']['name'] ?? 'Guest'; // Get user name from session

// For testing purposes, we allow guest to perform admin actions
if ($role === 'guest') {
    $role = 'admin'; // Temporarily elevate guest to admin for testing
}

// If the user is admin (or guest elevated to admin), allow them to delete admins
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
    // Only show this message if you're planning to remove the guest admin access later
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
    <?php else: ?>
        <!-- Re-enable restriction later by uncommenting this line -->
        <!-- <p>Only admins can delete admin records.</p> -->
    <?php endif; ?>
</body>
</html>
