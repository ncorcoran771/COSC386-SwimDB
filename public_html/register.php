<?php
session_start();
$conn = mysqli_connect("localhost", "eknights1", "eknights1", "athleticsRecruitingDB");
if (!$conn) die("Connection failed: " . mysqli_connect_error());

$userID = $_POST['userID'] ?? '';
if (empty($userID)) {
    die("User ID is required.");
}

// Change 'Swimmer' to 'User' here and update the corresponding ID field.
$tables = ['User' => 'userID', 'Administrator' => 'adminID'];
$found = false;

foreach ($tables as $table => $idField) {
    $query = "SELECT * FROM $table WHERE $idField = '$userID'";
    $result = mysqli_query($conn, $query);
    if (mysqli_num_rows($result) === 1) {
        $found = true;
        $newPass = bin2hex(random_bytes(4)); // Generate a new random password
        $hashed = hash('sha256', $newPass); // Hash the password using SHA256
        $update = "UPDATE $table SET password = '$hashed' WHERE $idField = '$userID'";
        mysqli_query($conn, $update); // Execute the update query
        echo "<h2>Password reset!</h2>";
        echo "Your new password is: <strong>$newPass</strong><br>";
        echo "<a href='indexp.php'>Return to Login</a>";
        break;
    }
}

if (!$found) {
    echo "<h2>User ID not found.</h2>";
    echo "<a href='indexp.php'>Return to Login</a>";
}

// Close the database connection
mysqli_close($conn);
?>

