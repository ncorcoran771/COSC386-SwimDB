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
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $state = mysqli_real_escape_string($conn, $_POST['state']);

    // Prepare the SQL query
    $query = "INSERT INTO Conference (name, state) VALUES ('$name', '$state')";
    
    // Execute the query
    $result = mysqli_query($conn, $query);

    // Check if insertion was successful
    if ($result) {
        echo "<p>✅ Conference inserted successfully.</p>";
    } else {
        echo "<p>❌ Insert failed: " . mysqli_error($conn) . "</p>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Insert Conference</title>
</head>
<body>
    <h2>Insert Conference</h2>
    <form method="post">
        Conference Name: <input type="text" name="name" required><br><br>
        State: <input type="text" name="state" required><br><br>
        <input type="submit" value="Insert">
    </form>
    <p><a href="home.php">Back to Home</a> | <a href="logout.php">Logout</a></p>
</body>
</html>
