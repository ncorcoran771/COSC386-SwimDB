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
    $swimName = mysqli_real_escape_string($conn, $_POST['swimName']);
    $swimmerName = mysqli_real_escape_string($conn, $_POST['swimmerName']);

    // Prepare the SQL query to delete the swim record
    $query = "DELETE FROM Swim WHERE swimName = '$swimName' AND swimmerName = '$swimmerName'";
    
    // Execute the query
    $result = mysqli_query($conn, $query);

    // Check if deletion was successful
    if ($result) {
        echo "<p>✅ Swim deleted successfully.</p>";
    } else {
        echo "<p>❌ Delete failed: " . mysqli_error($conn) . "</p>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Delete Swim</title>
</head>
<body>
    <h2>Delete Swim</h2>
    <form method="post">
        Swim Name: <input type="text" name="swimName" required><br><br>
        Swimmer Name: <input type="text" name="swimmerName" required><br><br>
        <input type="submit" value="Delete">
    </form>
    <p><a href="home.php">Back to Home</a> | <a href="logout.php">Logout</a></p>
</body>
</html>
