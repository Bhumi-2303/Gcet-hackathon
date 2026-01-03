<?php
session_start();
header('Content-Type: application/json');

// Check if user is authenticated
if (!isset($_SESSION['user'])) {
    echo json_encode(['ok' => false, 'error' => 'Not authenticated']);
    exit;
}

require_once __DIR__ . '/../config/db.php';

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

$type = strtoupper(trim($_POST['type'] ?? ''));

if (!in_array($type, ['IN', 'OUT'])) {
    echo json_encode(['ok' => false, 'error' => 'Invalid attendance type']);
    exit;
}

try {
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
            echo json_encode(['ok' => false, 'error' => 'Database error. Please contact administrator.']);
            exit;
        }
    }
    
    // Check if user is already checked in (for OUT) or already checked out (for IN)
    $latest = $pdo->prepare("
        SELECT type, created_at 
        FROM attendance 
        WHERE employee_id = ? 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $latest->execute([$employee_id]);
    $lastRecord = $latest->fetch();
    
    // Validation: Can't check in if already checked in (unless it's a different day)
    if ($lastRecord) {
        $lastDate = date('Y-m-d', strtotime($lastRecord['created_at']));
        $today = date('Y-m-d');
        
        if ($type === 'IN' && $lastRecord['type'] === 'IN' && $lastDate === $today) {
            echo json_encode(['ok' => false, 'error' => 'You are already checked in today']);
            exit;
        }
        
        if ($type === 'OUT' && ($lastRecord['type'] === 'OUT' || $lastDate !== $today)) {
            if ($lastRecord['type'] === 'OUT') {
                echo json_encode(['ok' => false, 'error' => 'You are already checked out']);
                exit;
            }
            // If last record is IN but from a different day, allow check in first
            if ($lastDate !== $today) {
                echo json_encode(['ok' => false, 'error' => 'Please check in first']);
                exit;
            }
        }
    } else if ($type === 'OUT') {
        // Can't check out without checking in first
        echo json_encode(['ok' => false, 'error' => 'Please check in first']);
        exit;
    }
    
    // Insert attendance record
    $stmt = $pdo->prepare("
        INSERT INTO attendance (employee_id, type, ip_address) 
        VALUES (?, ?, ?)
    ");
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $stmt->execute([$employee_id, $type, $ip]);
    
    echo json_encode([
        'ok' => true, 
        'message' => 'Attendance recorded successfully',
        'type' => $type,
        'time' => date('H:i:s')
    ]);
    
} catch (Exception $e) {
    error_log('Attendance error: ' . $e->getMessage());
    echo json_encode(['ok' => false, 'error' => 'Failed to record attendance']);
}

