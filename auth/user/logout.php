<?php
// logout.php
session_start();

// Destroy the session
session_destroy();

// Detect base URL dynamically
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$root_path = dirname(dirname(dirname($_SERVER['SCRIPT_NAME'])));
$base_url = $protocol . '://' . $host . $root_path;

// Redirect to signup page
header("Location: {$base_url}/auth/user/signup.php");
exit();
?>