<?php
require_once 'config.php';
require_once 'db.php';

// Sanitize input to prevent SQL injection
function sanitize($input) {
    global $conn;
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim($conn->real_escape_string($input)));
}

// Display messages to user
function showMessage($message, $isError = false) {
    $class = $isError ? 'error' : 'message';
    return "<div class='$class'>$message</div>";
}

// Redirect helper
function redirect($url, $message = '') {
    if (!empty($message)) {
        $_SESSION['message'] = $message;
    }
    header("Location: $url");
    exit;
}

// Convert time string (mm:ss:ms) to seconds
function timeToSeconds($timeStr) {
    if (empty($timeStr)) return 0;
    $parts = explode(':', $timeStr);
    return ($parts[0] * 60) + $parts[1] + ($parts[2] / 100);
}

// Convert seconds to time string
function secondsToTime($seconds) {
    $minutes = floor($seconds / 60);
    $secs = $seconds % 60;
    $ms = round(($secs - floor($secs)) * 100);
    return sprintf("%d:%02d:%02d", $minutes, floor($secs), $ms);
}
?>