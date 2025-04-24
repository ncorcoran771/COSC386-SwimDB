<?php
session_start();

// Include DB.php for database connection
include('DB.php');

// Check if the user is logged in (optional check)
if (!isset($_SESSION['loggedIN']) || !$_SESSION['loggedIN']) {
    echo "You are not logged in. <a href='indexp.php'>Login</a>";
    exit;
}

// Handle form submission for deleting a swimmer
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize swimmerID
    $swimmerID = mysqli_real_escape_string($conn, $_POST['swimmerID']);

    // SQL to delete the swimmer
    $sql = "DELETE FROM Swimmer WHERE swimmerID = ?";
    
    // Prepare and bind the statement
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $swimmerID);

    // Execute the query and check for success
    if ($stmt->execute()) {
        echo "Swimmer deleted successfully!";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
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

    <form method="POST" action="delete_swimmer.php">
        <label>Swimmer ID to Delete:</label><br>
        <input type="text" name="swimmerID" required><br><br>
        <button type="submit">Delete Swimmer</button>
    </form>

    <div>
        <a href="home.php">Back to Home</a> | 
        <a href="logout.php">Logout</a>
    </div>
</body>
</html>

<?php
$conn->close();
?>
