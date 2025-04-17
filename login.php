<?php
session_start();
require_once 'db_connection.php'; // include your DB connection script

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input
    $userID = trim($_POST['userID']);
    $plainPass = trim($_POST['password']);

    // Hashing should match what's in your database (use password_hash when storing)
    $hashPass = $plainPass; // We'll verify using password_verify below

    // Determine user type
    if (isset($_POST['swimmerLog'])) {
        $_SESSION['userType'] = 'swimmer';
        $table = 'Swimmer';
        $idField = 'swimmerID';
    } elseif (isset($_POST['coachLog'])) {
        $_SESSION['userType'] = 'coach';
        $table = 'Coaches';
        $idField = 'coachID';
    } elseif (isset($_POST['adminLog'])) {
        $_SESSION['userType'] = 'admin';
        $table = 'Administrator';
        $idField = 'adminID';
    } else {
        die("User type not specified.");
    }

    // Prepare and execute query
    $stmt = $conn->prepare("SELECT * FROM $table WHERE $idField = ?");
    $stmt->bind_param("s", $userID);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check for exactly one user
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Verify password
        if (password_verify($plainPass, $user['password'])) {
            $_SESSION['user'] = $userID;
            $_SESSION['loggedIN'] = true;

            // Optionally store other user data in session
            $_SESSION['userData'] = $user;

            header("Location: home.php"); // Redirect to a dashboard or home page
            exit;
        } else {
            echo "Incorrect password.";
        }
    } else {
        echo "User not found or multiple users found.";
    }

    $stmt->close();
    $conn->close();
}
?>

