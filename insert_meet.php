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
    $date = mysqli_real_escape_string($conn, $_POST['date']);

    // Prepare the SQL query to insert the meet
    $query = "INSERT INTO Meet (meetName, location, date) VALUES ('$meetName', '$location', '$date')";
    
    // Execute the query
    $result = mysqli_query($conn, $query);

    // Check if insertion was successful
    if ($result) {
        echo "<p>✅ Meet inserted successfully.</p>";
    } else {
        echo "<p>❌ Insert failed: " . mysqli_error($conn) . "</p>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Insert Meet</title>
</head>
<body>
    <h2>Insert Meet</h2>
    <form method="post">
        Meet Name: <input type="text" name="meetName" required><br><br>
        Location: <input type="text" name="location" required><br><br>
        Date: <input type="date" name="date" required><br><br>
        <input type="submit" value="Insert">
    </form>
    <p><a href="home.php">Back to Home</a> | <a href="logout.php">Logout</a></p>
</body>
</html>
