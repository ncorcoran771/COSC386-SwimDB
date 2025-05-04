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
    // TEMPORARY LOGIN (from login.php)
    $_SESSION['loggedIN'] = true;
    $_SESSION['userData'] = [
        'id' => $userID ?: 'guest',
        'type' => $userType,
        'name' => 'Guest User'
    ];
    return true;
    
    /* COMMENTED OUT REAL LOGIN FOR FUTURE IMPLEMENTATION
    global $conn;
    $hashedPassword = hash('sha256', $password);
    
    $table = ($userType === 'swimmer') ? 'Swimmer' : 'Admin';
    $idField = ($userType === 'swimmer') ? 'swimmerID' : 'adminID';
    
    $stmt = $conn->prepare("SELECT * FROM $table WHERE $idField = ? AND password = ?");
    $stmt->bind_param('ss', $userID, $hashedPassword);
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
    */
}
?>