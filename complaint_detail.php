<?php
// ============================================
// complaint_detail.php — Single Complaint View
// ============================================
require_once 'db.php';
$page_title = 'Complaint Detail';

$id = intval($_GET['id'] ?? 0);
if (!$id) { header("Location: view_complaints.php"); exit(); }

// ---- Handle technician assignment ----
$assign_msg   = '';
$assign_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_tech'])) {
    $tech_id = intval($_POST['technician_id']);
    if ($tech_id > 0) {
        $conn->query("UPDATE complaints SET technician_id = $tech_id, status = 'in_progress' WHERE id = $id");

        $tech_name = $conn->query("SELECT name FROM technicians WHERE id = $tech_id")->fetch_assoc()['name'];
        $action    = "Assigned to technician: $tech_name";
        $stmt = $conn->prepare("INSERT INTO complaint_log (complaint_id, action, done_by) VALUES (?,?,?)");
        $stmt->bind_param("iss", $id, $action, $_SESSION['admin_name']);
        $stmt->execute();
        $stmt->close();

        $assign_msg = "Technician assigned successfully!";
    } else {
        $assign_error = "Please select a technician.";
    }
}

// ---- Fetch complaint ----
$stmt = $conn->prepare("
    SELECT c.*, cu.name AS customer_name, cu.phone, cu.address, cu.ac_type, cu.ac_brand,
           t.name AS technician_name, t.phone AS tech_phone, t.area AS tech_area
    FROM complaints c
    JOIN customers cu ON c.customer_id = cu.id
    LEFT JOIN technicians t ON c.technician_id = t.id
    WHERE c.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$complaint = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$complaint) { header("Location: view_complaints.php"); exit(); }

// ---- Fetch activity log ----
$logs = $conn->query("SELECT * FROM complaint_log WHERE complaint_id = $id ORDER BY done_at ASC");

// ---- Fetch available technicians ----
$techs = $conn->query("SELECT id, name, area FROM technicians WHERE is_available = 1 ORDER BY name");

require_once 'header.php';
?>

<div class="page-header">
    <div>
        <h1>Complaint #<?= str_pad($complaint['id'], 4, '0', STR_PAD_LEFT) ?></h1>
        <div style="display:flex;align-items:center;gap:10px;margin-top:6px;">
            <?= statusBadge($complaint['status']) ?>
            <?= priorityBadge($complaint['priority']) ?>
            <span style="color:var(--muted);font-size:13px;">Registered <?= formatDate($complaint['created_at']) ?></span>
        </div>
    </div>
    <div style="display:flex;gap:10px;">
        <?php if ($complaint['status'] !== 'closed'): ?>
        <a href="close_complaint.php?id=<?= $id ?>"
           class="btn btn-success"
           onclick="return confirm('Mark this complaint as CLOSED?')">
           ✅ Close Complaint
        </a>
        <?php endif; ?>
        <a href="view_complaints.php" class="btn btn-ghost">← Back</a>
    </div>
</div>

<?php if ($assign_msg):   ?><div class="alert alert-success">✅ <?= $assign_msg ?></div><?php endif; ?>
<?php if ($assign_error): ?><div class="alert alert-error">⚠️ <?= $assign_error ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 340px;gap:24px;align-items:start;">

    <!-- LEFT: Main details -->
    <div>
        <!-- Customer Info -->
        <div class="card mb-2">
            <h3 style="font-family:'Syne',sans-serif;font-size:15px;font-weight:700;color:var(--accent);margin-bottom:18px;">
                👤 Customer Information
            </h3>
            <div class="detail-grid">
                <div class="detail-block">
                    <div class="detail-label">Full Name</div>
                    <div class="detail-value"><?= htmlspecialchars($complaint['customer_name']) ?></div>
                </div>
                <div class="detail-block">
                    <div class="detail-label">Phone</div>
                    <div class="detail-value">
                        <a href="tel:<?= htmlspecialchars($complaint['phone']) ?>"
                           style="color:var(--accent);text-decoration:none;">
                            <?= htmlspecialchars($complaint['phone']) ?>
                        </a>
                    </div>
                </div>
                <div class="detail-block" style="grid-column:span 2">
                    <div class="detail-label">Address</div>
                    <div class="detail-value"><?= htmlspecialchars($complaint['address']) ?></div>
                </div>
                <div class="detail-block">
                    <div class="detail-label">AC Type</div>
                    <div class="detail-value"><?= htmlspecialchars($complaint['ac_type']) ?></div>
                </div>
                <div class="detail-block">
                    <div class="detail-label">AC Brand</div>
                    <div class="detail-value"><?= htmlspecialchars($complaint['ac_brand'] ?: '—') ?></div>
                </div>
            </div>
        </div>

        <!-- Problem Description -->
        <div class="card mb-2">
            <h3 style="font-family:'Syne',sans-serif;font-size:15px;font-weight:700;color:var(--accent);margin-bottom:14px;">
                🔧 Problem Description
            </h3>
            <p style="color:var(--text2);line-height:1.8;font-size:15px;">
                <?= nl2br(htmlspecialchars($complaint['problem'])) ?>
            </p>

            <?php if ($complaint['ai_summary']): ?>
            <div class="ai-box mt-2">
                <div class="ai-box-header">🤖 AI Summary</div>
                <p><?= htmlspecialchars($complaint['ai_summary']) ?></p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Activity Log -->
        <div class="card">
            <h3 style="font-family:'Syne',sans-serif;font-size:15px;font-weight:700;color:var(--accent);margin-bottom:18px;">
                📝 Activity Log
            </h3>
            <?php if ($logs->num_rows === 0): ?>
            <p style="color:var(--muted);font-size:14px;">No activity logged yet.</p>
            <?php else: ?>
            <ul class="log-list">
                <?php while ($log = $logs->fetch_assoc()): ?>
                <li class="log-item">
                    <?= htmlspecialchars($log['action']) ?>
                    <span class="log-time"><?= formatDate($log['done_at']) ?> — by <?= htmlspecialchars($log['done_by']) ?></span>
                </li>
                <?php endwhile; ?>
            </ul>
            <?php endif; ?>
        </div>
    </div>

    <!-- RIGHT: Sidebar -->
    <div>
        <!-- Technician Assignment -->
        <div class="card mb-2">
            <h3 style="font-family:'Syne',sans-serif;font-size:15px;font-weight:700;color:var(--accent);margin-bottom:16px;">
                👨‍🔧 Technician
            </h3>

            <?php if ($complaint['technician_name']): ?>
            <div style="background:rgba(0,214,143,0.07);border:1px solid rgba(0,214,143,0.2);border-radius:10px;padding:16px;margin-bottom:16px;">
                <div style="font-weight:600;font-size:15px;color:var(--text);">
                    <?= htmlspecialchars($complaint['technician_name']) ?>
                </div>
                <div style="color:var(--text2);font-size:13px;margin-top:4px;">
                    📞 <?= htmlspecialchars($complaint['tech_phone']) ?><br>
                    📍 <?= htmlspecialchars($complaint['tech_area']) ?>
                </div>
            </div>
            <?php else: ?>
            <div style="color:var(--muted);font-size:13px;margin-bottom:14px;padding:12px;background:rgba(255,77,109,0.06);border-radius:8px;border:1px solid rgba(255,77,109,0.15);">
                ⚠️ No technician assigned yet
            </div>
            <?php endif; ?>

            <?php if ($complaint['status'] !== 'closed'): ?>
            <form method="POST" action="complaint_detail.php?id=<?= $id ?>">
                <div class="field" style="margin-bottom:10px;">
                    <label><?= $complaint['technician_name'] ? 'Reassign' : 'Assign' ?> Technician</label>
                    <select name="technician_id">
                        <option value="">— Select Technician —</option>
                        <?php
                        $techs->data_seek(0);
                        while ($t = $techs->fetch_assoc()):
                        $sel = ($complaint['technician_id'] == $t['id']) ? 'selected' : '';
                        ?>
                        <option value="<?= $t['id'] ?>" <?= $sel ?>>
                            <?= htmlspecialchars($t['name']) ?> — <?= htmlspecialchars($t['area']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <button type="submit" name="assign_tech" class="btn btn-primary w-full">
                    Assign Technician
                </button>
            </form>
            <?php endif; ?>
        </div>

        <!-- Complaint Meta -->
        <div class="card">
            <h3 style="font-family:'Syne',sans-serif;font-size:15px;font-weight:700;color:var(--accent);margin-bottom:16px;">
                📌 Complaint Info
            </h3>
            <div style="display:flex;flex-direction:column;gap:14px;">
                <div>
                    <div class="detail-label">Complaint ID</div>
                    <div class="detail-value" style="font-family:'Syne',sans-serif;font-size:20px;font-weight:800;color:var(--accent);">
                        #<?= str_pad($complaint['id'], 4, '0', STR_PAD_LEFT) ?>
                    </div>
                </div>
                <div>
                    <div class="detail-label">Status</div>
                    <div class="detail-value mt-1"><?= statusBadge($complaint['status']) ?></div>
                </div>
                <div>
                    <div class="detail-label">Priority</div>
                    <div class="detail-value mt-1"><?= priorityBadge($complaint['priority']) ?></div>
                </div>
                <div>
                    <div class="detail-label">Registered On</div>
                    <div class="detail-value" style="font-size:13px;"><?= formatDate($complaint['created_at']) ?></div>
                </div>
                <?php if ($complaint['closed_at']): ?>
                <div>
                    <div class="detail-label">Closed On</div>
                    <div class="detail-value" style="font-size:13px;color:var(--green);"><?= formatDate($complaint['closed_at']) ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
