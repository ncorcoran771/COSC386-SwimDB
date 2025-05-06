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

    if (count($parts) == 2) {
        $minutes = 0;
        $seconds = (float)$parts[0];
        if(strlen($parts[1]) == 1) {
            $parts[2] = $parts[2] . '0';
        }
        $milliseconds = (float)$parts[1] / 100;
    }
    else if (count($parts) == 3) {
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
        echo "<script>alert('Please enter a valid time format');</script>";
    }

    return ($minutes * 60) + $seconds + $milliseconds;
}

// Convert seconds to time string
function secondsToTime($seconds) {
    $seconds = (float)$seconds;
    
    $minutes = floor($seconds / 60);
    $secs = fmod($seconds, 60);
    $ms = round(($secs - floor($secs)) * 100);
    return sprintf("%d:%02d:%02d", $minutes, floor($secs), $ms);
}

function formatTime($totalSeconds) {
    if (!is_numeric($totalSeconds) || $totalSeconds < 0) {
        return "N/A";
    }

    $wholeSeconds = floor($totalSeconds);
    $milliseconds = round(($totalSeconds - $wholeSeconds) * 100);
    $minutes = floor($wholeSeconds / 60);
    $seconds = $wholeSeconds % 60;

    return sprintf("%02d:%02d:%02d", $minutes, $seconds, $milliseconds);
}
?>
