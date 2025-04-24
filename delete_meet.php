<?php
session_start();

// Include the DB connection file
include('DB.php'); 

// Check if user is admin
if ($_SESSION['userData']['type'] !== 'admin') {
    echo "Access denied.";
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and escape input to prevent SQL injection
    $meetName = mysqli_real_escape_string($conn, $_POST['meetName']);
    $location = mysqli_real_escape_string($conn, $_POST['location']);

    // Prepare the SQL query to delete the meet
    $query = "DELETE FROM Meet WHERE meetName = '$meetName' AND location = '$location'";
    
    // Execute the query
    $result = mysqli_query($conn, $query);

    // Check if deletion was successful
    if ($result) {
        echo "<p>✅ Meet deleted successfully.</p>";
    } else {
        echo "<p>❌ Delete failed: " . mysqli_error($conn) . "</p>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Delete Meet</title>
</head>
<body>
    <h2>Delete Meet</h2>
    <form method="post">
        Meet Name: <input type="text" name="meetName" required><br><br>
        Location: <input type="text" name="location" required><br><br>
        <input type="submit" value="Delete">
    </form>
    <p><a href="home.php">Back to Home</a> | <a href="logout.php">Logout</a></p>
</body>
</html>
