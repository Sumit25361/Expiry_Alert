<?php
require_once 'config/admin_auth.php';

$auth = new AdminAuth();
$auth->logout();

// Redirect to login page
header('Location: login.php');
exit;
?>
