<?php
require_once 'config/db.php';

try {
    $pdo = getPDO();
    
    // Check if gender column exists
    $check = $pdo->query("SHOW COLUMNS FROM employees LIKE 'gender'");
    if ($check->rowCount() == 0) {
        // Add gender column
        $pdo->exec("ALTER TABLE employees ADD COLUMN gender ENUM('Male', 'Female', 'Other') DEFAULT NULL AFTER phone");
        echo "Gender column added successfully!\n";
    } else {
        echo "Gender column already exists.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

