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

// If the user is admin (or guest elevated to admin), allow them to insert admins
if ($role === 'admin') {
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $name = $_POST['name'];
        $role = $_POST['role'];

        $sql = "INSERT INTO Admin (name, role) VALUES ('$name', '$role')";

        if ($conn->query($sql) === TRUE) {
            echo "New admin added successfully.";
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    }
} else {
    // Only show this message if you're planning to remove the guest admin access later
    echo "You do not have permission to add admin records. Please log in as an admin.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Insert Admin</title>
</head>
<body>
    <h1>Insert Admin</h1>

    <?php if ($role === 'admin'): ?>
    <form method="post">
        Name: <input type="text" name="name" required><br>
        Role: <input type="text" name="role" required><br>
        <button type="submit">Insert Admin</button>
    </form>
    <?php else: ?>
        <!-- Re-enable restriction later by uncommenting this line -->
        <!-- <p>Only admins can add new admin records.</p> -->
    <?php endif; ?>
    
    <a href="home.php">Back to Home</a>
</body>
</html>
