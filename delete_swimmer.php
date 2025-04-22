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

// If the user is admin (or guest elevated to admin), allow them to delete swimmers
if ($role === 'admin') {
    if (isset($_GET['swimmerID'])) {
        $swimmerID = $_GET['swimmerID'];

        // Prepare and execute the deletion query with parameter binding to prevent SQL injection
        if ($stmt = $conn->prepare("DELETE FROM Swimmer WHERE swimmerID = ?")) {
            $stmt->bind_param('i', $swimmerID); // 'i' means integer parameter
            if ($stmt->execute()) {
                echo "Swimmer deleted successfully.";
            } else {
                echo "Error: " . $stmt->error;
            }

            $stmt->close(); // Close prepared statement
        } else {
            echo "Error with the database query.";
        }
    }
} else {
    // Only show this message if you're planning to remove the guest admin access later
    echo "You do not have permission to delete swimmer records. Please log in as an admin.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delete Swimmer</title>
</head>
<body>
    <h1>Delete Swimmer</h1>

    <?php if ($role === 'admin'): ?>
    <form method="GET">
        <label for="swimmerID">Swimmer ID:</label><br>
        <input type="number" name="swimmerID" required><br><br>
        <input type="submit" value="Delete Swimmer">
    </form>
    <?php else: ?>
        <!-- Re-enable restriction later by uncommenting this line -->
        <!-- <p>Only admins can delete swimmer records.</p> -->
    <?php endif; ?>
</body>
</html>
