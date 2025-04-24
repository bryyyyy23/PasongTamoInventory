<?php
// Start the session if not already started
session_start();

// Unset all session variables
$_SESSION = array();

// If a session cookie is used, clear it by setting its expiration to the past
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 42000, '/');
}

// Destroy the session
session_destroy();

// Set a logout message in a temporary cookie (optional)
setcookie('logout_message', 'You have been successfully logged out.', time() + 30, '/');

// Redirect to login page
header("Location: login.php");
exit();
?>