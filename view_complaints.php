<?php
// ============================================
// view_complaints.php — All Complaints List
// ============================================
require_once 'db.php';
$page_title = 'All Complaints';

// ---- Filters ----
$where   = ["1=1"];
$status  = clean($conn, $_GET['status']   ?? '');
$priority= clean($conn, $_GET['priority'] ?? '');
$search  = clean($conn, $_GET['search']   ?? '');

if ($status)   $where[] = "c.status = '$status'";
if ($priority) $where[] = "c.priority = '$priority'";
if ($search)   $where[] = "(cu.name LIKE '%$search%' OR cu.phone LIKE '%$search%' OR c.id LIKE '%$search%')";

$where_sql = implode(' AND ', $where);

$complaints = $conn->query("
    SELECT c.id, c.status, c.priority, c.created_at, c.closed_at,
           cu.name AS customer_name, cu.phone, cu.address, cu.ac_type,
           t.name  AS technician_name,
           c.ai_summary
    FROM complaints c
    JOIN customers cu ON c.customer_id = cu.id
    LEFT JOIN technicians t ON c.technician_id = t.id
    WHERE $where_sql
    ORDER BY
        CASE c.priority WHEN 'urgent' THEN 1 WHEN 'normal' THEN 2 ELSE 3 END,
        c.created_at DESC
");

require_once 'header.php';
?>

<div class="page-header">
    <div>
        <h1>All Complaints</h1>
        <p><?= $complaints->num_rows ?> complaint(s) found</p>
    </div>
    <a href="register_complaint.php" class="btn btn-primary">+ New Complaint</a>
</div>

<!-- Filter Bar -->
<form method="GET" action="view_complaints.php">
    <div class="filter-bar">
        <input type="text" name="search" placeholder="🔍 Search name, phone, ID..."
               value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">

        <select name="status">
            <option value="">All Status</option>
            <?php
            foreach (['open','in_progress','closed'] as $s) {
                $sel = ($status === $s) ? 'selected' : '';
                $label = ucwords(str_replace('_', ' ', $s));
                echo "<option value='$s' $sel>$label</option>";
            }
            ?>
        </select>

        <select name="priority">
            <option value="">All Priority</option>
            <?php
            foreach (['urgent','normal','low'] as $p) {
                $sel = ($priority === $p) ? 'selected' : '';
                echo "<option value='$p' $sel>" . ucfirst($p) . "</option>";
            }
            ?>
        </select>

        <button type="submit" class="btn btn-ghost btn-sm">Filter</button>
        <a href="view_complaints.php" class="btn btn-ghost btn-sm">Clear</a>
    </div>
</form>

<!-- Table -->
<div class="table-wrap">
    <?php if ($complaints->num_rows === 0): ?>
    <div class="empty-state">
        <div class="empty-icon">🔍</div>
        <p>No complaints match your filters.</p>
    </div>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>#ID</th>
                <th>Customer</th>
                <th>Phone</th>
                <th>AC Type</th>
                <th>Technician</th>
                <th>Priority</th>
                <th>Status</th>
                <th>Registered</th>
                <th>Closed</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $complaints->fetch_assoc()): ?>
            <tr>
                <td style="color:var(--accent);font-weight:600;font-family:'Syne',sans-serif;">
                    #<?= str_pad($row['id'], 4, '0', STR_PAD_LEFT) ?>
                </td>
                <td style="color:var(--text);font-weight:500;"><?= htmlspecialchars($row['customer_name']) ?></td>
                <td><?= htmlspecialchars($row['phone']) ?></td>
                <td><?= htmlspecialchars($row['ac_type']) ?></td>
                <td>
                    <?= $row['technician_name']
                        ? htmlspecialchars($row['technician_name'])
                        : '<span style="color:var(--muted);font-style:italic;">Unassigned</span>' ?>
                </td>
                <td><?= priorityBadge($row['priority']) ?></td>
                <td><?= statusBadge($row['status']) ?></td>
                <td style="white-space:nowrap;font-size:13px;"><?= formatDate($row['created_at']) ?></td>
                <td style="white-space:nowrap;font-size:13px;color:var(--muted);">
                    <?= $row['closed_at'] ? formatDate($row['closed_at']) : '—' ?>
                </td>
                <td>
                    <div style="display:flex;gap:6px;">
                        <a href="complaint_detail.php?id=<?= $row['id'] ?>" class="btn btn-ghost btn-sm">View</a>
                        <?php if ($row['status'] !== 'closed'): ?>
                        <a href="close_complaint.php?id=<?= $row['id'] ?>"
                           class="btn btn-sm btn-success"
                           onclick="return confirm('Mark complaint #<?= $row['id'] ?> as CLOSED?')">
                           Close
                        </a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>
