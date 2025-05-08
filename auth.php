<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'login':
        // Process login
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userID = sanitize($_POST['userID'] ?? '');
            $password = $_POST['plainPassword'] ?? '';
            $userType = isset($_POST['swimmerLog']) ? 'swimmer' : (isset($_POST['adminLog']) ? 'admin' : 'guest');

            // double checking that the ID is a valid integer, don't care either way if we're auto-passing them for not being an admin
            if(filter_var($userID, FILTER_VALIDATE_INT) || $userType === 'swimmer')
                if (loginUser($userID, $password, $userType))
                    redirect('home.php');
                else
                    redirect('index.php?action=login', 'Invalid credentials');
            else
                redirect('index.php?action=login', 'ID must be an Integer');
        }
        break;
        
    case 'register':
        // Display registration form
        include 'includes/header.php';
        ?>
        <div class="login-form">
            <h2>Create Account</h2>
            <form method="post" action="auth.php">
                <input type="hidden" name="action" value="register_process">
                <input type="text" name="userID" placeholder="Choose a User ID" required>
                <input type="password" name="password" placeholder="Choose a Password" required>
                <select name="role" required>
                    <option value="">Select Role</option>
                    <option value="Swimmer">Swimmer</option>
                    <option value="Administrator">Administrator</option>
                </select>
                <input type="submit" value="Create Account">
            </form>
            <a href="index.php">Back to Login</a>
        </div>
        <?php
        include 'includes/footer.php';
        break;
        
    case 'register_process':
        // Process registration (from register.php)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userID = sanitize($_POST['userID'] ?? '');
            $password = $_POST['password'] ?? '';
            $role = strtolower($_POST['role'] ?? '');
            
            if (empty($userID) || empty($password) || empty($role)) {
                redirect('auth.php?action=register', 'All fields are required');
            }
            if (filter_var($userID, FILTER_VALIDATE_INT) === false)
                redirect('auth.php?action=register', 'ID must be an integer');

            $hashedPassword = hash('sha256', $password);
            
            if ($role === 'swimmer') {
                $table = 'Swimmer';
                $idField = 'swimmerID';
            } else {
                $table = 'Admin';
                $idField = 'adminID';
            }
            
            // Check if exists
            $stmt = $conn->prepare("SELECT * FROM $table WHERE $idField = ?");
            $stmt->bind_param('i', $userID);
            $stmt->execute();
            
            if ($stmt->get_result()->num_rows > 0) {
                redirect('auth.php?action=register', 'User ID already exists');
            }
            
            // Create user
            $stmt = $conn->prepare("INSERT INTO $table ($idField, password) VALUES (?, ?)");
            $stmt->bind_param('ss', $userID, $hashedPassword);
            
            if ($stmt->execute()) {
                redirect('index.php', 'Account created successfully');
            } else {
                redirect('auth.php?action=register', 'Error creating account: ' . $stmt->error);
            }
        }
        break;
        
    case 'forgot':
        // Display forgot password form
        include 'includes/header.php';
        ?>
        <div class="login-form">
            <h2>Forgot Password</h2>
            <form method="post" action="auth.php">
                <input type="hidden" name="action" value="reset">
                <input type="text" name="userID" placeholder="Enter your User ID" required>
                <input type="submit" value="Reset Password">
            </form>
            <a href="index.php">Back to Login</a>
        </div>
        <?php
        include 'includes/footer.php';
        break;
        
    case 'reset':
        // Process password reset (from reset_password.php)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userID = sanitize($_POST['userID'] ?? '');
            
            if (empty($userID)) {
                redirect('auth.php?action=forgot', 'User ID is required');
            }
            
            $tables = ['Swimmer' => 'swimmerID', 'Admin' => 'adminID'];
            $found = false;
            
            foreach ($tables as $table => $idField) {
                $stmt = $conn->prepare("SELECT * FROM $table WHERE $idField = ?");
                $stmt->bind_param('s', $userID);
                $stmt->execute();
                
                if ($stmt->get_result()->num_rows === 1) {
                    $found = true;
                    $newPassword = bin2hex(random_bytes(4)); // Generate random password
                    $hashedPassword = hash('sha256', $newPassword);
                    
                    $updateStmt = $conn->prepare("UPDATE $table SET password = ? WHERE $idField = ?");
                    $updateStmt->bind_param('ss', $hashedPassword, $userID);
                    $updateStmt->execute();
                    
                    // Display the new password
                    include 'includes/header.php';
                    ?>
                    <div class="login-form">
                        <h2>Password Reset</h2>
                        <p>Your new password is: <strong><?= $newPassword ?></strong></p>
                        <p>Please login with this password and then change it.</p>
                        <a href="index.php">Return to Login</a>
                    </div>
                    <?php
                    include 'includes/footer.php';
                    break;
                }
            }
            
            if (!$found) {
                redirect('auth.php?action=forgot', 'User ID not found');
            }
        }
        break;
        
    case 'logout':
        // Handle logout (from logout.php)
        session_unset();
        session_destroy();
        redirect('index.php', 'You have been logged out');
        break;
        
    default:
        // Invalid action
        redirect('index.php');
}
?>