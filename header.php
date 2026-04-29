<?php
// ============================================
// header.php - Shared Navigation Header
// Include at the top of every page
// ============================================
requireLogin(); // Redirect if not logged in

// Get current page for active nav highlight
$current_page = basename($_SERVER['PHP_SELF']);

// Get open complaint count for badge
$badge_result = $conn->query("SELECT COUNT(*) as cnt FROM complaints WHERE status = 'open'");
$open_count   = $badge_result->fetch_assoc()['cnt'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AC CRM — <?= $page_title ?? 'Dashboard' ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="navbar">
    <div class="nav-brand">
        <span class="nav-icon">❄️</span>
        <span class="nav-title">AC CRM</span>
    </div>

    <div class="nav-links">
        <a href="dashboard.php"          class="nav-link <?= $current_page === 'dashboard.php'          ? 'active' : '' ?>">Dashboard</a>
        <a href="register_complaint.php" class="nav-link <?= $current_page === 'register_complaint.php' ? 'active' : '' ?>">+ New Complaint</a>
        <a href="view_complaints.php"    class="nav-link <?= $current_page === 'view_complaints.php'    ? 'active' : '' ?>">
            Complaints
            <?php if ($open_count > 0): ?>
            <span class="badge"><?= $open_count ?></span>
            <?php endif; ?>
        </a>
        <a href="technicians.php"        class="nav-link <?= $current_page === 'technicians.php'        ? 'active' : '' ?>">Technicians</a>
        <a href="customers.php"          class="nav-link <?= $current_page === 'customers.php'          ? 'active' : '' ?>">Customers</a>
    </div>

    <div class="nav-right">
        <span class="admin-label">👤 <?= htmlspecialchars($_SESSION['admin_name']) ?></span>
        <a href="logout.php" class="btn-logout">Logout</a>
    </div>
</nav>

<div class="page-wrap">
