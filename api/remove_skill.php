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

$skill_name = trim($_POST['skill_name'] ?? '');

if (!$skill_name) {
    echo json_encode(['ok' => false, 'error' => 'Skill name required']);
    exit;
}

try {
    $pdo = getPDO();
    
    // Remove skill
    $stmt = $pdo->prepare("DELETE FROM employee_skills WHERE employee_id = ? AND skill_name = ?");
    $stmt->execute([$employee_id, $skill_name]);
    
    echo json_encode(['ok' => true, 'message' => 'Skill removed successfully']);
    
} catch (Exception $e) {
    error_log('Remove skill error: ' . $e->getMessage());
    echo json_encode(['ok' => false, 'error' => 'Failed to remove skill']);
}

