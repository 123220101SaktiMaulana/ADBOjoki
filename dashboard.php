<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

$role = $_SESSION["role"];

switch ($role) {
    case 'admin':
        include 'admin_dashboard.php';
        break;
    case 'joki':
        include 'joki_dashboard.php';
        break;
    case 'customer':
        include 'customer_dashboard.php';
        break;
    default:
        // Optional: Redirect to login if role is not set or invalid
        header("location: login.php");
        exit;
}
?> 