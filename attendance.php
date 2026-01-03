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

$pdo = getPDO();

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
        // Continue anyway, the query will fail gracefully
    }
}

// Get filter parameters
$filter_employee = isset($_GET['employee']) ? (int)$_GET['employee'] : null;
$filter_date = $_GET['date'] ?? date('Y-m-d');
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get user role
$userRoleId = $user['role_id'] ?? null;

// Build query to get daily attendance summary (check-in, check-out, work hours)
$where = ["DATE(a.created_at) = ?"];
$params = [$filter_date];

if ($filter_employee) {
    $where[] = "a.employee_id = ?";
    $params[] = $filter_employee;
}

// For non-admin users, only show their own attendance
if ($userRoleId != 1 && $employee_id) { // Not ADMIN
    $where[] = "a.employee_id = ?";
    $params[] = $employee_id;
}

$whereClause = implode(' AND ', $where);

// Fetch attendance records grouped by employee for the selected date
$attendanceData = [];
try {
    // Get all employees for the company
    $empWhere = "e.company_id = ?";
    $empParams = [$company_id];
    
    if ($userRoleId != 1 && $employee_id) {
        $empWhere .= " AND e.employee_id = ?";
        $empParams[] = $employee_id;
    }
    
    if ($filter_employee) {
        $empWhere .= " AND e.employee_id = ?";
        $empParams[] = $filter_employee;
    }
    
    if ($search_query) {
        $empWhere .= " AND (e.first_name LIKE ? OR e.last_name LIKE ? OR e.email LIKE ?)";
        $searchTerm = '%' . $search_query . '%';
        $empParams[] = $searchTerm;
        $empParams[] = $searchTerm;
        $empParams[] = $searchTerm;
    }
    
    $empStmt = $pdo->prepare("
        SELECT 
            e.employee_id,
            e.first_name,
            e.last_name,
            e.email
        FROM employees e
        WHERE $empWhere
        ORDER BY e.first_name, e.last_name
    ");
    $empStmt->execute($empParams);
    $employees = $empStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // For each employee, get their check-in and check-out for the selected date
    foreach ($employees as $emp) {
        $checkInStmt = $pdo->prepare("
            SELECT created_at 
            FROM attendance 
            WHERE employee_id = ? AND DATE(created_at) = ? AND type = 'IN'
            ORDER BY created_at ASC
            LIMIT 1
        ");
        $checkInStmt->execute([$emp['employee_id'], $filter_date]);
        $checkIn = $checkInStmt->fetch();
        
        $checkOutStmt = $pdo->prepare("
            SELECT created_at 
            FROM attendance 
            WHERE employee_id = ? AND DATE(created_at) = ? AND type = 'OUT'
            ORDER BY created_at DESC
            LIMIT 1
        ");
        $checkOutStmt->execute([$emp['employee_id'], $filter_date]);
        $checkOut = $checkOutStmt->fetch();
        
        $checkInTime = $checkIn ? $checkIn['created_at'] : null;
        $checkOutTime = $checkOut ? $checkOut['created_at'] : null;
        
        // Calculate work hours
        $workHours = '00:00';
        $extraHours = '00:00';
        if ($checkInTime && $checkOutTime) {
            $checkInTimestamp = strtotime($checkInTime);
            $checkOutTimestamp = strtotime($checkOutTime);
            $totalSeconds = $checkOutTimestamp - $checkInTimestamp;
            $totalHours = floor($totalSeconds / 3600);
            $totalMinutes = floor(($totalSeconds % 3600) / 60);
            $workHours = sprintf('%02d:%02d', $totalHours, $totalMinutes);
            
            // Calculate extra hours (assuming 8 hours standard work day)
            $standardHours = 8;
            if ($totalHours > $standardHours) {
                $extraSeconds = ($totalHours - $standardHours) * 3600 + ($totalMinutes * 60);
                $extraH = floor($extraSeconds / 3600);
                $extraM = floor(($extraSeconds % 3600) / 60);
                $extraHours = sprintf('%02d:%02d', $extraH, $extraM);
            }
        }
        
        $attendanceData[] = [
            'employee_id' => $emp['employee_id'],
            'first_name' => $emp['first_name'],
            'last_name' => $emp['last_name'],
            'email' => $emp['email'],
            'check_in' => $checkInTime ? date('H:i', strtotime($checkInTime)) : '-',
            'check_out' => $checkOutTime ? date('H:i', strtotime($checkOutTime)) : '-',
            'work_hours' => $workHours,
            'extra_hours' => $extraHours
        ];
    }
} catch (PDOException $e) {
    error_log('Attendance query error: ' . $e->getMessage());
    $attendanceData = [];
}

// Get employees list for filter (only for admins)
$employeesList = [];
if ($userRoleId == 1) { // ADMIN
    $empStmt = $pdo->prepare("SELECT employee_id, first_name, last_name FROM employees WHERE company_id = ? ORDER BY first_name");
    $empStmt->execute([$company_id]);
    $employeesList = $empStmt->fetchAll(PDO::FETCH_ASSOC);
}

// Format date for display
$displayDate = date('d, F Y', strtotime($filter_date));
$prevDate = date('Y-m-d', strtotime($filter_date . ' -1 day'));
$nextDate = date('Y-m-d', strtotime($filter_date . ' +1 day'));

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Attendance — HRMS</title>
  <link rel="stylesheet" href="assets/css/dashboard.css">
  <style>
    .attendance-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .attendance-header h1 { margin: 0; }
    .search-bar { display: flex; align-items: center; gap: 10px; }
    .search-bar input { padding: 10px 15px; border: 1px solid #e6e9ef; border-radius: 8px; width: 300px; }
    .date-navigation { display: flex; align-items: center; gap: 10px; margin-bottom: 20px; }
    .date-nav-btn { background: var(--panel); border: 1px solid #e6e9ef; padding: 8px 12px; border-radius: 8px; cursor: pointer; font-size: 16px; }
    .date-nav-btn:hover { background: #f8fafc; }
    .date-display { font-size: 18px; font-weight: 600; color: #0f172a; margin: 0 15px; }
    .date-select-btn { background: var(--accent); color: white; border: none; padding: 8px 16px; border-radius: 8px; cursor: pointer; font-weight: 600; }
    .date-select-btn:hover { background: #6d4cff; }
    .day-btn { background: var(--panel); border: 1px solid #e6e9ef; padding: 8px 16px; border-radius: 8px; cursor: pointer; font-weight: 600; }
    .day-btn:hover { background: #f8fafc; }
    .attendance-table { width: 100%; border-collapse: collapse; background: var(--panel); border-radius: 12px; overflow: hidden; }
    .attendance-table th, .attendance-table td { padding: 15px; text-align: left; border-bottom: 1px solid #e6e9ef; }
    .attendance-table th { background: #f8fafc; font-weight: 600; color: #334155; }
    .attendance-table tr:hover { background: #f8fafc; }
    .attendance-table td { color: #0f172a; }
  </style>
</head>
<body>
  <header class="topbar">
    <div class="container topbar-inner">
      <div class="left">
        <div class="logo">AuroraHQ</div>
        <nav class="main-nav">
          <a href="dashboard.php">Employees</a>
          <a href="attendance.php" class="active">Attendance</a>
          <a href="timeoff.php">Time Off</a>
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
    <div class="attendance-header">
      <h1>Attendance</h1>
      <div class="search-bar">
        <form method="get" action="attendance.php" style="display: flex; gap: 10px;">
          <input type="hidden" name="date" value="<?php echo htmlspecialchars($filter_date); ?>">
          <?php if ($filter_employee): ?>
          <input type="hidden" name="employee" value="<?php echo $filter_employee; ?>">
          <?php endif; ?>
          <input type="text" name="search" placeholder="Search employees..." value="<?php echo htmlspecialchars($search_query); ?>" style="padding: 10px 15px; border: 1px solid #e6e9ef; border-radius: 8px; width: 300px;">
          <button type="submit" class="btn primary" style="padding: 10px 20px;">Search</button>
        </form>
      </div>
    </div>
    
    <div class="date-navigation">
      <a href="?date=<?php echo $prevDate; ?><?php echo $filter_employee ? '&employee=' . $filter_employee : ''; ?><?php echo $search_query ? '&search=' . urlencode($search_query) : ''; ?>" class="date-nav-btn">←</a>
      <a href="?date=<?php echo $nextDate; ?><?php echo $filter_employee ? '&employee=' . $filter_employee : ''; ?><?php echo $search_query ? '&search=' . urlencode($search_query) : ''; ?>" class="date-nav-btn">→</a>
      <form method="get" action="attendance.php" style="display: inline;">
        <?php if ($filter_employee): ?>
        <input type="hidden" name="employee" value="<?php echo $filter_employee; ?>">
        <?php endif; ?>
        <?php if ($search_query): ?>
        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>">
        <?php endif; ?>
        <input type="date" name="date" value="<?php echo htmlspecialchars($filter_date); ?>" onchange="this.form.submit()" style="display: none;" id="dateInput">
        <button type="button" onclick="document.getElementById('dateInput').click()" class="date-select-btn">Date ▼</button>
      </form>
      <span class="date-display"><?php echo $displayDate; ?></span>
      <button class="day-btn" onclick="window.location='?date=<?php echo date('Y-m-d'); ?><?php echo $filter_employee ? '&employee=' . $filter_employee : ''; ?><?php echo $search_query ? '&search=' . urlencode($search_query) : ''; ?>'">Day</button>
    </div>

    <div class="panel">
      <table class="attendance-table">
        <thead>
          <tr>
            <th>Emp</th>
            <th>Check In</th>
            <th>Check Out</th>
            <th>Work Hours</th>
            <th>Extra hours</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($attendanceData)): ?>
          <tr>
            <td colspan="5" style="text-align: center; padding: 40px; color: var(--muted);">
              No attendance records found for <?php echo $displayDate; ?>.
            </td>
          </tr>
          <?php else: ?>
            <?php foreach($attendanceData as $record): ?>
            <tr>
              <td><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></td>
              <td><?php echo htmlspecialchars($record['check_in']); ?></td>
              <td><?php echo htmlspecialchars($record['check_out']); ?></td>
              <td><?php echo htmlspecialchars($record['work_hours']); ?></td>
              <td><?php echo htmlspecialchars($record['extra_hours']); ?></td>
            </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </main>
</body>
</html>

