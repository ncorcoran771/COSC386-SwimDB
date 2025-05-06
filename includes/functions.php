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
    //check to see if we include minutes or not
    //if we do not include minutes, then ignore and process seconds and milliseconds
    //if we do include minutes, then process all three

    //no minutes
    //:xx:xx results in parts 0 being null
    //handle if parts[0] is null

    if (count($parts) == 2) {
        $minutes = 0;
        $seconds = (float)$parts[0];
        if(strlen($parts[1]) == 1) {
            $parts[2] = $parts[2] . '0';
        }
        $milliseconds = (float)$parts[1] / 100;
    }
    //with minutes
    else if (count($parts) == 3) {
        //normalize minutes digits
        if(strlen($parts[0]) == 1) {
            $parts[0] = '0' . $parts[0];
        }
        $minutes = (int)$parts[0];
        $seconds = (float)$parts[1];
        if(strlen($parts[2]) == 1) {
            $parts[2] = $parts[2] . '0';
        }
        $milliseconds = (float)$parts[2] / 100;
    } else {
        echo "<script>alert('Please enter a valid time format');</script>"; // Invalid format
    }
  
    return ($minutes * 60) + $seconds + $milliseconds;
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
