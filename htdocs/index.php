<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

// If logged in, redirect to dashboard
redirect('pages/dashboard.php');
?>