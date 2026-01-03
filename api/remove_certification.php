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

$certification_id = (int)($_POST['certification_id'] ?? 0);

if (!$certification_id) {
    echo json_encode(['ok' => false, 'error' => 'Certification ID required']);
    exit;
}

try {
    $pdo = getPDO();
    
    // Verify ownership
    $checkStmt = $pdo->prepare("SELECT certification_id FROM employee_certifications WHERE certification_id = ? AND employee_id = ?");
    $checkStmt->execute([$certification_id, $employee_id]);
    if (!$checkStmt->fetch()) {
        echo json_encode(['ok' => false, 'error' => 'Certification not found or access denied']);
        exit;
    }
    
    // Remove certification
    $stmt = $pdo->prepare("DELETE FROM employee_certifications WHERE certification_id = ? AND employee_id = ?");
    $stmt->execute([$certification_id, $employee_id]);
    
    echo json_encode(['ok' => true, 'message' => 'Certification removed successfully']);
    
} catch (Exception $e) {
    error_log('Remove certification error: ' . $e->getMessage());
    echo json_encode(['ok' => false, 'error' => 'Failed to remove certification']);
}

