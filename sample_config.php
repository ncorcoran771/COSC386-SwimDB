<?php
// config.php

// Database configuration
$host = 'localhost';        // or the IP address of your MySQL server
$dbname = 'your_database_name';
$username = 'your_database_user';
$password = 'your_database_password';

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
