<?php
session_start();
$conn = mysqli_connect("localhost", "eknights1", "eknights1", "athleticsRecruitingDB");
if (!$conn) die("Connection failed: " . mysqli_connect_error());

$userID = $_POST['userID'] ?? '';
$plainPassword = $_POST['plainPassword'] ?? '';
$role = $_POST['role'] ?? '';

if (!$userID || !$plainPassword || !$role) {
    die("All fields are required.");
}

$hashedPassword = hash('sha256', $plainPassword);

switch (strtolower($role)) {
    case 'user':
        $table = "User";
        $idField = "userID";
        break;
    case 'administrator':
        $table = "Administrator";
        $idField = "adminID";
        break;
    default:
        die("Invalid role selected.");
}

$checkQuery = "SELECT * FROM $table WHERE $idField = '$userID'";
$result = mysqli_query($conn, $checkQuery);
if (mysqli_num_rows($result) > 0) {
    die("User ID already exists.");
}

$insertQuery = "INSERT INTO $table ($idField, password) VALUES ('$userID', '$hashedPassword')";
if (mysqli_query($conn, $insertQuery)) {
    echo "<h2>Account created successfully!</h2>";
    echo "<a href='indexp.php'>Return to Login</a>";
} else {
    echo "Error: " . mysqli_error($conn);
}
?>

