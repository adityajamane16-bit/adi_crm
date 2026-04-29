<?php
// ============================================
// customers.php — All Customers
// ============================================
require_once 'db.php';
$page_title = 'Customers';

$search = clean($conn, $_GET['search'] ?? '');
$where  = $search ? "WHERE cu.name LIKE '%$search%' OR cu.phone LIKE '%$search%'" : "";

$customers = $conn->query("
    SELECT cu.*,
        COUNT(c.id) AS total_complaints,
        MAX(c.created_at) AS last_complaint
    FROM customers cu
    LEFT JOIN complaints c ON cu.id = c.customer_id
    $where
    GROUP BY cu.id
    ORDER BY cu.created_at DESC
");

require_once 'header.php';
?>

<div class="page-header">
    <div>
        <h1>Customers</h1>
        <p><?= $customers->num_rows ?> customer(s) registered</p>
    </div>
</div>

<!-- Search -->
<form method="GET" action="customers.php">
    <div class="filter-bar">
        <input type="text" name="search" placeholder="🔍 Search by name or phone..."
               value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
        <button type="submit" class="btn btn-ghost btn-sm">Search</button>
        <a href="customers.php" class="btn btn-ghost btn-sm">Clear</a>
    </div>
</form>

<div class="table-wrap">
    <?php if ($customers->num_rows === 0): ?>
    <div class="empty-state">
        <div class="empty-icon">👥</div>
        <p>No customers found.</p>
    </div>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Phone</th>
                <th>Address</th>
                <th>AC Type</th>
                <th>AC Brand</th>
                <th>Total Complaints</th>
                <th>Last Complaint</th>
                <th>Registered</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($cu = $customers->fetch_assoc()): ?>
            <tr>
                <td style="color:var(--muted);"><?= $cu['id'] ?></td>
                <td style="color:var(--text);font-weight:500;"><?= htmlspecialchars($cu['name']) ?></td>
                <td>
                    <a href="tel:<?= htmlspecialchars($cu['phone']) ?>"
                       style="color:var(--accent);text-decoration:none;">
                        <?= htmlspecialchars($cu['phone']) ?>
                    </a>
                </td>
                <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                    <?= htmlspecialchars($cu['address']) ?>
                </td>
                <td><?= htmlspecialchars($cu['ac_type']) ?></td>
                <td><?= htmlspecialchars($cu['ac_brand'] ?: '—') ?></td>
                <td>
                    <span style="color:<?= $cu['total_complaints'] > 2 ? 'var(--yellow)' : 'var(--accent)' ?>;font-weight:600;">
                        <?= $cu['total_complaints'] ?>
                        <?= $cu['total_complaints'] > 2 ? ' ⚠️' : '' ?>
                    </span>
                </td>
                <td style="font-size:13px;color:var(--muted);"><?= $cu['last_complaint'] ? formatDate($cu['last_complaint']) : '—' ?></td>
                <td style="font-size:13px;color:var(--muted);"><?= formatDate($cu['created_at']) ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>
