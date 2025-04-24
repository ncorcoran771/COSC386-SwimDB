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
        // Get form data
        $name = $_POST['name'];
        $role = $_POST['role'];

        // Validate and sanitize inputs
        $name = mysqli_real_escape_string($conn, $name);
        $role = mysqli_real_escape_string($conn, $role);

        // Set a default password if none is provided (optional)
        $password = $_POST['password'] ?? 'defaultpassword'; // Default password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT); // Hash the password

        // Insert query using prepared statements
        $sql = "INSERT INTO Admin (name, password, role) VALUES (?, ?, ?)";

        // Prepare the statement
        if ($stmt = $conn->prepare($sql)) {
            // Bind parameters to the prepared statement
            $stmt->bind_param("sss", $name, $hashedPassword, $role);

            // Execute the query
            if ($stmt->execute()) {
                echo "✅ New admin added successfully.";
            } else {
                echo "❌ Error: " . $stmt->error;
            }

            // Close the statement
            $stmt->close();
        } else {
            echo "❌ Error preparing query: " . $conn->error;
        }
    }
} else {
    echo "❌ You do not have permission to add admin records. Please log in as an admin.";
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
        <label for="name">Name:</label>
        <input type="text" name="name" required><br><br>

        <label for="password">Password:</label>
        <input type="password" name="password" required><br><br>

        <label for="role">Role (admin/user):</label>
        <input type="text" name="role" required><br><br>

        <button type="submit">Insert Admin</button>
    </form>
    <?php else: ?>
        <!-- Only show this message for non-admins -->
        <p>You must be logged in as an admin to add new admin records.</p>
    <?php endif; ?>
    
    <a href="home.php">Back to Home</a>
</body>
</html>
