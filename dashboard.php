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
$role_id = $user['role_id'] ?? null;

// Fetch employees from the database
$pdo = getPDO();

// Get user role first
$userRoleId = $user['role_id'] ?? null;
$userRole = null;
if ($userRoleId) {
    $roleStmt = $pdo->prepare("SELECT role_name FROM roles WHERE role_id = ?");
    $roleStmt->execute([$userRoleId]);
    $userRole = $roleStmt->fetchColumn();
}

// Check if user has permission to add employees (ADMIN or HR)
$canAddEmployee = false;
if ($userRole) {
    $canAddEmployee = in_array($userRole, ['ADMIN', 'HR']);
}

// Fetch roles for dropdown (if user can add employees)
$roles = [];
if ($canAddEmployee) {
    $rolesStmt = $pdo->query("SELECT role_id, role_name FROM roles ORDER BY role_name");
    $roles = $rolesStmt->fetchAll(PDO::FETCH_ASSOC);
}

// Check if attendance table exists with correct structure, create if not
$tableExists = false;
try {
    $check = $pdo->query("SHOW TABLES LIKE 'attendance'");
    if ($check->rowCount() > 0) {
        // Table exists, check if it has the required column
        $columns = $pdo->query("SHOW COLUMNS FROM attendance LIKE 'attendance_id'");
        if ($columns->rowCount() > 0) {
            $tableExists = true;
        } else {
            // Table exists but wrong structure, drop and recreate
            $pdo->exec("DROP TABLE IF EXISTS attendance");
        }
    }
} catch (PDOException $e) {
    // Table doesn't exist
}

if (!$tableExists) {
    // Create the table
    try {
        $pdo->exec("
            CREATE TABLE `attendance` (
              `attendance_id` int NOT NULL AUTO_INCREMENT,
              `employee_id` int NOT NULL,
              `type` enum('IN','OUT') NOT NULL,
              `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
              `ip_address` varchar(45) DEFAULT NULL,
              PRIMARY KEY (`attendance_id`),
              KEY `employee_id` (`employee_id`),
              KEY `created_at` (`created_at`),
              CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
        ");
    } catch (PDOException $e) {
        error_log('Failed to create attendance table: ' . $e->getMessage());
    }
}

// If user is EMPLOYEE, only show their own information
// If user is ADMIN or HR, show all employees
if ($userRole === 'EMPLOYEE' && $employee_id) {
    // Employee can only see their own information
    $stmt = $pdo->prepare("
        SELECT 
            e.employee_id AS id, 
            e.first_name, 
            e.last_name, 
            e.email, 
            e.phone,
            e.date_of_joining,
            r.role_name AS title,
            (SELECT a.type FROM attendance a WHERE a.employee_id = e.employee_id AND DATE(a.created_at) = CURDATE() ORDER BY a.created_at DESC LIMIT 1) AS status
        FROM employees e
        LEFT JOIN users u ON e.employee_id = u.employee_id
        LEFT JOIN roles r ON u.role_id = r.role_id
        WHERE e.employee_id = ?
    ");
    $stmt->execute([$employee_id]);
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // ADMIN or HR can see all employees
    $stmt = $pdo->prepare("
        SELECT 
            e.employee_id AS id, 
            e.first_name, 
            e.last_name, 
            e.email, 
            e.phone,
            e.date_of_joining,
            r.role_name AS title,
            (SELECT a.type FROM attendance a WHERE a.employee_id = e.employee_id AND DATE(a.created_at) = CURDATE() ORDER BY a.created_at DESC LIMIT 1) AS status
        FROM employees e
        LEFT JOIN users u ON e.employee_id = u.employee_id
        LEFT JOIN roles r ON u.role_id = r.role_id
        WHERE e.company_id = ?
        ORDER BY e.first_name, e.last_name
    ");
    $stmt->execute([$company_id]);
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Debug: Log employee count
error_log('Employees found: ' . count($employees));

// Map status to present, absent, leave
$employees = array_map(function($employee) {
    if ($employee['status'] === 'IN') {
        $employee['status'] = 'present';
    } else if ($employee['status'] === 'OUT') {
        $employee['status'] = 'absent';
    } else {
        $employee['status'] = 'unknown'; 
    }
    return $employee;
}, $employees);

// Get current user's attendance status for today
$currentUserStatus = null;
$currentUserCheckInTime = null;
if ($employee_id) {
    try {
        $attStmt = $pdo->prepare("
            SELECT type, created_at 
            FROM attendance 
            WHERE employee_id = ? AND DATE(created_at) = CURDATE()
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $attStmt->execute([$employee_id]);
        $attRecord = $attStmt->fetch();
        if ($attRecord) {
            $currentUserStatus = $attRecord['type'];
            $currentUserCheckInTime = $attRecord['created_at'];
        }
    } catch (PDOException $e) {
        // Table might not exist yet, ignore
        error_log('Attendance query error: ' . $e->getMessage());
    }
}

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Employee Dashboard — HRMS</title>
  <link rel="stylesheet" href="assets/css/dashboard.css">
  <style>
    .panel-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
    .panel-header .hint { margin: 0; }
    .add-employee-btn { background: linear-gradient(90deg, var(--accent), #9063ff); color: white; border: none; padding: 8px 16px; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; }
    .add-employee-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(123,97,255,0.3); }
    .modal { position: fixed; left: 0; top: 0; width: 100%; height: 100%; display: none; align-items: center; justify-content: center; background: rgba(12,18,40,0.45); z-index: 140; }
    .modal.open { display: flex; }
    .modal-card { background: var(--panel); padding: 30px; border-radius: 12px; max-width: 700px; width: 90%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
    .modal-card h2 { margin: 0 0 20px 0; color: #0f172a; }
    .modal-card .close { float: right; background: none; border: none; color: var(--accent); cursor: pointer; font-size: 24px; padding: 5px 10px; line-height: 1; }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px; }
    .form-row.full { grid-template-columns: 1fr; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #334155; font-size: 14px; }
    .form-group label .required { color: var(--danger); margin-left: 3px; }
    .form-group input, .form-group select { width: 100%; padding: 12px; border: 1px solid #e6e9ef; border-radius: 8px; font-size: 14px; font-family: inherit; transition: border-color 0.2s; }
    .form-group input:focus, .form-group select:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px rgba(123,97,255,0.1); }
    .form-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; }
    .form-actions button { padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; border: none; }
    .form-actions .btn-cancel { background: #f1f5f9; color: #475569; }
    .form-actions .btn-cancel:hover { background: #e2e8f0; }
  </style>
</head>
<body>
  <header class="topbar">
    <div class="container topbar-inner">
      <div class="left">
        <div class="logo">AuroraHQ</div>
        <nav class="main-nav">
          <a href="dashboard.php" class="active">Employees</a>
          <a href="attendance.php">Attendance</a>
          <a href="timeoff.php">Time Off</a>
          <?php if (in_array($userRole, ['ADMIN', 'HR'])): ?>
          <a href="employees.php">Manage</a>
          <?php endif; ?>
        </nav>
      </div>
      <div class="center">
        <?php if ($userRole !== 'EMPLOYEE'): ?>
        <div class="search-box"><input id="globalSearch" type="search" placeholder="Search employees..." aria-label="Search employees"></div>
        <?php endif; ?>
      </div>
      <div class="right">
        <div class="avatar-wrapper" id="avatarWrapper">
          <img id="userAvatar" class="avatar" src="<?php echo htmlspecialchars($user['logo'] ?? 'https://i.pravatar.cc/40?u=me'); ?>" alt="User">
          <div class="avatar-menu" id="avatarMenu" role="menu" aria-hidden="true">
            <button id="viewProfileBtn" class="menu-item">My Profile</button>
            <button id="logoutBtn" class="menu-item">Log Out</button>
          </div>
        </div>
      </div>
    </div>
  </header>

  <main class="container main-grid">
    <section class="panel employees-panel">
      <div class="panel-header">
        <div class="hint">Click on an employee card to view all details.</div>
        <?php if ($canAddEmployee): ?>
        <button class="add-employee-btn" onclick="document.getElementById('addEmployeeModal').classList.add('open')">+ Add Employee</button>
        <?php endif; ?>
      </div>
      <div id="employeeGrid" class="employee-grid" aria-live="polite" style="min-height: 200px;">
        <!-- Employee cards will be rendered here by JS -->
      </div>
    </section>

    <aside class="panel attendance-panel" aria-label="Attendance">
      <div class="panel-header">
        <strong>Attendance</strong>
      </div>
      <div class="attendance-body">
        <div class="status-row">
          <div class="status-dot header-dot" id="headerStatus" title="You are absent"></div>
          <div class="status-text" id="statusText">Not checked in</div>
        </div>
        <div class="time-text" id="sinceText"></div>
        <div class="attendance-actions">
          <button id="checkInBtn" class="btn primary">Check In</button>
          <button id="checkOutBtn" class="btn" disabled>Check Out</button>
        </div>
      </div>
    </aside>
  </main>

  <div id="employeeModal" class="modal" aria-hidden="true" role="dialog">
    <div class="modal-card" role="document">
      <button id="closeModal" class="close btn-link">×</button>
      <div id="employeeContent">
        <!-- filled by JS -->
      </div>
    </div>
  </div>

  <?php if ($canAddEmployee): ?>
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
            <label>Gender <span class="required">*</span></label>
            <select name="gender" required>
              <option value="">Select Gender</option>
              <option value="Male">Male</option>
              <option value="Female">Female</option>
              <option value="Other">Other</option>
            </select>
          </div>
        </div>
        
        <div class="form-row">
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
          <div class="form-group">
            <label>Initial Password <span class="required">*</span></label>
            <input type="password" name="password" required placeholder="Set temporary password" id="employeePassword">
            <small style="color: var(--muted); font-size: 12px; margin-top: 5px; display: block;">
              Employee will use this password for first login.
            </small>
          </div>
        </div>
        
        <div class="section-divider" style="margin: 20px 0; border-top: 2px solid #e6e9ef;"></div>
        <h3 style="margin: 20px 0 15px 0; color: #0f172a;">Salary Information</h3>
        
        <div class="form-row">
          <div class="form-group">
            <label>Monthly Wage (₹) <span class="required">*</span></label>
            <input type="number" name="monthly_wage" step="0.01" min="0" required placeholder="50000" value="50000">
          </div>
          <div class="form-group">
            <label>Yearly Wage (₹) <span class="required">*</span></label>
            <input type="number" name="yearly_wage" step="0.01" min="0" required placeholder="600000" value="600000">
          </div>
        </div>
        
        <div class="form-row">
          <div class="form-group">
            <label>Working Days per Week</label>
            <input type="number" name="working_days_per_week" step="0.1" min="0" max="7" placeholder="5" value="5">
          </div>
          <div class="form-group">
            <label>Break Time (hours)</label>
            <input type="number" name="break_time_hours" step="0.01" min="0" placeholder="1" value="1">
          </div>
        </div>
        
        <h4 style="margin: 20px 0 15px 0; color: #334155; font-size: 16px;">Salary Components</h4>
        
        <div class="form-row">
          <div class="form-group">
            <label>Basic Salary (₹)</label>
            <input type="number" name="basic_salary" step="0.01" min="0" placeholder="25000" value="25000">
          </div>
          <div class="form-group">
            <label>Basic Salary %</label>
            <input type="number" name="basic_salary_percent" step="0.01" min="0" max="100" placeholder="50" value="50.00">
          </div>
        </div>
        
        <div class="form-row">
          <div class="form-group">
            <label>HRA (₹)</label>
            <input type="number" name="hra" step="0.01" min="0" placeholder="12500" value="12500">
          </div>
          <div class="form-group">
            <label>HRA %</label>
            <input type="number" name="hra_percent" step="0.01" min="0" max="100" placeholder="50" value="50.00">
          </div>
        </div>
        
        <div class="form-row">
          <div class="form-group">
            <label>Standard Allowance (₹)</label>
            <input type="number" name="standard_allowance" step="0.01" min="0" placeholder="4167" value="4167">
          </div>
          <div class="form-group">
            <label>Standard Allowance %</label>
            <input type="number" name="standard_allowance_percent" step="0.01" min="0" max="100" placeholder="16.67" value="16.67">
          </div>
        </div>
        
        <div class="form-row">
          <div class="form-group">
            <label>Performance Bonus (₹)</label>
            <input type="number" name="performance_bonus" step="0.01" min="0" placeholder="2082.50" value="2082.50">
          </div>
          <div class="form-group">
            <label>Performance Bonus %</label>
            <input type="number" name="performance_bonus_percent" step="0.01" min="0" max="100" placeholder="8.33" value="8.33">
          </div>
        </div>
        
        <div class="form-row">
          <div class="form-group">
            <label>LTA (₹)</label>
            <input type="number" name="lta" step="0.01" min="0" placeholder="2082.50" value="2082.50">
          </div>
          <div class="form-group">
            <label>LTA %</label>
            <input type="number" name="lta_percent" step="0.01" min="0" max="100" placeholder="8.33" value="8.33">
          </div>
        </div>
        
        <div class="form-row">
          <div class="form-group">
            <label>Fixed Allowance (₹)</label>
            <input type="number" name="fixed_allowance" step="0.01" min="0" placeholder="2918" value="2918">
          </div>
          <div class="form-group">
            <label>Professional Tax (₹)</label>
            <input type="number" name="professional_tax" step="0.01" min="0" placeholder="200" value="200">
          </div>
        </div>
        
        <h4 style="margin: 20px 0 15px 0; color: #334155; font-size: 16px;">Provident Fund (PF)</h4>
        
        <div class="form-row">
          <div class="form-group">
            <label>PF Employee (₹)</label>
            <input type="number" name="pf_employee" step="0.01" min="0" placeholder="3000" value="3000">
          </div>
          <div class="form-group">
            <label>PF Employee %</label>
            <input type="number" name="pf_employee_percent" step="0.01" min="0" max="100" placeholder="12" value="12.00">
          </div>
        </div>
        
        <div class="form-row">
          <div class="form-group">
            <label>PF Employer (₹)</label>
            <input type="number" name="pf_employer" step="0.01" min="0" placeholder="3000" value="3000">
          </div>
          <div class="form-group">
            <label>PF Employer %</label>
            <input type="number" name="pf_employer_percent" step="0.01" min="0" max="100" placeholder="12" value="12.00">
          </div>
        </div>
        
        <div class="form-actions">
          <button type="button" class="btn-cancel" onclick="document.getElementById('addEmployeeModal').classList.remove('open')">Cancel</button>
          <button type="submit" class="btn primary">Add Employee</button>
        </div>
      </form>
    </div>
  </div>
  <?php endif; ?>
  
  <script>
    const employeeData = <?php echo json_encode($employees); ?>;
    const currentUser = <?php echo json_encode($user); ?>;
    const currentUserRole = <?php echo json_encode($userRole); ?>;
    const currentUserAttendance = {
      status: <?php echo json_encode($currentUserStatus); ?>,
      checkInTime: <?php echo json_encode($currentUserCheckInTime); ?>
    };
    
    // Security: Ensure employees only see their own data
    <?php if ($userRole === 'EMPLOYEE'): ?>
    if (employeeData && employeeData.length > 0) {
      // Filter to only show current user's data
      const filteredData = employeeData.filter(emp => emp.id === currentUser.employee_id);
      if (filteredData.length !== employeeData.length) {
        console.warn('Security: Filtered employee data to show only current user');
      }
    }
    <?php endif; ?>
  </script>
  <script src="assets/js/dashboard.js"></script>
  <?php if ($canAddEmployee): ?>
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
    const addEmployeeForm = document.getElementById('addEmployeeForm');
    if (addEmployeeForm) {
      addEmployeeForm.addEventListener('submit', function(e) {
        const password = document.getElementById('employeePassword').value;
        if (password.length < 6) {
          e.preventDefault();
          alert('Password must be at least 6 characters long.');
          return false;
        }
      });
    }
  </script>
  <?php endif; ?>
</body>
</html>