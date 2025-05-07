<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

$user = getCurrentUser();
$username = htmlspecialchars($user['name']);
$role = $user['type'];

include 'includes/header.php';
include 'includes/sidebar.php';
?>
<body>
    <div class="main">
        <div class="container">
            <?php
            // Display messages if any
            if (isset($_SESSION['message'])) {
                echo showMessage($_SESSION['message']);
                unset($_SESSION['message']);
            }
            ?>
            <h1>Welcome to Swim Data!</h1>
            <p>Whether you're a coach, athlete, recruiter, or fan, Swim Data 
                makes it easy to search and explore detailed information on swimmers, 
                teams, conferences, meets, and individual performances. Track season 
                progress, compare times, and dive deep into swimming statistics with 
                our intuitive, user-friendly platform.</p>
            
            <?php if ($role === 'admin'): ?>
                <h2>Administrator Tools</h2>
                <div class="nav">
                    <!-- New unified management interfaces -->
                    <a href="swimmer_management.php">Swimmer Management</a>
                    <a href="team_management.php">Team Management</a>
                    
                    <!-- Keep existing links for backward compatibility -->
                    <a href="operations.php?action=insert&entity=swimmer">Add Swimmer</a>
                    <a href="operations.php?action=delete&entity=swimmer">Delete Swimmer</a>
                    <a href="operations.php?action=insert&entity=swim">Add Swim Times</a>
                    <a href="operations.php?action=search&entity=admin">Search Admin</a>
                    <a href="operations.php?action=insert&entity=admin">Add Admin</a>
                    <a href="operations.php?action=delete&entity=admin">Delete Admin</a>
                </div>
                
            <?php endif; ?>

            <br>

            <?php if (isLoggedIn()): ?>
                <a href="auth.php?action=logout">Log Out</a>
            <?php endif; ?>
            <?php if (!(isLoggedIn())): ?>
                <a href="index.php">Login</a>
            <?php endif; ?>
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>
</body>
