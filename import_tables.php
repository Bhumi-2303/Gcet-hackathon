<?php
/**
 * Import missing SQL tables
 * This script will import attendance_table.sql and time_off_table.sql
 */

require_once 'config/db.php';

echo "========================================\n";
echo "Importing Missing Tables\n";
echo "========================================\n\n";

try {
    $pdo = getPDO();
    
    // Import attendance table
    echo "1. Importing attendance table...\n";
    $attendanceSQL = file_get_contents('attendance_table.sql');
    if ($attendanceSQL) {
        $pdo->exec($attendanceSQL);
        echo "   ✓ Attendance table imported successfully\n\n";
    } else {
        echo "   ✗ Could not read attendance_table.sql\n\n";
    }
    
    // Import time_off table
    echo "2. Importing time_off table...\n";
    $timeOffSQL = file_get_contents('time_off_table.sql');
    if ($timeOffSQL) {
        $pdo->exec($timeOffSQL);
        echo "   ✓ Time off table imported successfully\n\n";
    } else {
        echo "   ✗ Could not read time_off_table.sql\n\n";
    }
    
    // Verify tables were created
    echo "3. Verifying tables...\n";
    $tables = ['attendance', 'time_off'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "   ✓ Table '$table' exists\n";
        } else {
            echo "   ✗ Table '$table' still missing\n";
        }
    }
    
    echo "\n========================================\n";
    echo "Import Complete!\n";
    echo "========================================\n";
    echo "All tables have been imported successfully.\n";
    echo "You can now run setup.php again to verify everything is set up correctly.\n\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "\nIf you see SQL syntax errors, you may need to import the files manually using phpMyAdmin.\n";
}

