<?php
// ============================================
// technicians.php — Manage Technicians
// ============================================
require_once 'db.php';
$page_title = 'Technicians';

$success = '';
$error   = '';

// ---- Add Technician ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_tech'])) {
    $name  = clean($conn, $_POST['name']  ?? '');
    $phone = clean($conn, $_POST['phone'] ?? '');
    $area  = clean($conn, $_POST['area']  ?? '');

    if (empty($name) || empty($phone)) {
        $error = "Name and phone are required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO technicians (name, phone, area) VALUES (?,?,?)");
        $stmt->bind_param("sss", $name, $phone, $area);
        $stmt->execute();
        $stmt->close();
        $success = "Technician '$name' added successfully!";
    }
}

// ---- Toggle availability ----
if (isset($_GET['toggle'])) {
    $tid = intval($_GET['toggle']);
    $conn->query("UPDATE technicians SET is_available = NOT is_available WHERE id = $tid");
    header("Location: technicians.php");
    exit();
}

// ---- Delete technician ----
if (isset($_GET['delete'])) {
    $tid = intval($_GET['delete']);
    $conn->query("DELETE FROM technicians WHERE id = $tid");
    header("Location: technicians.php");
    exit();
}

// ---- Fetch all technicians with complaint counts ----
$techs = $conn->query("
    SELECT t.*,
        COUNT(CASE WHEN c.status != 'closed' THEN 1 END) AS active_complaints,
        COUNT(c.id) AS total_complaints
    FROM technicians t
    LEFT JOIN complaints c ON t.id = c.technician_id
    GROUP BY t.id
    ORDER BY t.is_available DESC, t.name ASC
");

require_once 'header.php';
?>

<div class="page-header">
    <div>
        <h1>Technicians</h1>
        <p>Manage your service team</p>
    </div>
</div>

<?php if ($success): ?>
<div class="alert alert-success">✅ <?= $success ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-error">⚠️ <?= $error ?></div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 340px;gap:24px;align-items:start;">

    <!-- Technicians Table -->
    <div>
        <div class="table-wrap">
            <?php if ($techs->num_rows === 0): ?>
            <div class="empty-state">
                <div class="empty-icon">👨‍🔧</div>
                <p>No technicians added yet.</p>
            </div>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Area</th>
                        <th>Active Jobs</th>
                        <th>Total Jobs</th>
                        <th>Availability</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($t = $techs->fetch_assoc()): ?>
                    <tr>
                        <td style="color:var(--muted);"><?= $t['id'] ?></td>
                        <td style="color:var(--text);font-weight:500;"><?= htmlspecialchars($t['name']) ?></td>
                        <td><?= htmlspecialchars($t['phone']) ?></td>
                        <td><?= htmlspecialchars($t['area'] ?: '—') ?></td>
                        <td>
                            <span style="color:<?= $t['active_complaints'] > 0 ? 'var(--yellow)' : 'var(--muted)' ?>;font-weight:600;">
                                <?= $t['active_complaints'] ?>
                            </span>
                        </td>
                        <td style="color:var(--accent);"><?= $t['total_complaints'] ?></td>
                        <td>
                            <?php if ($t['is_available']): ?>
                            <span style="color:var(--green);font-size:12px;font-weight:600;">● Available</span>
                            <?php else: ?>
                            <span style="color:var(--red);font-size:12px;font-weight:600;">● Unavailable</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="display:flex;gap:6px;">
                                <a href="technicians.php?toggle=<?= $t['id'] ?>"
                                   class="btn btn-ghost btn-sm">
                                   <?= $t['is_available'] ? 'Mark Busy' : 'Mark Available' ?>
                                </a>
                                <a href="technicians.php?delete=<?= $t['id'] ?>"
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirm('Delete <?= htmlspecialchars($t['name']) ?>? This cannot be undone.')">
                                   Delete
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Technician Form -->
    <div>
        <div class="card">
            <h3 style="font-family:'Syne',sans-serif;font-size:16px;font-weight:700;color:var(--accent);margin-bottom:20px;">
                + Add Technician
            </h3>
            <form method="POST" action="technicians.php">
                <div class="field mb-2">
                    <label>Full Name *</label>
                    <input type="text" name="name" placeholder="Technician name" required>
                </div>
                <div class="field mb-2">
                    <label>Phone *</label>
                    <input type="tel" name="phone" placeholder="Mobile number" required>
                </div>
                <div class="field mb-2">
                    <label>Service Area</label>
                    <input type="text" name="area" placeholder="e.g. North Zone, Sector 4">
                </div>
                <button type="submit" name="add_tech" class="btn btn-primary w-full">Add Technician</button>
            </form>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
