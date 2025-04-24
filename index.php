<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Swim Data Login</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="login-form">
        <h2>Swim Data Login</h2>
        <?php
        // Display messages if any
        if (isset($_SESSION['message'])) {
            echo showMessage($_SESSION['message']);
            unset($_SESSION['message']);
        }
        ?>
        <form method="post" action="auth.php">
            <input type="hidden" name="action" value="login">
            <input type="text" name="userID" placeholder="User ID">
            <input type="password" name="plainPassword" placeholder="Password">
            <input type="submit" name="swimmerLog" value="Swimmer">
            <input type="submit" name="adminLog" value="Administrator">
        </form>
        <a href="auth.php?action=forgot">Forgot Password?</a>
        <a href="auth.php?action=register">Create Account</a>
    </div>
</body>
</html>