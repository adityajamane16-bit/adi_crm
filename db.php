<?php
// ============================================
// db.php - Database Connection
// ============================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');        // Change to your MySQL username
define('DB_PASS', '');            // Change to your MySQL password
define('DB_NAME', 'ac_crm');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("
    <div style='font-family:sans-serif;padding:40px;color:#c0392b;background:#fdecea;border-radius:8px;margin:40px auto;max-width:500px;text-align:center;'>
        <h2>⚠️ Database Connection Failed</h2>
        <p>" . $conn->connect_error . "</p>
        <p style='font-size:13px;color:#555;'>Check your credentials in db.php</p>
    </div>");
}

$conn->set_charset("utf8");

// Session start (called here so every page that includes db.php has sessions)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper: Check if admin is logged in
function requireLogin() {
    if (!isset($_SESSION['admin_id'])) {
        header("Location: index.php");
        exit();
    }
}

// Helper: Sanitize input
function clean($conn, $data) {
    return $conn->real_escape_string(htmlspecialchars(trim($data)));
}

// Helper: Format date nicely
function formatDate($date) {
    if (!$date || $date == '0000-00-00 00:00:00') return '—';
    return date('d M Y, h:i A', strtotime($date));
}

// Helper: Status badge HTML
function statusBadge($status) {
    $map = [
        'open'        => ['label' => 'Open',        'color' => '#e74c3c'],
        'in_progress' => ['label' => 'In Progress',  'color' => '#f39c12'],
        'closed'      => ['label' => 'Closed',       'color' => '#27ae60'],
    ];
    $s = $map[$status] ?? ['label' => ucfirst($status), 'color' => '#888'];
    return "<span style='background:{$s['color']};color:#fff;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600;'>{$s['label']}</span>";
}

// Helper: Priority badge HTML
function priorityBadge($priority) {
    $map = [
        'low'    => ['label' => 'Low',    'color' => '#3498db'],
        'normal' => ['label' => 'Normal', 'color' => '#8e44ad'],
        'urgent' => ['label' => 'Urgent', 'color' => '#c0392b'],
    ];
    $p = $map[$priority] ?? ['label' => ucfirst($priority), 'color' => '#888'];
    return "<span style='background:{$p['color']};color:#fff;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600;'>{$p['label']}</span>";
}
?>
