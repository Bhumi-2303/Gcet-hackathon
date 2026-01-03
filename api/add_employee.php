<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Check authentication
if (!isset($_SESSION['user'])) {
    header('Location: ../employees.php?error=' . urlencode('Not authenticated'));
    exit;
}

$user = $_SESSION['user'];
$company_id = $user['company_id'];

// Check permission
$pdo = getPDO();
$roleStmt = $pdo->prepare("SELECT role_name FROM roles WHERE role_id = ?");
$roleStmt->execute([$user['role_id'] ?? null]);
$userRole = $roleStmt->fetchColumn();

if (!in_array($userRole, ['ADMIN', 'HR'])) {
    header('Location: ../employees.php?error=' . urlencode('Permission denied. Only ADMIN and HR can add employees.'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../employees.php?error=' . urlencode('Invalid request'));
    exit;
}

$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$date_of_joining = $_POST['date_of_joining'] ?? date('Y-m-d');
$gender = trim($_POST['gender'] ?? '');
$role_id = (int)($_POST['role_id'] ?? 3); // Default to EMPLOYEE
$password = $_POST['password'] ?? '';

// Salary fields
$monthly_wage = isset($_POST['monthly_wage']) ? (float)$_POST['monthly_wage'] : 0;
$yearly_wage = isset($_POST['yearly_wage']) ? (float)$_POST['yearly_wage'] : 0;
$working_days_per_week = isset($_POST['working_days_per_week']) ? (float)$_POST['working_days_per_week'] : null;
$break_time_hours = isset($_POST['break_time_hours']) ? (float)$_POST['break_time_hours'] : null;
$basic_salary = isset($_POST['basic_salary']) ? (float)$_POST['basic_salary'] : 0;
$basic_salary_percent = isset($_POST['basic_salary_percent']) ? (float)$_POST['basic_salary_percent'] : 50.00;
$hra = isset($_POST['hra']) ? (float)$_POST['hra'] : 0;
$hra_percent = isset($_POST['hra_percent']) ? (float)$_POST['hra_percent'] : 50.00;
$standard_allowance = isset($_POST['standard_allowance']) ? (float)$_POST['standard_allowance'] : 0;
$standard_allowance_percent = isset($_POST['standard_allowance_percent']) ? (float)$_POST['standard_allowance_percent'] : 16.67;
$performance_bonus = isset($_POST['performance_bonus']) ? (float)$_POST['performance_bonus'] : 0;
$performance_bonus_percent = isset($_POST['performance_bonus_percent']) ? (float)$_POST['performance_bonus_percent'] : 8.33;
$lta = isset($_POST['lta']) ? (float)$_POST['lta'] : 0;
$lta_percent = isset($_POST['lta_percent']) ? (float)$_POST['lta_percent'] : 8.33;
$fixed_allowance = isset($_POST['fixed_allowance']) ? (float)$_POST['fixed_allowance'] : 0;
$pf_employee = isset($_POST['pf_employee']) ? (float)$_POST['pf_employee'] : 0;
$pf_employee_percent = isset($_POST['pf_employee_percent']) ? (float)$_POST['pf_employee_percent'] : 12.00;
$pf_employer = isset($_POST['pf_employer']) ? (float)$_POST['pf_employer'] : 0;
$pf_employer_percent = isset($_POST['pf_employer_percent']) ? (float)$_POST['pf_employer_percent'] : 12.00;
$professional_tax = isset($_POST['professional_tax']) ? (float)$_POST['professional_tax'] : 200.00;

if (!$first_name || !$last_name || !$email || !$phone || !$password || !$gender) {
    header('Location: ../employees.php?error=' . urlencode('All required fields must be filled'));
    exit;
}

if (!in_array($gender, ['Male', 'Female', 'Other'])) {
    header('Location: ../employees.php?error=' . urlencode('Invalid gender selected'));
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ../employees.php?error=' . urlencode('Invalid email'));
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Check if email or phone already exists
    $check = $pdo->prepare("SELECT employee_id FROM employees WHERE LOWER(email) = LOWER(?) OR phone = ?");
    $check->execute([$email, $phone]);
    if ($check->fetch()) {
        $pdo->rollBack();
        header('Location: ../employees.php?error=' . urlencode('Email or phone already exists'));
        exit;
    }
    
    // Insert employee
    $stmt = $pdo->prepare("
        INSERT INTO employees (company_id, first_name, last_name, email, phone, gender, date_of_joining) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$company_id, $first_name, $last_name, $email, $phone, $gender, $date_of_joining]);
    $employee_id = $pdo->lastInsertId();
    
    // Generate login ID
    $companyStmt = $pdo->prepare("SELECT company_code FROM companies WHERE company_id = ?");
    $companyStmt->execute([$company_id]);
    $company_code = $companyStmt->fetchColumn();
    
    if (!$company_code) {
        $pdo->rollBack();
        header('Location: ../employees.php?error=' . urlencode('Company code not found. Please contact administrator.'));
        exit;
    }
    
    $roleStmt = $pdo->prepare("SELECT role_name FROM roles WHERE role_id = ?");
    $roleStmt->execute([$role_id]);
    $role_name = $roleStmt->fetchColumn();
    
    if (!$role_name) {
        $pdo->rollBack();
        header('Location: ../employees.php?error=' . urlencode('Invalid role selected.'));
        exit;
    }
    
    $role_prefix = strtoupper(substr($role_name, 0, 2));
    
    // Create user account
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $login_id = $company_code . $role_prefix . str_pad($employee_id, 4, '0', STR_PAD_LEFT);
    
    $userStmt = $pdo->prepare("
        INSERT INTO users (employee_id, company_id, role_id, login_id, password_hash, is_first_login) 
        VALUES (?, ?, ?, ?, ?, 1)
    ");
    $userStmt->execute([$employee_id, $company_id, $role_id, $login_id, $password_hash]);
    
    // Insert salary information
    $salaryStmt = $pdo->prepare("
        INSERT INTO employee_salary (
            employee_id, monthly_wage, yearly_wage, working_days_per_week, break_time_hours,
            basic_salary, basic_salary_percent, hra, hra_percent,
            standard_allowance, standard_allowance_percent,
            performance_bonus, performance_bonus_percent,
            lta, lta_percent, fixed_allowance,
            pf_employee, pf_employee_percent, pf_employer, pf_employer_percent,
            professional_tax
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $salaryStmt->execute([
        $employee_id, $monthly_wage, $yearly_wage, $working_days_per_week, $break_time_hours,
        $basic_salary, $basic_salary_percent, $hra, $hra_percent,
        $standard_allowance, $standard_allowance_percent,
        $performance_bonus, $performance_bonus_percent,
        $lta, $lta_percent, $fixed_allowance,
        $pf_employee, $pf_employee_percent, $pf_employer, $pf_employer_percent,
        $professional_tax
    ]);
    
    $pdo->commit();
    header('Location: ../employees.php?success=' . urlencode('Employee added successfully. Login ID: ' . $login_id));
    exit;
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Add employee error: ' . $e->getMessage());
    $errorMsg = 'Failed to add employee: ' . $e->getMessage();
    // Truncate error message if too long
    if (strlen($errorMsg) > 200) {
        $errorMsg = 'Failed to add employee. Please check server logs for details.';
    }
    header('Location: ../employees.php?error=' . urlencode($errorMsg));
    exit;
}

