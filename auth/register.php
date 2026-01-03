<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: ../signup.php');
  exit;
}
require_once __DIR__ . '/../config/db.php';
$company = trim($_POST['company'] ?? '');
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';
if (!$company || !$name || !$email || !$password || !$phone) {
  header('Location: ../signup.php?error=' . urlencode('Please fill out all required fields.'));
  exit;
}
if ($password !== $confirm) {
  header('Location: ../signup.php?error=' . urlencode('Passwords do not match.'));
  exit;
}
// Basic email validation
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  header('Location: ../signup.php?error=' . urlencode('Invalid email.'));
  exit;
}
// Handle logo upload (required)
$uploadDir = __DIR__ . '/../uploads';
if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }
$logoPath = '';
if (!empty($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
  $f = $_FILES['logo'];
  $allowed = ['image/png','image/jpeg','image/svg+xml','image/webp'];
  if (!in_array($f['type'], $allowed)) {
    header('Location: ../signup.php?error=' . urlencode('Unsupported logo file type.'));
    exit;
  }
  if ($f['size'] > 2 * 1024 * 1024) {
    header('Location: ../signup.php?error=' . urlencode('Logo is too large (max 2MB).'));
    exit;
  }
  $ext = pathinfo($f['name'], PATHINFO_EXTENSION);
  $dest = $uploadDir . '/' . uniqid('logo_') . '.' . $ext;
  if (!move_uploaded_file($f['tmp_name'], $dest)) {
    header('Location: ../signup.php?error=' . urlencode('Failed to save logo.'));
    exit;
  }
  $logoPath = 'uploads/' . basename($dest);
} else {
  header('Location: ../signup.php?error=' . urlencode('Please upload a company logo.'));
  exit;
}

// Insert into database (hrms)
$pdo = getPDO();
// check existing employee email / phone to provide friendly error
$check = $pdo->prepare('SELECT employee_id FROM employees WHERE LOWER(email) = LOWER(:email) OR phone = :phone LIMIT 1');
$check->execute([':email' => $email, ':phone' => $phone]);
if ($check->fetch()) {
  // cleanup uploaded logo
  if ($logoPath && file_exists(__DIR__ . '/../' . $logoPath)) { @unlink(__DIR__ . '/../' . $logoPath); }
  header('Location: ../signup.php?error=' . urlencode('An account with that email or phone already exists.'));
  exit;
}
try {
  $pdo->beginTransaction();

  // generate company code (simple unique code from name)
  $code = strtoupper(preg_replace('/[^A-Z]/','',substr($company,0,4)));
  if (!$code) { $code = strtoupper(substr(preg_replace('/[^A-Z0-9]/','',$company),0,3)) ?: 'CMP'; }
  // ensure uniqueness by appending number if needed
  $baseCode = $code; $i = 1;
  while (true) {
    $stmt = $pdo->prepare('SELECT company_id FROM companies WHERE company_code = ?');
    $stmt->execute([$code]);
    if (!$stmt->fetch()) break;
    $code = $baseCode . $i; $i++;
  }

  // create company
  $stmt = $pdo->prepare('INSERT INTO companies (company_name, company_code, logo_url) VALUES (:name,:code,:logo)');
  $stmt->execute([':name' => $company, ':code' => $code, ':logo' => $logoPath]);
  $company_id = $pdo->lastInsertId();

  // split full name into first/last
  $parts = preg_split('/\s+/', $name, 2);
  $first = $parts[0] ?? $name;
  $last = $parts[1] ?? '';

  // create employee
  $stmt = $pdo->prepare('INSERT INTO employees (company_id, first_name, last_name, email, phone, date_of_joining) VALUES (:company_id,:first,:last,:email,:phone,:doj)');
  $stmt->execute([
    ':company_id' => $company_id,
    ':first' => $first,
    ':last' => $last,
    ':email' => $email,
    ':phone' => $phone,
    ':doj' => date('Y-m-d')
  ]);
  $employee_id = $pdo->lastInsertId();

  // assign ADMIN role for initial account
  $role_id = 1;

  // create user with a placeholder login_id
  $password_hash = password_hash($password, PASSWORD_DEFAULT);
  $stmt = $pdo->prepare('INSERT INTO users (employee_id, company_id, role_id, login_id, password_hash) VALUES (:emp,:company,:role,:login,:pw)');
  $placeholder_login = $code . 'ADMIN';
  $stmt->execute([':emp' => $employee_id, ':company' => $company_id, ':role' => $role_id, ':login' => $placeholder_login, ':pw' => $password_hash]);
  $user_id = $pdo->lastInsertId();

  // finalize login id (e.g., OIADMIN0001)
  $final_login = $code . 'ADMIN' . str_pad($user_id, 4, '0', STR_PAD_LEFT);
  $stmt = $pdo->prepare('UPDATE users SET login_id = :login WHERE user_id = :id');
  $stmt->execute([':login' => $final_login, ':id' => $user_id]);

  $pdo->commit();
  header('Location: ../signin.php?success=' . urlencode('Account created — you can sign in now. Your login id: ' . $final_login));
  exit;
} catch (Exception $e) {
  $pdo->rollBack();
  // rollback upload if saved
  if ($logoPath && file_exists(__DIR__ . '/../' . $logoPath)) { @unlink(__DIR__ . '/../' . $logoPath); }
  error_log('Register error: ' . $e->getMessage());
  header('Location: ../signup.php?error=' . urlencode('Registration failed. Try again.'));
  exit;
}