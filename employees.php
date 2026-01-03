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
$role_id = $user['role_id'] ?? null;

// Check if user has permission (ADMIN or HR)
$pdo = getPDO();
$userRole = null;
if ($role_id) {
    $roleStmt = $pdo->prepare("SELECT role_name FROM roles WHERE role_id = ?");
    $roleStmt->execute([$role_id]);
    $userRole = $roleStmt->fetchColumn();
}

if (!in_array($userRole, ['ADMIN', 'HR'])) {
    header('Location: dashboard.php?error=' . urlencode('You do not have permission to access this page.'));
    exit;
}

// Handle delete employee
if (isset($_GET['delete']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $emp_id = (int)$_GET['delete'];
    try {
        // Verify employee belongs to same company
        $check = $pdo->prepare("SELECT company_id FROM employees WHERE employee_id = ?");
        $check->execute([$emp_id]);
        $emp = $check->fetch();
        
        if ($emp && $emp['company_id'] == $company_id) {
            $del = $pdo->prepare("DELETE FROM employees WHERE employee_id = ?");
            $del->execute([$emp_id]);
            header('Location: employees.php?success=' . urlencode('Employee deleted successfully.'));
            exit;
        }
    } catch (Exception $e) {
        error_log('Delete error: ' . $e->getMessage());
    }
    header('Location: employees.php?error=' . urlencode('Failed to delete employee.'));
    exit;
}

// Fetch employees
$stmt = $pdo->prepare("
    SELECT 
        e.employee_id,
        e.first_name,
        e.last_name,
        e.email,
        e.phone,
        e.date_of_joining,
        r.role_name,
        u.login_id
    FROM employees e
    LEFT JOIN users u ON e.employee_id = u.employee_id
    LEFT JOIN roles r ON u.role_id = r.role_id
    WHERE e.company_id = ?
    ORDER BY e.first_name, e.last_name
");
$stmt->execute([$company_id]);
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch roles for dropdown
$rolesStmt = $pdo->query("SELECT role_id, role_name FROM roles ORDER BY role_name");
$roles = $rolesStmt->fetchAll(PDO::FETCH_ASSOC);

$error = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';
$success = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : '';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Employee Management — HRMS</title>
  <link rel="stylesheet" href="assets/css/dashboard.css">
  <style>
    .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .page-header h1 { margin: 0; }
    .employees-table { width: 100%; border-collapse: collapse; background: var(--panel); border-radius: 12px; overflow: hidden; }
    .employees-table th, .employees-table td { padding: 12px; text-align: left; border-bottom: 1px solid #e6e9ef; }
    .employees-table th { background: #f8fafc; font-weight: 600; color: #334155; }
    .employees-table tr:hover { background: #f8fafc; }
    .btn-danger { background: var(--danger); color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 13px; }
    .btn-danger:hover { background: #dc2626; }
    .btn-edit { background: var(--accent); color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; text-decoration: none; display: inline-block; font-size: 13px; margin-right: 5px; }
    .btn-edit:hover { background: #6d4cff; }
    .modal { position: fixed; left: 0; top: 0; width: 100%; height: 100%; display: none; align-items: center; justify-content: center; background: rgba(12,18,40,0.45); z-index: 140; }
    .modal.open { display: flex; }
    .modal-card { background: var(--panel); padding: 30px; border-radius: 12px; max-width: 700px; width: 90%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
    .modal-card h2 { margin: 0 0 20px 0; color: #0f172a; }
    .modal-card .close { float: right; background: none; border: none; color: var(--accent); cursor: pointer; font-size: 18px; padding: 5px 10px; }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px; }
    .form-row.full { grid-template-columns: 1fr; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #334155; font-size: 14px; }
    .form-group label .required { color: var(--danger); margin-left: 3px; }
    .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px; border: 1px solid #e6e9ef; border-radius: 8px; font-size: 14px; font-family: inherit; transition: border-color 0.2s; }
    .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px rgba(123,97,255,0.1); }
    .form-group textarea { resize: vertical; min-height: 80px; }
    .form-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; }
    .form-actions button { padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; border: none; }
    .form-actions .btn-cancel { background: #f1f5f9; color: #475569; }
    .form-actions .btn-cancel:hover { background: #e2e8f0; }
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
          <a href="timeoff.php">Time Off</a>
          <a href="employees.php" class="active">Manage</a>
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
      <h1>Employee Management</h1>
      <button class="btn primary" onclick="document.getElementById('addEmployeeModal').classList.add('open')">+ Add Employee</button>
    </div>

    <?php if($error): ?>
      <div class="alert alert-error" style="margin-bottom: 15px;"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if($success): ?>
      <div class="alert alert-success" style="margin-bottom: 15px;"><?php echo $success; ?></div>
    <?php endif; ?>

    <div class="panel">
      <table class="employees-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Role</th>
            <th>Date of Joining</th>
            <th>Login ID</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($employees as $emp): ?>
          <tr>
            <td><?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?></td>
            <td><?php echo htmlspecialchars($emp['email'] ?? 'N/A'); ?></td>
            <td><?php echo htmlspecialchars($emp['phone'] ?? 'N/A'); ?></td>
            <td><?php echo htmlspecialchars($emp['role_name'] ?? 'N/A'); ?></td>
            <td><?php echo htmlspecialchars($emp['date_of_joining']); ?></td>
            <td><?php echo htmlspecialchars($emp['login_id'] ?? 'N/A'); ?></td>
            <td>
              <a href="?edit=<?php echo $emp['employee_id']; ?>" class="btn-edit">Edit</a>
              <button class="btn-danger" onclick="if(confirm('Delete this employee?')) window.location='?delete=<?php echo $emp['employee_id']; ?>'">Delete</button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </main>

  <!-- Add Employee Modal -->
  <div id="addEmployeeModal" class="modal">
    <div class="modal-card">
      <button class="close btn-link" onclick="document.getElementById('addEmployeeModal').classList.remove('open')">×</button>
      <h2>Add New Employee</h2>
      <form action="api/add_employee.php" method="post" id="addEmployeeForm">
        <div class="form-row">
          <div class="form-group">
            <label>First Name <span class="required">*</span></label>
            <input type="text" name="first_name" required placeholder="Enter first name">
          </div>
          <div class="form-group">
            <label>Last Name <span class="required">*</span></label>
            <input type="text" name="last_name" required placeholder="Enter last name">
          </div>
        </div>
        
        <div class="form-row">
          <div class="form-group">
            <label>Email Address <span class="required">*</span></label>
            <input type="email" name="email" required placeholder="employee@example.com">
          </div>
          <div class="form-group">
            <label>Phone Number <span class="required">*</span></label>
            <input type="tel" name="phone" required placeholder="+1234567890">
          </div>
        </div>
        
        <div class="form-row">
          <div class="form-group">
            <label>Date of Joining <span class="required">*</span></label>
            <input type="date" name="date_of_joining" required value="<?php echo date('Y-m-d'); ?>">
          </div>
          <div class="form-group">
            <label>Role <span class="required">*</span></label>
            <select name="role_id" required>
              <option value="">Select Role</option>
              <?php foreach($roles as $role): ?>
                <option value="<?php echo $role['role_id']; ?>" <?php echo $role['role_id'] == 3 ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($role['role_name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        
        <div class="form-row full">
          <div class="form-group">
            <label>Initial Password <span class="required">*</span></label>
            <input type="password" name="password" required placeholder="Set temporary password for employee login" id="employeePassword">
            <small style="color: var(--muted); font-size: 12px; margin-top: 5px; display: block;">
              Employee will use this password for first login. They should change it after logging in.
            </small>
          </div>
        </div>
        
        <div class="form-actions">
          <button type="button" class="btn-cancel" onclick="document.getElementById('addEmployeeModal').classList.remove('open')">Cancel</button>
          <button type="submit" class="btn primary">Add Employee</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    // Modal close functionality
    document.querySelectorAll('.modal').forEach(modal => {
      modal.addEventListener('click', (e) => {
        if (e.target === modal) {
          modal.classList.remove('open');
        }
      });
    });
    
    // Form validation
    document.getElementById('addEmployeeForm').addEventListener('submit', function(e) {
      const password = document.getElementById('employeePassword').value;
      if (password.length < 6) {
        e.preventDefault();
        alert('Password must be at least 6 characters long.');
        return false;
      }
    });
    
    // Show modal when button is clicked
    document.querySelector('.btn.primary').addEventListener('click', function() {
      document.getElementById('addEmployeeModal').classList.add('open');
      // Reset form
      document.getElementById('addEmployeeForm').reset();
    });
  </script>
</body>
</html>

