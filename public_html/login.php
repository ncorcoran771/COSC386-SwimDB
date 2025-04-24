<?php
session_start();

// TEMPORARY BYPASS FOR DEVELOPMENT:
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userID = $_POST['userID'] ?? '';
    $plainPass = $_POST['plainPassword'] ?? '';

    if (isset($_POST['adminLog'])) {
        $_SESSION['userType'] = 'admin';
    } else {
        $_SESSION['userType'] = 'user'; // default for anyone not admin
    }

    // TEMPORARY LOGIN: Set as logged in and assign guest data
    $_SESSION['loggedIN'] = true;
    $_SESSION['userData'] = [
        'id' => $userID ?: 'guest',
        'type' => $_SESSION['userType'],
        'name' => 'Guest User'  // Temporary Guest User name
    ];

    header("Location: home.php");
    exit;

    // REAL LOGIN CODE FOR FUTURE IMPLEMENTATION:
    /*
    $hashPassword = hash('sha256', $plainPass);
    unset($plainPass);

    $query = passQuery($_SESSION['userType'], $userID, $hashPassword);
    $output = mysqli_query($conn, $query);

    if ($user = mysqli_fetch_assoc($output)) {
        $_SESSION['loggedIN'] = true;
        $_SESSION['userData'] = [
            'id' => $userID,
            'type' => $_SESSION['userType'],
            'name' => $user['name'] ?? $userID
        ];
        header("Location: home.php");
        exit;
    } else {
        echo "<h2>Invalid ID or password.</h2>";
        echo "<a href='indexp.php'>Back to Login</a>";
    }
    */
} elseif (isset($_GET['action'])) {
    if ($_GET['action'] === 'forgot') {
        echo <<<HTML
        <h2>Forgot Password</h2>
        <form method="post" action="reset_password.php">
            <input type="text" name="userID" placeholder="Enter your User ID" required>
            <input type="submit" value="Reset Password">
        </form>
        <a href='indexp.php'>Back to Login</a>
        HTML;
    } elseif ($_GET['action'] === 'create') {
        echo <<<HTML
        <h2>Create Account</h2>
        <form method="post" action="register.php">
            <input type="text" name="userID" placeholder="Choose a User ID" required>
            <input type="password" name="plainPassword" placeholder="Choose a Password" required>
            <select name="role" required>
                <option value="">Select Role</option>
                <option value="User">User</option>
                <option value="Administrator">Administrator</option>
            </select><br><br>
            <input type="submit" value="Create Account">
        </form>
        <a href='indexp.php'>Back to Login</a>
        HTML;
    }
} else {
    header("Location: indexp.php");
    exit;
}

// Future function for authentication query
/*
function passQuery($type, $id, $hashPass) {
    $table = $type === 'admin' ? 'Administrator' : 'User';
    $idField = $type === 'admin' ? 'adminID' : 'userID';
    return "SELECT * FROM $table WHERE $idField = '$id' AND password = '$hashPass'";
}
*/
?>

