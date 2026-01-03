<?php
session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user'])) {
    echo json_encode(['ok' => false, 'error' => 'Not authenticated']);
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
    echo json_encode(['ok' => false, 'error' => 'Permission denied']);
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
$role_id = (int)($_POST['role_id'] ?? 3); // Default to EMPLOYEE
$password = $_POST['password'] ?? '';

if (!$first_name || !$last_name || !$email || !$phone || !$password) {
    header('Location: ../employees.php?error=' . urlencode('All fields are required'));
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
        INSERT INTO employees (company_id, first_name, last_name, email, phone, date_of_joining) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$company_id, $first_name, $last_name, $email, $phone, $date_of_joining]);
    $employee_id = $pdo->lastInsertId();
    
    // Generate login ID
    $companyStmt = $pdo->prepare("SELECT company_code FROM companies WHERE company_id = ?");
    $companyStmt->execute([$company_id]);
    $company_code = $companyStmt->fetchColumn();
    
    $roleStmt = $pdo->prepare("SELECT role_name FROM roles WHERE role_id = ?");
    $roleStmt->execute([$role_id]);
    $role_name = $roleStmt->fetchColumn();
    $role_prefix = strtoupper(substr($role_name, 0, 2));
    
    // Create user account
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $login_id = $company_code . $role_prefix . str_pad($employee_id, 4, '0', STR_PAD_LEFT);
    
    $userStmt = $pdo->prepare("
        INSERT INTO users (employee_id, company_id, role_id, login_id, password_hash, is_first_login) 
        VALUES (?, ?, ?, ?, ?, 1)
    ");
    $userStmt->execute([$employee_id, $company_id, $role_id, $login_id, $password_hash]);
    
    $pdo->commit();
    header('Location: ../employees.php?success=' . urlencode('Employee added successfully. Login ID: ' . $login_id));
    exit;
    
} catch (Exception $e) {
    $pdo->rollBack();
    error_log('Add employee error: ' . $e->getMessage());
    header('Location: ../employees.php?error=' . urlencode('Failed to add employee'));
    exit;
}

