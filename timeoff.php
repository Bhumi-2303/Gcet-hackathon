<?php
session_start();
require_once 'config/db.php';

// Check if user is authenticated
if (!isset($_SESSION['user'])) {
    header('Location: signin.php');
    exit;
}

$user = $_SESSION['user'];
$company_id = $user['company_id'];
$employee_id = $user['employee_id'] ?? null;
$userRoleId = $user['role_id'] ?? null;

$pdo = getPDO();

// Check if time_off table exists, create if not
$tableExists = false;
try {
    $check = $pdo->query("SHOW TABLES LIKE 'time_off'");
    if ($check->rowCount() > 0) {
        $columns = $pdo->query("SHOW COLUMNS FROM time_off LIKE 'time_off_id'");
        if ($columns->rowCount() > 0) {
            $tableExists = true;
        } else {
            $pdo->exec("DROP TABLE IF EXISTS time_off");
        }
    }
} catch (PDOException $e) {
    // Table doesn't exist
}

if (!$tableExists) {
    try {
        $pdo->exec("
            CREATE TABLE `time_off` (
              `time_off_id` int NOT NULL AUTO_INCREMENT,
              `employee_id` int NOT NULL,
              `type` enum('Paid Time Off','Sick Leave','Unpaid Leave') NOT NULL,
              `start_date` date NOT NULL,
              `end_date` date NOT NULL,
              `reason` text,
              `attachment_url` varchar(255) DEFAULT NULL,
              `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
              `approved_by` int DEFAULT NULL,
              `approved_at` timestamp NULL DEFAULT NULL,
              `rejection_reason` text DEFAULT NULL,
              `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`time_off_id`),
              KEY `employee_id` (`employee_id`),
              KEY `status` (`status`),
              KEY `start_date` (`start_date`),
              CONSTRAINT `time_off_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
        ");
    } catch (PDOException $e) {
        error_log('Failed to create time_off table: ' . $e->getMessage());
    }
}

// Get filter parameters
$filter_employee = isset($_GET['employee']) ? (int)$_GET['employee'] : null;
$filter_date = $_GET['date'] ?? '';
$filter_type = $_GET['type'] ?? '';
$filter_status = $_GET['status'] ?? '';

// For non-admin users, only show their own time off
$employees = [];
if ($userRoleId == 1) { // ADMIN
    $empStmt = $pdo->prepare("SELECT employee_id, first_name, last_name FROM employees WHERE company_id = ? ORDER BY first_name");
    $empStmt->execute([$company_id]);
    $employees = $empStmt->fetchAll(PDO::FETCH_ASSOC);
}

// Build query for time off records
$where = [];
$params = [];

// For employees, only show their own records
if ($userRoleId != 1 && $employee_id) {
    $where[] = "t.employee_id = ?";
    $params[] = $employee_id;
} else if ($filter_employee && $userRoleId == 1) {
    $where[] = "t.employee_id = ?";
    $params[] = $filter_employee;
}

if ($filter_type && in_array($filter_type, ['Paid Time Off', 'Sick Leave', 'Unpaid Leave'])) {
    $where[] = "t.type = ?";
    $params[] = $filter_type;
}

if ($filter_status && in_array($filter_status, ['Pending', 'Approved', 'Rejected'])) {
    $where[] = "t.status = ?";
    $params[] = $filter_status;
}

if ($filter_date) {
    $where[] = "(t.start_date <= ? AND t.end_date >= ?)";
    $params[] = $filter_date;
    $params[] = $filter_date;
}

// Fetch time off records
$timeOffRecords = [];
try {
    $sql = "
        SELECT 
            t.time_off_id,
            t.type,
            t.start_date,
            t.end_date,
            t.reason,
            t.status,
            t.created_at,
            e.first_name,
            e.last_name,
            e.employee_id
        FROM time_off t
        JOIN employees e ON t.employee_id = e.employee_id
        WHERE e.company_id = ?";
    
    if (!empty($where)) {
        $sql .= " AND " . implode(' AND ', $where);
    }
    
    $sql .= " ORDER BY t.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $allParams = array_merge([$company_id], $params);
    $stmt->execute($allParams);
    $timeOffRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Time off query error: ' . $e->getMessage());
    $timeOffRecords = [];
}

$error = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';
$success = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : '';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Time Off Management — HRMS</title>
  <link rel="stylesheet" href="assets/css/dashboard.css">
  <style>
    .filters { background: var(--panel); padding: 20px; border-radius: 12px; margin-bottom: 20px; }
    .filter-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: end; }
    .filter-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #334155; }
    .filter-group input, .filter-group select { width: 100%; padding: 10px; border: 1px solid #e6e9ef; border-radius: 8px; }
    .timeoff-table { width: 100%; border-collapse: collapse; background: var(--panel); border-radius: 12px; overflow: hidden; }
    .timeoff-table th, .timeoff-table td { padding: 12px; text-align: left; border-bottom: 1px solid #e6e9ef; }
    .timeoff-table th { background: #f8fafc; font-weight: 600; color: #334155; }
    .timeoff-table tr:hover { background: #f8fafc; }
    .badge { padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; }
    .badge.pending { background: #fef3c7; color: #92400e; }
    .badge.approved { background: #d1fae5; color: #065f46; }
    .badge.rejected { background: #fee2e2; color: #991b1b; }
    .btn-action { padding: 6px 12px; border-radius: 6px; border: none; cursor: pointer; font-size: 13px; margin-right: 5px; }
    .btn-approve { background: var(--success); color: white; }
    .btn-approve:hover { background: #059669; }
    .btn-reject { background: var(--danger); color: white; }
    .btn-reject:hover { background: #dc2626; }
    .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .page-header h1 { margin: 0; }
    .empty-state { text-align: center; padding: 40px; color: var(--muted); }
    .modal { position: fixed; left: 0; top: 0; width: 100%; height: 100%; display: none; align-items: center; justify-content: center; background: rgba(12,18,40,0.45); z-index: 140; }
    .modal.open { display: flex; }
    .modal-card { background: var(--panel); padding: 30px; border-radius: 12px; max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
    .modal-card .close { float: right; background: none; border: none; color: var(--accent); cursor: pointer; font-size: 24px; padding: 5px 10px; line-height: 1; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #334155; font-size: 14px; }
    .btn-cancel { background: #f1f5f9; color: #475569; padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; border: none; }
    .btn-cancel:hover { background: #e2e8f0; }
    .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 15px; font-size: 14px; }
    .alert-error { background: rgba(254,242,242,0.9); border: 1px solid rgba(239,68,68,0.12); color: var(--danger); }
    .alert-success { background: rgba(240,253,244,0.9); border: 1px solid rgba(16,185,129,0.12); color: var(--success); }
  </style>
</head>
<body>
  <header class="topbar">
    <div class="container topbar-inner">
      <div class="left">
        <div class="logo">AuroraHQ</div>
        <nav class="main-nav">
          <a href="dashboard.php">Employees</a>
          <a href="attendance.php">Attendance</a>
          <a href="timeoff.php" class="active">Time Off</a>
          <?php if ($userRoleId == 1): ?>
          <a href="employees.php">Manage</a>
          <?php endif; ?>
        </nav>
      </div>
      <div class="right">
        <div class="avatar-wrapper">
          <img class="avatar" src="<?php echo htmlspecialchars($user['logo'] ?? 'https://i.pravatar.cc/40?u=me'); ?>" alt="User">
        </div>
      </div>
    </div>
  </header>

  <main class="container" style="padding-top: 20px;">
    <div class="page-header">
      <h1>Time Off Management</h1>
      <?php if ($userRoleId != 1): // Not Admin, show Apply button ?>
      <button class="btn primary" onclick="document.getElementById('applyTimeOffModal').classList.add('open')">Apply for Time Off</button>
      <?php endif; ?>
    </div>

    <?php if($error): ?>
      <div class="alert alert-error" style="margin-bottom: 15px;"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if($success): ?>
      <div class="alert alert-success" style="margin-bottom: 15px;"><?php echo $success; ?></div>
    <?php endif; ?>

    <div class="filters">
      <form method="get" action="timeoff.php">
        <div class="filter-row">
          <?php if ($userRoleId == 1): ?>
          <div class="filter-group">
            <label>Employee</label>
            <select name="employee">
              <option value="">All Employees</option>
              <?php foreach($employees as $emp): ?>
                <option value="<?php echo $emp['employee_id']; ?>" <?php echo $filter_employee == $emp['employee_id'] ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php endif; ?>
          <div class="filter-group">
            <label>Date</label>
            <input type="date" name="date" value="<?php echo htmlspecialchars($filter_date); ?>">
          </div>
          <div class="filter-group">
            <label>Type</label>
            <select name="type">
              <option value="">All</option>
              <option value="Paid Time Off" <?php echo $filter_type === 'Paid Time Off' ? 'selected' : ''; ?>>Paid Time Off</option>
              <option value="Sick Leave" <?php echo $filter_type === 'Sick Leave' ? 'selected' : ''; ?>>Sick Leave</option>
              <option value="Unpaid Leave" <?php echo $filter_type === 'Unpaid Leave' ? 'selected' : ''; ?>>Unpaid Leave</option>
            </select>
          </div>
          <div class="filter-group">
            <label>Status</label>
            <select name="status">
              <option value="">All</option>
              <option value="Pending" <?php echo $filter_status === 'Pending' ? 'selected' : ''; ?>>Pending</option>
              <option value="Approved" <?php echo $filter_status === 'Approved' ? 'selected' : ''; ?>>Approved</option>
              <option value="Rejected" <?php echo $filter_status === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
            </select>
          </div>
          <div class="filter-group">
            <button type="submit" class="btn primary">Filter</button>
            <a href="timeoff.php" class="btn" style="margin-left: 10px;">Reset</a>
          </div>
        </div>
      </form>
    </div>

    <div class="panel">
      <table class="timeoff-table">
        <thead>
          <tr>
            <?php if ($userRoleId == 1): ?>
            <th>Employee</th>
            <?php endif; ?>
            <th>Start Date</th>
            <th>End Date</th>
            <th>Type</th>
            <th>Status</th>
            <?php if ($userRoleId == 1): ?>
            <th>Actions</th>
            <?php endif; ?>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($timeOffRecords)): ?>
          <tr>
            <td colspan="<?php echo $userRoleId == 1 ? '6' : '4'; ?>" class="empty-state">
              No time off records found.
            </td>
          </tr>
          <?php else: ?>
            <?php foreach($timeOffRecords as $record): ?>
            <tr>
              <?php if ($userRoleId == 1): ?>
              <td><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></td>
              <?php endif; ?>
              <td><?php echo date('M d, Y', strtotime($record['start_date'])); ?></td>
              <td><?php echo date('M d, Y', strtotime($record['end_date'])); ?></td>
              <td><?php echo htmlspecialchars($record['type']); ?></td>
              <td><span class="badge <?php echo strtolower($record['status']); ?>"><?php echo htmlspecialchars($record['status']); ?></span></td>
              <?php if ($userRoleId == 1 && $record['status'] === 'Pending'): ?>
              <td>
                <button class="btn-action btn-approve" onclick="approveTimeOff(<?php echo $record['time_off_id']; ?>)">Approve</button>
                <button class="btn-action btn-reject" onclick="rejectTimeOff(<?php echo $record['time_off_id']; ?>)">Reject</button>
              </td>
              <?php elseif ($userRoleId == 1): ?>
              <td>-</td>
              <?php endif; ?>
            </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </main>

  <?php if ($userRoleId != 1): // Apply Time Off Modal for Employees ?>
  <div id="applyTimeOffModal" class="modal">
    <div class="modal-card" style="max-width: 600px;">
      <button class="close btn-link" onclick="document.getElementById('applyTimeOffModal').classList.remove('open')">×</button>
      <h2>Apply for Time Off</h2>
      <form id="timeOffForm" enctype="multipart/form-data">
        <div class="form-group" style="margin-bottom: 15px;">
          <label>Time Off Type <span style="color: var(--danger);">*</span></label>
          <select name="type" required style="width: 100%; padding: 12px; border: 1px solid #e6e9ef; border-radius: 8px;">
            <option value="">Select Type</option>
            <option value="Paid Time Off">Paid Time Off</option>
            <option value="Sick Leave">Sick Leave</option>
            <option value="Unpaid Leave">Unpaid Leave</option>
          </select>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
          <div class="form-group">
            <label>Start Date <span style="color: var(--danger);">*</span></label>
            <input type="date" name="start_date" required style="width: 100%; padding: 12px; border: 1px solid #e6e9ef; border-radius: 8px;" min="<?php echo date('Y-m-d'); ?>">
          </div>
          <div class="form-group">
            <label>End Date <span style="color: var(--danger);">*</span></label>
            <input type="date" name="end_date" required style="width: 100%; padding: 12px; border: 1px solid #e6e9ef; border-radius: 8px;" min="<?php echo date('Y-m-d'); ?>">
          </div>
        </div>
        
        <div class="form-group" style="margin-bottom: 15px;">
          <label>Reason <span style="color: var(--danger);">*</span></label>
          <textarea name="reason" required rows="4" style="width: 100%; padding: 12px; border: 1px solid #e6e9ef; border-radius: 8px; resize: vertical;" placeholder="Please provide a reason for your time off request"></textarea>
        </div>
        
        <div class="form-group" style="margin-bottom: 15px;">
          <label>Attachment (Optional)</label>
          <input type="file" name="attachment" accept=".pdf,.png,.jpg,.jpeg" style="width: 100%; padding: 12px; border: 1px solid #e6e9ef; border-radius: 8px;">
          <small style="color: var(--muted); font-size: 12px;">Allowed: PDF, PNG, JPG (Max 5MB)</small>
        </div>
        
        <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
          <button type="button" class="btn-cancel" onclick="document.getElementById('applyTimeOffModal').classList.remove('open')">Cancel</button>
          <button type="submit" class="btn primary">Submit Request</button>
        </div>
      </form>
    </div>
  </div>
  <?php endif; ?>

  <script>
    document.querySelectorAll('.modal').forEach(modal => {
      modal.addEventListener('click', (e) => {
        if (e.target === modal) {
          modal.classList.remove('open');
        }
      });
    });
    
    // Handle time off form submission
    const timeOffForm = document.getElementById('timeOffForm');
    if (timeOffForm) {
      timeOffForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const startDate = new Date(formData.get('start_date'));
        const endDate = new Date(formData.get('end_date'));
        
        if (startDate > endDate) {
          alert('End date must be after start date');
          return;
        }
        
        fetch('api/apply_timeoff.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if (data.ok) {
            alert('Time off request submitted successfully!');
            window.location.reload();
          } else {
            alert(data.error || 'Failed to submit request');
          }
        })
        .catch(() => alert('Network error. Please try again.'));
      });
    }
    
    // Approve/Reject functions for Admin
    function approveTimeOff(id) {
      if (confirm('Approve this time off request?')) {
        fetch('api/approve_timeoff.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/x-www-form-urlencoded'},
          body: 'id=' + id + '&action=approve'
        })
        .then(response => response.json())
        .then(data => {
          if (data.ok) {
            window.location.reload();
          } else {
            alert(data.error || 'Failed to approve request');
          }
        });
      }
    }
    
    function rejectTimeOff(id) {
      const reason = prompt('Please provide a reason for rejection:');
      if (reason !== null && reason.trim()) {
        fetch('api/approve_timeoff.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/x-www-form-urlencoded'},
          body: 'id=' + id + '&action=reject&reason=' + encodeURIComponent(reason)
        })
        .then(response => response.json())
        .then(data => {
          if (data.ok) {
            window.location.reload();
          } else {
            alert(data.error || 'Failed to reject request');
          }
        });
      }
    }
  </script>
</body>
</html>

