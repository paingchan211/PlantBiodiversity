<?php

// Set teh session timeout period (in seconds)

$timeout_duration = 300; // 5mins

// Check if the session has timed out
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header('Location: login.php'); // Redirect to login page
    exit;
}

// Update the session's last activity time stamp
$_SESSION['LAST_ACTIVITY'] = time();
