<?php
// ============================================
// logout.php — Destroy session and redirect
// ============================================
require_once 'db.php';
session_destroy();
header("Location: index.php?msg=logged_out");
exit();
?>
