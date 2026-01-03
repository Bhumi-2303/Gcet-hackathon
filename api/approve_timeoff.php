<?php
session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

// Check if user is authenticated
if (!isset($_SESSION['user'])) {
    echo json_encode(['ok' => false, 'error' => 'Not authenticated']);
    exit;
}

$user = $_SESSION['user'];
$userRoleId = $user['role_id'] ?? null;

// Check if user is ADMIN or HR
$pdo = getPDO();
$userRole = null;
if ($userRoleId) {
    $roleStmt = $pdo->prepare("SELECT role_name FROM roles WHERE role_id = ?");
    $roleStmt->execute([$userRoleId]);
    $userRole = $roleStmt->fetchColumn();
}

if (!in_array($userRole, ['ADMIN', 'HR'])) {
    echo json_encode(['ok' => false, 'error' => 'Permission denied']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'error' => 'Invalid request method']);
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$action = trim($_POST['action'] ?? '');
$reason = trim($_POST['reason'] ?? '');

if (!$id || !in_array($action, ['approve', 'reject'])) {
    echo json_encode(['ok' => false, 'error' => 'Invalid parameters']);
    exit;
}

try {
    // Verify time off request exists and belongs to same company
    $check = $pdo->prepare("
        SELECT t.time_off_id, t.status, e.company_id 
        FROM time_off t
        JOIN employees e ON t.employee_id = e.employee_id
        WHERE t.time_off_id = ?
    ");
    $check->execute([$id]);
    $record = $check->fetch();
    
    if (!$record) {
        echo json_encode(['ok' => false, 'error' => 'Time off request not found']);
        exit;
    }
    
    if ($record['company_id'] != $user['company_id']) {
        echo json_encode(['ok' => false, 'error' => 'Permission denied']);
        exit;
    }
    
    if ($record['status'] !== 'Pending') {
        echo json_encode(['ok' => false, 'error' => 'Request already processed']);
        exit;
    }
    
    if ($action === 'approve') {
        $stmt = $pdo->prepare("
            UPDATE time_off 
            SET status = 'Approved', 
                approved_by = ?, 
                approved_at = NOW() 
            WHERE time_off_id = ?
        ");
        $stmt->execute([$user['user_id'], $id]);
    } else {
        if (!$reason) {
            echo json_encode(['ok' => false, 'error' => 'Rejection reason is required']);
            exit;
        }
        $stmt = $pdo->prepare("
            UPDATE time_off 
            SET status = 'Rejected', 
                approved_by = ?, 
                approved_at = NOW(),
                rejection_reason = ?
            WHERE time_off_id = ?
        ");
        $stmt->execute([$user['user_id'], $reason, $id]);
    }
    
    echo json_encode(['ok' => true, 'message' => 'Request ' . $action . 'd successfully']);
    
} catch (Exception $e) {
    error_log('Time off approval error: ' . $e->getMessage());
    echo json_encode(['ok' => false, 'error' => 'Failed to process request']);
}

