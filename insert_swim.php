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
    $event = mysqli_real_escape_string($conn, $_POST['event']);
    $time = mysqli_real_escape_string($conn, $_POST['time']);
    $meetName = mysqli_real_escape_string($conn, $_POST['meetName']);

    // Prepare the SQL query to insert the swim record
    $query = "INSERT INTO Swim (swimName, swimmerName, event, time, meetName) VALUES ('$swimName', '$swimmerName', '$event', '$time', '$meetName')";
    
    // Execute the query
    $result = mysqli_query($conn, $query);

    // Check if insertion was successful
    if ($result) {
        echo "<p>✅ Swim inserted successfully.</p>";
    } else {
        echo "<p>❌ Insert failed: " . mysqli_error($conn) . "</p>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Insert Swim</title>
</head>
<body>
    <h2>Insert Swim</h2>
    <form method="post">
        Swim Name: <input type="text" name="swimName" required><br><br>
        Swimmer Name: <input type="text" name="swimmerName" required><br><br>
        Event: <input type="text" name="event" required><br><br>
        Time: <input type="text" name="time" required><br><br>
        Meet Name: <input type="text" name="meetName" required><br><br>
        <input type="submit" value="Insert">
    </form>
    <p><a href="home.php">Back to Home</a> | <a href="logout.php">Logout</a></p>
</body>
</html>
