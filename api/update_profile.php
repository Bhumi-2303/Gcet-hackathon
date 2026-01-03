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

$allowedFields = ['about', 'job_love', 'interests_hobbies', 'department', 'location', 'manager_id'];
if (!in_array($field, $allowedFields)) {
    echo json_encode(['ok' => false, 'error' => 'Invalid field']);
    exit;
}

try {
    $pdo = getPDO();
    
    // Check if profile exists
    $checkStmt = $pdo->prepare("SELECT profile_id FROM employee_profiles WHERE employee_id = ?");
    $checkStmt->execute([$employee_id]);
    $profile = $checkStmt->fetch();
    
    if ($profile) {
        // Update existing profile
        $stmt = $pdo->prepare("UPDATE employee_profiles SET `$field` = ? WHERE employee_id = ?");
        $stmt->execute([$value, $employee_id]);
    } else {
        // Create new profile
        $stmt = $pdo->prepare("INSERT INTO employee_profiles (employee_id, `$field`) VALUES (?, ?)");
        $stmt->execute([$employee_id, $value]);
    }
    
    echo json_encode(['ok' => true, 'message' => 'Profile updated successfully']);
    
} catch (Exception $e) {
    error_log('Update profile error: ' . $e->getMessage());
    echo json_encode(['ok' => false, 'error' => 'Failed to update profile']);
}

