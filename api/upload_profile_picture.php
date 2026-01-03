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

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['profile_picture'])) {
    echo json_encode(['ok' => false, 'error' => 'Invalid request']);
    exit;
}

$file = $_FILES['profile_picture'];
$allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
$maxSize = 5 * 1024 * 1024; // 5MB

if (!in_array($file['type'], $allowed)) {
    echo json_encode(['ok' => false, 'error' => 'Invalid file type. Only images are allowed.']);
    exit;
}

if ($file['size'] > $maxSize) {
    echo json_encode(['ok' => false, 'error' => 'File size too large. Maximum 5MB allowed.']);
    exit;
}

try {
    $uploadDir = __DIR__ . '/../uploads/profiles';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'profile_' . $employee_id . '_' . uniqid() . '.' . $ext;
    $dest = $uploadDir . '/' . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $dest)) {
        $url = 'uploads/profiles/' . $filename;
        
        // Update database
        $pdo = getPDO();
        $checkStmt = $pdo->prepare("SELECT profile_id FROM employee_profiles WHERE employee_id = ?");
        $checkStmt->execute([$employee_id]);
        $profile = $checkStmt->fetch();
        
        if ($profile) {
            // Delete old picture if exists
            $oldStmt = $pdo->prepare("SELECT profile_picture FROM employee_profiles WHERE employee_id = ?");
            $oldStmt->execute([$employee_id]);
            $oldPic = $oldStmt->fetchColumn();
            if ($oldPic && file_exists(__DIR__ . '/../' . $oldPic)) {
                unlink(__DIR__ . '/../' . $oldPic);
            }
            
            $stmt = $pdo->prepare("UPDATE employee_profiles SET profile_picture = ? WHERE employee_id = ?");
            $stmt->execute([$url, $employee_id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO employee_profiles (employee_id, profile_picture) VALUES (?, ?)");
            $stmt->execute([$employee_id, $url]);
        }
        
        echo json_encode(['ok' => true, 'url' => $url, 'message' => 'Profile picture uploaded successfully']);
    } else {
        echo json_encode(['ok' => false, 'error' => 'Failed to upload file']);
    }
    
} catch (Exception $e) {
    error_log('Upload profile picture error: ' . $e->getMessage());
    echo json_encode(['ok' => false, 'error' => 'Failed to upload profile picture']);
}

