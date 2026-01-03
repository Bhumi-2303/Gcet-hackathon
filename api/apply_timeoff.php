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
$employee_id = $user['employee_id'] ?? null;

if (!$employee_id) {
    echo json_encode(['ok' => false, 'error' => 'Employee ID not found']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'error' => 'Invalid request method']);
    exit;
}

$type = trim($_POST['type'] ?? '');
$start_date = $_POST['start_date'] ?? '';
$end_date = $_POST['end_date'] ?? '';
$reason = trim($_POST['reason'] ?? '');

// Validation
if (!$type || !$start_date || !$end_date) {
    echo json_encode(['ok' => false, 'error' => 'All required fields must be filled']);
    exit;
}

if (!in_array($type, ['Paid Time Off', 'Sick Leave', 'Unpaid Leave'])) {
    echo json_encode(['ok' => false, 'error' => 'Invalid time off type']);
    exit;
}

// Validate dates
if (strtotime($start_date) > strtotime($end_date)) {
    echo json_encode(['ok' => false, 'error' => 'End date must be after start date']);
    exit;
}

if (strtotime($start_date) < strtotime('today')) {
    echo json_encode(['ok' => false, 'error' => 'Start date cannot be in the past']);
    exit;
}

try {
    $pdo = getPDO();
    
    // Check if time_off table exists, create if not
    $tableExists = false;
    try {
        $check = $pdo->query("SHOW TABLES LIKE 'time_off'");
        if ($check->rowCount() > 0) {
            $columns = $pdo->query("SHOW COLUMNS FROM time_off LIKE 'time_off_id'");
            if ($columns->rowCount() > 0) {
                $tableExists = true;
            } else {
                $pdo->exec("DROP TABLE IF EXISTS time_off");
            }
        }
    } catch (PDOException $e) {
        // Table doesn't exist
    }
    
    if (!$tableExists) {
        $pdo->exec("
            CREATE TABLE `time_off` (
              `time_off_id` int NOT NULL AUTO_INCREMENT,
              `employee_id` int NOT NULL,
              `type` enum('Paid Time Off','Sick Leave','Unpaid Leave') NOT NULL,
              `start_date` date NOT NULL,
              `end_date` date NOT NULL,
              `reason` text,
              `attachment_url` varchar(255) DEFAULT NULL,
              `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
              `approved_by` int DEFAULT NULL,
              `approved_at` timestamp NULL DEFAULT NULL,
              `rejection_reason` text DEFAULT NULL,
              `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`time_off_id`),
              KEY `employee_id` (`employee_id`),
              KEY `status` (`status`),
              KEY `start_date` (`start_date`),
              CONSTRAINT `time_off_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
        ");
    }
    
    // Handle file upload if provided
    $attachment_url = null;
    if (!empty($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../uploads/timeoff';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $f = $_FILES['attachment'];
        $allowed = ['application/pdf', 'image/png', 'image/jpeg', 'image/jpg'];
        if (!in_array($f['type'], $allowed)) {
            echo json_encode(['ok' => false, 'error' => 'Invalid file type. Only PDF and images are allowed.']);
            exit;
        }
        
        if ($f['size'] > 5 * 1024 * 1024) { // 5MB max
            echo json_encode(['ok' => false, 'error' => 'File size too large. Maximum 5MB allowed.']);
            exit;
        }
        
        $ext = pathinfo($f['name'], PATHINFO_EXTENSION);
        $filename = uniqid('timeoff_') . '.' . $ext;
        $dest = $uploadDir . '/' . $filename;
        
        if (move_uploaded_file($f['tmp_name'], $dest)) {
            $attachment_url = 'uploads/timeoff/' . $filename;
        }
    }
    
    // Insert time off request
    $stmt = $pdo->prepare("
        INSERT INTO time_off (employee_id, type, start_date, end_date, reason, attachment_url, status) 
        VALUES (?, ?, ?, ?, ?, ?, 'Pending')
    ");
    $stmt->execute([$employee_id, $type, $start_date, $end_date, $reason, $attachment_url]);
    
    echo json_encode([
        'ok' => true, 
        'message' => 'Time off request submitted successfully',
        'time_off_id' => $pdo->lastInsertId()
    ]);
    
} catch (Exception $e) {
    error_log('Time off application error: ' . $e->getMessage());
    echo json_encode(['ok' => false, 'error' => 'Failed to submit time off request']);
}

