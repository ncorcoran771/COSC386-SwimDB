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


// Convert seconds to time string
function secondsToTime($seconds) {
    // Ensure $seconds is treated as float
    $seconds = (float)$seconds;
    
    $minutes = floor($seconds / 60);
    // Use fmod instead of % for floating point modulo
    $secs = fmod($seconds, 60);
    $ms = round(($secs - floor($secs)) * 100);
    return sprintf("%d:%02d:%02d", $minutes, floor($secs), $ms);
}

function timeToSeconds($timeStr) {
    if (empty($timeStr)) {
        return 0.0; // Or null, depending on how you want to handle empty input
    }
    $parts = explode(':', $timeStr);
    // Expecting 'mm:ss:ms' -> 3 parts
    if (count($parts) !== 3) {
        // Invalid format
        return false; // Indicate an invalid conversion
    }
    $minutes = (int)$parts[0];
    $seconds = (int)$parts[1];
    // Assuming milliseconds are stored as hundredths (e.g., 45 means 0.45 seconds)
    $milliseconds = (int)$parts[2];

    // Basic validation: seconds and milliseconds parts should be 0-99
    if ($seconds < 0 || $seconds > 59 || $milliseconds < 0 || $milliseconds > 99) {
        return false; // Indicate invalid values
    }

    return ($minutes * 60) + $seconds + ($milliseconds / 100.0);
}
function formatTime($totalSeconds) {
    // Ensure input is numeric and non-negative
    if (!is_numeric($totalSeconds) || $totalSeconds < 0) {
        return "N/A"; // Or some other indicator for invalid data
    }

    // Get the whole number of seconds
    $wholeSeconds = floor($totalSeconds);

    // Get the milliseconds part (as hundredths)
    // Calculate the fractional part, multiply by 100, and round to nearest integer
    $milliseconds = round(($totalSeconds - $wholeSeconds) * 100);

    // Calculate minutes
    $minutes = floor($wholeSeconds / 60);

    // Calculate remaining seconds after extracting minutes
    $seconds = $wholeSeconds % 60;

    // Format the output string as mm:ss:ms
    // Use sprintf with %02d to pad with leading zeros if needed
    return sprintf("%02d:%02d:%02d", $minutes, $seconds, $milliseconds);
}
?>
