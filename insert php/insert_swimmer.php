<?php
session_start();

// Include DB.php for database connection
include('DB.php');

// Check if the user is logged in (optional check)
if (!isset($_SESSION['loggedIN']) || !$_SESSION['loggedIN']) {
    echo "You are not logged in. <a href='indexp.php'>Login</a>";
    exit;
}

// Handle form submission for inserting swimmer data
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Collect and sanitize the form data
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $powerIndex = mysqli_real_escape_string($conn, $_POST['powerIndex']);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $swimmerID = mysqli_real_escape_string($conn, $_POST['swimmerID']);
    $hometown = mysqli_real_escape_string($conn, $_POST['hometown']);
    $team = mysqli_real_escape_string($conn, $_POST['team']);

    // Insert swimmer into the database
    $sql = "INSERT INTO Swimmer (name, powerIndex, gender, swimmerID, hometown, team) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    // Prepare and bind the statement
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssssss', $name, $powerIndex, $gender, $swimmerID, $hometown, $team);

    // Execute the query and check for success
    if ($stmt->execute()) {
        echo "Swimmer inserted successfully!";
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
    <title>Insert Swimmer</title>
</head>
<body>
    <h1>Insert New Swimmer</h1>
    
    <form method="POST" action="insert_swimmer.php">
        <label>Name:</label><br>
        <input type="text" name="name" required><br><br>

        <label>Power Index:</label><br>
        <input type="text" name="powerIndex" required><br><br>

        <label>Gender:</label><br>
        <input type="text" name="gender" required><br><br>

        <label>Swimmer ID:</label><br>
        <input type="text" name="swimmerID" required><br><br>

        <label>Hometown:</label><br>
        <input type="text" name="hometown" required><br><br>

        <label>Team:</label><br>
        <input type="text" name="team" required><br><br>

        <button type="submit">Insert Swimmer</button>
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
