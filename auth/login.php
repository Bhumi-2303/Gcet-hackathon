<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: ../signin.php');
  exit;
}
require_once __DIR__ . '/../config/db.php';
$ident = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
if (!$ident || !$password) {
  header('Location: ../signin.php?error=' . urlencode('Please provide both email/login and password.'));
  exit;
}
try {
  $pdo = getPDO();
  $sql = "SELECT u.*, e.first_name, e.last_name, e.email AS emp_email, c.company_name, c.logo_url
          FROM users u
          LEFT JOIN employees e ON e.employee_id = u.employee_id
          LEFT JOIN companies c ON c.company_id = u.company_id
          WHERE LOWER(u.login_id) = LOWER(:ident) OR LOWER(e.email) = LOWER(:ident) LIMIT 1";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([':ident' => $ident]);
  $row = $stmt->fetch();
  if (!$row || !password_verify($password, $row['password_hash'])) {
    // log failed attempt
    $log = $pdo->prepare('INSERT INTO login_audit (user_id, ip_address, status) VALUES (:uid, :ip, :st)');
    $log->execute([':uid' => $row['user_id'] ?? null, ':ip' => $_SERVER['REMOTE_ADDR'] ?? null, ':st' => 'FAILED']);

    header('Location: ../signin.php?error=' . urlencode('Invalid credentials.'));
    exit;
  }

  // success: update last_login and write audit
  $upd = $pdo->prepare('UPDATE users SET last_login = NOW() WHERE user_id = :id');
  $upd->execute([':id' => $row['user_id']]);
  $log = $pdo->prepare('INSERT INTO login_audit (user_id, ip_address, status) VALUES (:uid, :ip, :st)');
  $log->execute([':uid' => $row['user_id'], ':ip' => $_SERVER['REMOTE_ADDR'] ?? null, ':st' => 'SUCCESS']);

  $_SESSION['user'] = [
    'name' => trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')),
    'company' => $row['company_name'] ?? '',
    'email' => $row['emp_email'] ?? '',
    'logo' => $row['logo_url'] ?? ''
  ];
  header('Location: ../welcome.php');
  exit;
} catch (Exception $e) {
  error_log('Login error: ' . $e->getMessage());
  header('Location: ../signin.php?error=' . urlencode('Login error.'));
  exit;
}