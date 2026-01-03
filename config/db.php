<?php
// Simple PDO helper for local development with XAMPP
// Update these constants if your MySQL credentials differ
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'hrms');
define('DB_USER', 'root');
define('DB_PASS', 'root');

define('DB_DSN', 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4');

function getPDO(){
  static $pdo = null;
  if ($pdo) return $pdo;
  try {
    $pdo = new PDO(DB_DSN, DB_USER, DB_PASS, [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    return $pdo;
  } catch (PDOException $e) {
    // In production, don't reveal details
    die('Database connection failed: ' . $e->getMessage());
  }
}
