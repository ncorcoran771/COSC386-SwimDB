<?php
$servername = "localhost";
$username = "eknights1";
$password = "eknights1";
$dbname = "eknights1DB";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
