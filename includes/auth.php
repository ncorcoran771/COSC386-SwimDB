<?php
require_once 'config.php';
require_once 'db.php';
require_once 'functions.php';

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['loggedIN']) && $_SESSION['loggedIN'] === true;
}

// Check if user is admin
function isAdmin() {
    return isLoggedIn() && $_SESSION['userData']['type'] === 'admin';
}

// Check if user is swimmer
function isSwimmer() {
    return isLoggedIn() && $_SESSION['userData']['type'] === 'swimmer';
}

// Get current user data
function getCurrentUser() {
    return $_SESSION['userData'] ?? ['name' => 'Guest', 'type' => 'guest'];
}

// Handle login (temporary development version like in login.php)
function loginUser($userID, $password, $userType) {
    if($userType === 'swimmer'){ 
        // currently if you're ID'd as a swimmer then you don't have any perms that a guest wouldn't have so we'll keep it at that
        $_SESSION['loggedIN'] = true;
        $_SESSION['userData'] = [
            'id' => $userID ?: 'guest',
            'type' => $userType,
            'name' => 'Guest User'
        ];
        return true;
    }
    //admin login:
    global $conn; // need to grab connection from global as we're a function
    $hashedPassword = hash('sha256', $password);
    
    $table = ($userType === 'swimmer') ? 'Swimmer' : 'Admin';   // redundant but I'll keep this here for future implementations
    $idField = ($userType === 'swimmer') ? 'swimmerID' : 'adminID';
    
    $stmt = $conn->prepare("SELECT * FROM $table WHERE $idField = ? AND password = ?");
    $stmt->bind_param('is', $userID, $hashedPassword);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $_SESSION['loggedIN'] = true;
        $_SESSION['userData'] = [
            'id' => $userID,
            'type' => $userType,
            'name' => $row['name'] ?? $userID
        ];
        return true;
    }
    return false;
}
?>