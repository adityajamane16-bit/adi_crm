<?php
// ============================================
// register_complaint.php — Register New Complaint
// ============================================
require_once 'db.php';
$page_title = 'Register Complaint';

$success = '';
$error   = '';
$new_id  = null;

// Get technicians for dropdown
$techs = $conn->query("SELECT id, name, area FROM technicians WHERE is_available = 1 ORDER BY name");

// ---- Handle Form Submit ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name       = clean($conn, $_POST['name'] ?? '');
    $phone      = clean($conn, $_POST['phone'] ?? '');
    $address    = clean($conn, $_POST['address'] ?? '');
    $ac_type    = clean($conn, $_POST['ac_type'] ?? 'Split');
    $ac_brand   = clean($conn, $_POST['ac_brand'] ?? '');
    $problem    = clean($conn, $_POST['problem'] ?? '');
    $tech_id    = intval($_POST['technician_id'] ?? 0);
    $priority   = clean($conn, $_POST['priority'] ?? 'normal');
    $ai_summary = clean($conn, $_POST['ai_summary'] ?? '');

    if (empty($name) || empty($phone) || empty($address) || empty($problem)) {
        $error = "Please fill all required fields.";
    } else {
        // 1. Insert customer
        $stmt = $conn->prepare("INSERT INTO customers (name, phone, address, ac_type, ac_brand) VALUES (?,?,?,?,?)");
        $stmt->bind_param("sssss", $name, $phone, $address, $ac_type, $ac_brand);
        $stmt->execute();
        $customer_id = $conn->insert_id;
        $stmt->close();

        // 2. Insert complaint
        $tech_val = $tech_id > 0 ? $tech_id : null;
        $status   = $tech_id > 0 ? 'in_progress' : 'open';

        $stmt = $conn->prepare("INSERT INTO complaints (customer_id, technician_id, problem, ai_summary, priority, status) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param("iissss", $customer_id, $tech_val, $problem, $ai_summary, $priority, $status);
        $stmt->execute();
        $complaint_id = $conn->insert_id;
        $stmt->close();

        // 3. Log creation
        $action = "Complaint registered by " . $_SESSION['admin_name'];
        $stmt = $conn->prepare("INSERT INTO complaint_log (complaint_id, action, done_by) VALUES (?,?,?)");
        $stmt->bind_param("iss", $complaint_id, $action, $_SESSION['admin_name']);
        $stmt->execute();
        $stmt->close();

        // 4. If technician assigned, log that too
        if ($tech_id > 0) {
            $tech_row = $conn->query("SELECT name FROM technicians WHERE id = $tech_id")->fetch_assoc();
            $action2  = "Assigned to technician: " . ($tech_row['name'] ?? 'Unknown');
            $stmt = $conn->prepare("INSERT INTO complaint_log (complaint_id, action, done_by) VALUES (?,?,?)");
            $stmt->bind_param("iss", $complaint_id, $action2, $_SESSION['admin_name']);
            $stmt->execute();
            $stmt->close();
        }

        $success = "Complaint registered successfully!";
        $new_id  = $complaint_id;
    }
}

require_once 'header.php';
?>

<div class="page-header">
    <div>
        <h1>Register New Complaint</h1>
        <p>Enter customer details and describe the AC problem</p>
    </div>
    <a href="view_complaints.php" class="btn btn-ghost">← All Complaints</a>
</div>

<?php if ($success): ?>
<div class="alert alert-success">
    ✅ <?= $success ?>
    <?php if ($new_id): ?>
    — <a href="complaint_detail.php?id=<?= $new_id ?>" style="color:var(--green);font-weight:600;">View Complaint #<?= str_pad($new_id, 4, '0', STR_PAD_LEFT) ?> →</a>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-error">⚠️ <?= $error ?></div>
<?php endif; ?>

<form method="POST" action="register_complaint.php" id="complaintForm">

    <!-- Customer Info -->
    <div class="card mb-2">
        <h3 style="font-family:'Syne',sans-serif;font-size:16px;font-weight:700;margin-bottom:20px;color:var(--accent);">
            👤 Customer Information
        </h3>
        <div class="form-grid">
            <div class="field">
                <label>Full Name *</label>
                <input type="text" name="name" placeholder="e.g. Ramesh Kumar" required
                       value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
            </div>
            <div class="field">
                <label>Phone Number *</label>
                <input type="tel" name="phone" placeholder="e.g. 9876543210" required
                       value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
            </div>
            <div class="field span2">
                <label>Address *</label>
                <input type="text" name="address" placeholder="Full address with area/landmark" required
                       value="<?= htmlspecialchars($_POST['address'] ?? '') ?>">
            </div>
            <div class="field">
                <label>AC Type</label>
                <select name="ac_type">
                    <?php
                    $types = ['Split','Window','Cassette','Tower','Portable'];
                    foreach ($types as $t) {
                        $sel = (($_POST['ac_type'] ?? 'Split') === $t) ? 'selected' : '';
                        echo "<option value='$t' $sel>$t AC</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="field">
                <label>AC Brand</label>
                <input type="text" name="ac_brand" placeholder="e.g. Voltas, Daikin, LG"
                       value="<?= htmlspecialchars($_POST['ac_brand'] ?? '') ?>">
            </div>
        </div>
    </div>

    <!-- Complaint Info -->
    <div class="card mb-2">
        <h3 style="font-family:'Syne',sans-serif;font-size:16px;font-weight:700;margin-bottom:20px;color:var(--accent);">
            🔧 Complaint Details
        </h3>
        <div class="form-grid">
            <div class="field span2">
                <label>Problem Description *</label>
                <textarea name="problem" id="problemText" rows="4"
                          placeholder="Describe the AC issue in detail. E.g. 'AC not cooling even after running for 2 hours, making noise, last serviced 6 months ago...'"
                          required><?= htmlspecialchars($_POST['problem'] ?? '') ?></textarea>
            </div>

            <!-- AI Summary area -->
            <div class="field span2">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                    <button type="button" id="aiBtn" class="btn btn-ghost btn-sm" onclick="generateAISummary()">
                        ✨ Generate AI Summary
                    </button>
                    <span id="aiStatus" style="font-size:13px;color:var(--muted);"></span>
                </div>
                <div id="aiBox" style="display:none;" class="ai-box">
                    <div class="ai-box-header">🤖 AI Summary</div>
                    <p id="aiSummaryText"></p>
                    <input type="hidden" name="ai_summary" id="aiSummaryInput">
                </div>
            </div>

            <div class="field">
                <label>Priority</label>
                <select name="priority" id="prioritySelect">
                    <option value="normal" <?= (($_POST['priority'] ?? 'normal') === 'normal') ? 'selected' : '' ?>>Normal</option>
                    <option value="low"    <?= (($_POST['priority'] ?? '') === 'low')    ? 'selected' : '' ?>>Low</option>
                    <option value="urgent" <?= (($_POST['priority'] ?? '') === 'urgent') ? 'selected' : '' ?>>Urgent</option>
                </select>
            </div>

            <div class="field">
                <label>Assign Technician (optional)</label>
                <select name="technician_id">
                    <option value="">— Assign Later —</option>
                    <?php
                    $techs->data_seek(0);
                    while ($t = $techs->fetch_assoc()):
                    $sel = (($_POST['technician_id'] ?? '') == $t['id']) ? 'selected' : '';
                    ?>
                    <option value="<?= $t['id'] ?>" <?= $sel ?>>
                        <?= htmlspecialchars($t['name']) ?> (<?= htmlspecialchars($t['area']) ?>)
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>
    </div>

    <div style="display:flex;gap:12px;">
        <button type="submit" class="btn btn-primary">✅ Register Complaint</button>
        <a href="dashboard.php" class="btn btn-ghost">Cancel</a>
    </div>
</form>

<script>
async function generateAISummary() {
    const problem = document.getElementById('problemText').value.trim();
    if (!problem || problem.length < 10) {
        alert('Please enter a detailed problem description first.');
        return;
    }

    const btn    = document.getElementById('aiBtn');
    const status = document.getElementById('aiStatus');
    const box    = document.getElementById('aiBox');

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> Generating...';
    status.textContent = 'Calling AI...';

    try {
        const formData = new FormData();
        formData.append('problem', problem);

        const response = await fetch('ai_summary.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.error) {
            status.textContent = '⚠️ ' + data.error;
        } else {
            document.getElementById('aiSummaryText').textContent  = data.summary;
            document.getElementById('aiSummaryInput').value       = data.summary;
            box.style.display = 'block';
            status.textContent = '✅ Summary generated!';

            // Auto-set priority if AI returned one
            if (data.priority) {
                document.getElementById('prioritySelect').value = data.priority;
            }
        }
    } catch (e) {
        status.textContent = '⚠️ Network error. Try again.';
    } finally {
        btn.disabled = false;
        btn.innerHTML = '✨ Regenerate AI Summary';
    }
}
</script>

<?php require_once 'footer.php'; ?>
