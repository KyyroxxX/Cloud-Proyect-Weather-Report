<?php
/**
 * Weather Report Solutions S.L. — Logout
 */
require_once __DIR__ . '/config.php';
session_start();
session_destroy();
header('Location: ' . APP_URL . '/login.php');
exit;
