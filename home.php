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
                <div class="admin-tools">
                    <h3>Data Management</h3>
                    <div class="admin-grid">
                        <a href="swimmer_management.php" class="admin-link-box">
                            <h4>Swimmer Management</h4>
                            <p>Add, edit, search, and delete swimmers</p>
                        </a>
                        <a href="team_management.php" class="admin-link-box">
                            <h4>Team Management</h4>
                            <p>Add, edit, search, and delete teams</p>
                        </a>
                        <a href="conference_management.php" class="admin-link-box">
                            <h4>Conference Management</h4>
                            <p>Add, edit, search, and delete conferences</p>
                        </a>
                        <a href="meet_management.php" class="admin-link-box">
                            <h4>Meet Management</h4>
                            <p>Add, edit, search, and delete meets</p>
                        </a>
                        <a href="swim_management.php" class="admin-link-box">
                            <h4>Swim Record Management</h4>
                            <p>Add, edit, search, and delete swim records</p>
                        </a>
                        <a href="operations.php?action=search&entity=admin" class="admin-link-box">
                            <h4>Admin Management</h4>
                            <p>Add, search, and delete administrators</p>
                        </a>
                    </div>
                    
                    <h3>Quick Actions</h3>
                    <div class="quick-actions">
                        <a href="operations.php?action=insert&entity=swimmer" class="button">Add Swimmer</a>
                        <a href="operations.php?action=insert&entity=swim" class="button">Add Swim Time</a>
                        <a href="operations.php?action=insert&entity=admin" class="button">Add Admin</a>
                    </div>
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
        background-color: white; /* Pure white background */
        border-radius: 8px;
        padding: 15px;
        text-decoration: none;
        color: inherit;
        transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
        border: 3px solid #00796b; /* Thicker teal border */
        box-shadow: 0 4px 8px rgba(0,0,0,0.2); /* Stronger shadow */
    }
    
    .quick-link-box:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.2); /* Even stronger shadow on hover */
        text-decoration: none;
        background-color: #e0f7fa; /* Light teal background on hover */
        border-color: #004d40; /* Darker teal border on hover */
    }
    
    .quick-link-box h3 {
        color: #004d40; /* Very dark teal heading for maximum contrast */
        margin-top: 0;
        margin-bottom: 10px;
        font-weight: 700; /* Bold heading */
        font-size: 1.3em; /* Larger heading */
        text-shadow: 0px 0px 1px rgba(0,0,0,0.1); /* Subtle text shadow for definition */
    }
    
    .quick-link-box p {
        margin: 0;
        font-size: 1em; /* Standard size */
        color: #000000; /* Pure black text for maximum contrast */
        line-height: 1.5; /* Improved line height */
        font-weight: 500; /* Medium weight for better visibility */
    }
    
    /* Admin Tools Section */
    .admin-tools {
        margin: 20px 0 30px;
    }
    
    .admin-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 20px;
        margin: 15px 0 30px;
    }
    
    .admin-link-box {
        background-color: white;
        border-radius: 8px;
        padding: 15px;
        text-decoration: none;
        color: inherit;
        transition: transform 0.2s, box-shadow 0.2s;
        border: 2px solid #7b1fa2; /* Purple border for admin tools */
        box-shadow: 0 3px 6px rgba(0,0,0,0.16);
    }
    
    .admin-link-box:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 12px rgba(0,0,0,0.2);
        background-color: #f3e5f5; /* Light purple background on hover */
        border-color: #4a148c; /* Darker purple border on hover */
    }
    
    .admin-link-box h4 {
        color: #4a148c; /* Dark purple heading */
        margin-top: 0;
        margin-bottom: 8px;
        font-weight: 600;
        font-size: 1.1em;
    }
    
    .admin-link-box p {
        margin: 0;
        font-size: 0.9em;
        color: #444;
    }
    
    .quick-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 10px;
    }
    
    .quick-actions .button {
        background-color: #7b1fa2; /* Purple background for admin action buttons */
        color: white;
        padding: 8px 15px;
        border-radius: 5px;
        text-decoration: none;
        transition: background-color 0.2s;
    }
    
    .quick-actions .button:hover {
        background-color: #4a148c; /* Darker purple on hover */
    }
    </style>
</body>