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

$certification_name = trim($_POST['certification_name'] ?? '');
$issuing_organization = trim($_POST['issuing_organization'] ?? '');
$issue_date = trim($_POST['issue_date'] ?? '');

if (!$certification_name) {
    echo json_encode(['ok' => false, 'error' => 'Certification name required']);
    exit;
}

try {
    $pdo = getPDO();
    
    // Add certification
    $stmt = $pdo->prepare("
        INSERT INTO employee_certifications (employee_id, certification_name, issuing_organization, issue_date) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([
        $employee_id, 
        $certification_name, 
        $issuing_organization ?: null, 
        $issue_date ?: null
    ]);
    
    echo json_encode(['ok' => true, 'message' => 'Certification added successfully']);
    
} catch (Exception $e) {
    error_log('Add certification error: ' . $e->getMessage());
    echo json_encode(['ok' => false, 'error' => 'Failed to add certification']);
}

