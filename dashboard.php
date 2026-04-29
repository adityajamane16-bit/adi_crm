<?php
// ============================================
// dashboard.php — Main Dashboard
// ============================================
require_once 'db.php';
$page_title = 'Dashboard';
require_once 'header.php';

// ---- Stats ----
$stats = [];

$r = $conn->query("SELECT COUNT(*) as cnt FROM complaints");
$stats['total'] = $r->fetch_assoc()['cnt'];

$r = $conn->query("SELECT COUNT(*) as cnt FROM complaints WHERE status = 'open'");
$stats['open'] = $r->fetch_assoc()['cnt'];

$r = $conn->query("SELECT COUNT(*) as cnt FROM complaints WHERE status = 'in_progress'");
$stats['in_progress'] = $r->fetch_assoc()['cnt'];

$r = $conn->query("SELECT COUNT(*) as cnt FROM complaints WHERE status = 'closed'");
$stats['closed'] = $r->fetch_assoc()['cnt'];

$r = $conn->query("SELECT COUNT(*) as cnt FROM customers");
$stats['customers'] = $r->fetch_assoc()['cnt'];

$r = $conn->query("SELECT COUNT(*) as cnt FROM technicians WHERE is_available = 1");
$stats['available_techs'] = $r->fetch_assoc()['cnt'];

// ---- Recent Complaints ----
$recent = $conn->query("
    SELECT c.id, c.status, c.priority, c.created_at,
           cu.name AS customer_name, cu.phone,
           t.name  AS technician_name
    FROM complaints c
    JOIN customers cu ON c.customer_id = cu.id
    LEFT JOIN technicians t ON c.technician_id = t.id
    ORDER BY c.created_at DESC
    LIMIT 8
");

// ---- Urgent Open Complaints ----
$urgent = $conn->query("
    SELECT c.id, cu.name, cu.phone, c.created_at
    FROM complaints c
    JOIN customers cu ON c.customer_id = cu.id
    WHERE c.status != 'closed' AND c.priority = 'urgent'
    ORDER BY c.created_at ASC
    LIMIT 5
");
?>

<div class="page-header">
    <div>
        <h1>Dashboard</h1>
        <p>Welcome back, <?= htmlspecialchars($_SESSION['admin_name']) ?> — <?= date('l, d F Y') ?></p>
    </div>
    <a href="register_complaint.php" class="btn btn-primary">+ Register New Complaint</a>
</div>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card" style="--accent-color: var(--accent)">
        <span class="stat-icon">📋</span>
        <div class="stat-number"><?= $stats['total'] ?></div>
        <div class="stat-label">Total Complaints</div>
    </div>
    <div class="stat-card" style="--accent-color: var(--red)">
        <span class="stat-icon">🔴</span>
        <div class="stat-number" style="color:var(--red)"><?= $stats['open'] ?></div>
        <div class="stat-label">Open Complaints</div>
    </div>
    <div class="stat-card" style="--accent-color: var(--yellow)">
        <span class="stat-icon">🔧</span>
        <div class="stat-number" style="color:var(--yellow)"><?= $stats['in_progress'] ?></div>
        <div class="stat-label">In Progress</div>
    </div>
    <div class="stat-card" style="--accent-color: var(--green)">
        <span class="stat-icon">✅</span>
        <div class="stat-number" style="color:var(--green)"><?= $stats['closed'] ?></div>
        <div class="stat-label">Closed</div>
    </div>
    <div class="stat-card" style="--accent-color: var(--purple)">
        <span class="stat-icon">👥</span>
        <div class="stat-number" style="color:var(--purple)"><?= $stats['customers'] ?></div>
        <div class="stat-label">Total Customers</div>
    </div>
    <div class="stat-card" style="--accent-color: var(--green)">
        <span class="stat-icon">👨‍🔧</span>
        <div class="stat-number" style="color:var(--green)"><?= $stats['available_techs'] ?></div>
        <div class="stat-label">Available Technicians</div>
    </div>
</div>

<!-- Urgent Complaints Alert -->
<?php if ($urgent->num_rows > 0): ?>
<div class="alert alert-error mb-2" style="flex-direction:column;gap:12px;">
    <div style="display:flex;align-items:center;gap:8px;font-weight:600;">
        🚨 Urgent Complaints Pending Action
    </div>
    <?php while ($u = $urgent->fetch_assoc()): ?>
    <div style="display:flex;align-items:center;justify-content:space-between;background:rgba(255,77,109,0.07);padding:10px 14px;border-radius:8px;">
        <div>
            <strong><?= htmlspecialchars($u['name']) ?></strong>
            <span style="color:var(--muted);font-size:12px;margin-left:8px;"><?= htmlspecialchars($u['phone']) ?></span>
        </div>
        <div style="display:flex;align-items:center;gap:12px;">
            <span style="color:var(--muted);font-size:12px;"><?= formatDate($u['created_at']) ?></span>
            <a href="complaint_detail.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-danger">View →</a>
        </div>
    </div>
    <?php endwhile; ?>
</div>
<?php endif; ?>

<!-- Recent Complaints Table -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
    <h2 style="font-family:'Syne',sans-serif;font-size:18px;font-weight:700;">Recent Complaints</h2>
    <a href="view_complaints.php" class="btn btn-ghost btn-sm">View All →</a>
</div>

<div class="table-wrap">
    <?php if ($recent->num_rows === 0): ?>
    <div class="empty-state">
        <div class="empty-icon">📭</div>
        <p>No complaints yet. <a href="register_complaint.php" style="color:var(--accent)">Register the first one →</a></p>
    </div>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>#ID</th>
                <th>Customer</th>
                <th>Phone</th>
                <th>Technician</th>
                <th>Priority</th>
                <th>Status</th>
                <th>Registered</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $recent->fetch_assoc()): ?>
            <tr>
                <td style="color:var(--accent);font-weight:600;">#<?= str_pad($row['id'], 4, '0', STR_PAD_LEFT) ?></td>
                <td style="color:var(--text);"><?= htmlspecialchars($row['customer_name']) ?></td>
                <td><?= htmlspecialchars($row['phone']) ?></td>
                <td><?= $row['technician_name'] ? htmlspecialchars($row['technician_name']) : '<span style="color:var(--muted)">Unassigned</span>' ?></td>
                <td><?= priorityBadge($row['priority']) ?></td>
                <td><?= statusBadge($row['status']) ?></td>
                <td><?= formatDate($row['created_at']) ?></td>
                <td><a href="complaint_detail.php?id=<?= $row['id'] ?>" class="btn btn-ghost btn-sm">View</a></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>
