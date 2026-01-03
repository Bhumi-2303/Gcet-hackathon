<?php
session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['ok' => false, 'error' => 'Not authenticated']);
    exit;
}

$user = $_SESSION['user'];
$employee_id = $user['employee_id'] ?? null;

if (!$employee_id) {
    echo json_encode(['ok' => false, 'error' => 'Employee ID not found']);
    exit;
}

// Check if user is admin
$pdo = getPDO();
$roleStmt = $pdo->prepare("SELECT role_name FROM roles WHERE role_id = ?");
$roleStmt->execute([$user['role_id'] ?? null]);
$userRole = $roleStmt->fetchColumn();

if ($userRole !== 'ADMIN') {
    echo json_encode(['ok' => false, 'error' => 'Permission denied. Only admins can update salary.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'error' => 'Invalid request method']);
    exit;
}

$field = trim($_POST['field'] ?? '');
$value = trim($_POST['value'] ?? '');

if (!$field) {
    echo json_encode(['ok' => false, 'error' => 'Field name required']);
    exit;
}

$allowedFields = [
    'monthly_wage', 'yearly_wage', 'working_days_per_week', 'break_time_hours',
    'basic_salary', 'basic_salary_percent', 'hra', 'hra_percent',
    'standard_allowance', 'standard_allowance_percent',
    'performance_bonus', 'performance_bonus_percent',
    'lta', 'lta_percent', 'fixed_allowance',
    'pf_employee', 'pf_employee_percent', 'pf_employer', 'pf_employer_percent',
    'professional_tax'
];

if (!in_array($field, $allowedFields)) {
    echo json_encode(['ok' => false, 'error' => 'Invalid field']);
    exit;
}

try {
    // Check if salary record exists
    $checkStmt = $pdo->prepare("SELECT salary_id FROM employee_salary WHERE employee_id = ?");
    $checkStmt->execute([$employee_id]);
    $salary = $checkStmt->fetch();
    
    if ($salary) {
        // Update existing salary
        $stmt = $pdo->prepare("UPDATE employee_salary SET `$field` = ? WHERE employee_id = ?");
        $stmt->execute([$value ?: 0, $employee_id]);
    } else {
        // Create new salary record
        $stmt = $pdo->prepare("INSERT INTO employee_salary (employee_id, `$field`) VALUES (?, ?)");
        $stmt->execute([$employee_id, $value ?: 0]);
    }
    
    echo json_encode(['ok' => true, 'message' => 'Salary updated successfully']);
    
} catch (Exception $e) {
    error_log('Update salary error: ' . $e->getMessage());
    echo json_encode(['ok' => false, 'error' => 'Failed to update salary']);
}

