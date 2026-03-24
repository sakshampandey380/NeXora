<?php
session_start();

/* Unset all session variables */
$_SESSION = [];

/* Destroy the session */
session_destroy();

/* Redirect admin to admin signup page */
header("Location: signup.php");
exit;
?>