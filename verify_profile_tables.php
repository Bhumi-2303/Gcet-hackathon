<?php
require_once 'config/db.php';

echo "Checking profile tables...\n\n";

try {
    $pdo = getPDO();
    
    $tables = ['employee_profiles', 'employee_skills', 'employee_certifications', 'employee_salary'];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✓ Table '$table' exists\n";
        } else {
            echo "✗ Table '$table' does NOT exist\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

