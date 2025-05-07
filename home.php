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
            
            <div class="quick-links">
                <h2>Quick Links</h2>
                <div class="link-grid">
                    <a href="view.php?entity=swimmers" class="quick-link-box">
                        <h3>Swimmers</h3>
                        <p>Browse all swimmers in the database</p>
                    </a>
                    <a href="view.php?entity=teams" class="quick-link-box">
                        <h3>Teams</h3>
                        <p>View team information and rosters</p>
                    </a>
                    <a href="event_records.php" class="quick-link-box">
                        <h3>Event Records</h3>
                        <p>See top performances for each event</p>
                    </a>
                    <a href="view.php?entity=find_recruit" class="quick-link-box">
                        <h3>Find Recruits</h3>
                        <p>Search for swimmers based on criteria</p>
                    </a>
                </div>
            </div>
            
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

    <style>
    .quick-links {
        margin: 30px 0;
    }
    
    .link-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 20px;
        margin-top: 15px;
    }
    
    .quick-link-box {
        background-color: #f5f5f5;
        border-radius: 8px;
        padding: 15px;
        text-decoration: none;
        color: inherit;
        transition: transform 0.2s, box-shadow 0.2s;
        border: 1px solid #ddd;
    }
    
    .quick-link-box:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        text-decoration: none;
        background-color: #e0f7fa;
    }
    
    .quick-link-box h3 {
        color: #00796b;
        margin-top: 0;
        margin-bottom: 10px;
    }
    
    .quick-link-box p {
        margin: 0;
        font-size: 0.9em;
        color: #555;
    }
    </style>
</body>