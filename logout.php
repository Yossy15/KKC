<?php
/**
 * Logout Endpoint
 */
require_once __DIR__ . '/includes/functions.php';

logout_user();
header("Location: " . base_url('index.php'));
exit;
