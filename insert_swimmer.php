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

// If the user is admin (or guest elevated to admin), allow them to insert swimmers
if ($role === 'admin') {
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $name = $_POST['name'];
        $powerIndex = $_POST['powerIndex'];
        $gender = $_POST['gender'];
        $hometown = $_POST['hometown'];
        $team = $_POST['team'];

        $sql = "INSERT INTO Swimmer (name, powerIndex, gender, hometown, team)
                VALUES ('$name', '$powerIndex', '$gender', '$hometown', '$team')";

        if ($conn->query($sql) === TRUE) {
            echo "New swimmer added successfully.";
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    }
} else {
    // Only show this message if you're planning to remove the guest admin access later
    echo "You do not have permission to add swimmer records. Please log in as an admin.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Insert Swimmer</title>
</head>
<body>
    <h1>Welcome <?= htmlspecialchars($user) ?>! Insert Swimmer</h1>

    <?php if ($role === 'admin'): ?>
    <form method="post">
        Name: <input type="text" name="name" required><br>
        Power Index: <input type="text" name="powerIndex" required><br>
        Gender: <input type="text" name="gender" required><br>
        Hometown: <input type="text" name="hometown" required><br>
        Team: <input type="text" name="team" required><br>
        <button type="submit">Insert Swimmer</button>
    </form>
    <?php else: ?>
        <!-- Re-enable restriction later by uncommenting this line -->
        <!-- <p>Only admins can add new swimmer records.</p> -->
    <?php endif; ?>
    
    <a href="home.php">Back to Home</a>
</body>
</html>
