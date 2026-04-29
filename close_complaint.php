<?php
// ============================================
// close_complaint.php — Close a Complaint
// ============================================
require_once 'db.php';
requireLogin();

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    header("Location: view_complaints.php");
    exit();
}

// Check complaint exists and is not already closed
$stmt = $conn->prepare("SELECT id, status FROM complaints WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$complaint = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$complaint) {
    header("Location: view_complaints.php?msg=not_found");
    exit();
}

if ($complaint['status'] === 'closed') {
    header("Location: complaint_detail.php?id=$id&msg=already_closed");
    exit();
}

// Close the complaint
$conn->query("UPDATE complaints SET status = 'closed', closed_at = NOW() WHERE id = $id");

// Log it
$action = "Complaint closed by " . $_SESSION['admin_name'];
$stmt = $conn->prepare("INSERT INTO complaint_log (complaint_id, action, done_by) VALUES (?,?,?)");
$stmt->bind_param("iss", $id, $action, $_SESSION['admin_name']);
$stmt->execute();
$stmt->close();

// Redirect back to detail page
header("Location: complaint_detail.php?id=$id&msg=closed");
exit();
?>
