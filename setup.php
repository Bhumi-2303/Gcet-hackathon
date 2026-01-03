<?php
/**
 * HRMS Setup Script
 * This script will help you set up the database and verify the configuration
 */

require_once 'config/db.php';

echo "========================================\n";
echo "HRMS System Setup\n";
echo "========================================\n\n";

// Check database connection
echo "1. Checking database connection...\n";
try {
    $pdo = getPDO();
    echo "   ✓ Database connection successful!\n\n";
} catch (Exception $e) {
    echo "   ✗ Database connection failed: " . $e->getMessage() . "\n";
    echo "   Please check your database credentials in config/db.php\n";
    exit(1);
}

// Check if database exists
echo "2. Checking if database 'hrms' exists...\n";
try {
    $pdo->exec("USE hrms");
    echo "   ✓ Database 'hrms' exists\n\n";
} catch (Exception $e) {
    echo "   ✗ Database 'hrms' does not exist\n";
    echo "   Please import hrms.sql first using phpMyAdmin or command line:\n";
    echo "   mysql -u root -p < hrms.sql\n\n";
    exit(1);
}

// Check for required tables
echo "3. Checking required tables...\n";
$requiredTables = [
    'companies',
    'roles',
    'employees',
    'users',
    'login_audit',
    'attendance',
    'time_off'
];

$missingTables = [];
foreach ($requiredTables as $table) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "   ✓ Table '$table' exists\n";
        } else {
            echo "   ✗ Table '$table' is missing\n";
            $missingTables[] = $table;
        }
    } catch (Exception $e) {
        echo "   ✗ Error checking table '$table': " . $e->getMessage() . "\n";
        $missingTables[] = $table;
    }
}

echo "\n";

// Provide instructions for missing tables
if (!empty($missingTables)) {
    echo "4. Missing tables detected. Please run the following SQL files:\n";
    if (in_array('attendance', $missingTables)) {
        echo "   - attendance_table.sql\n";
    }
    if (in_array('time_off', $missingTables)) {
        echo "   - time_off_table.sql\n";
    }
    if (count($missingTables) > 2) {
        echo "   - hrms.sql (main database)\n";
    }
    echo "\n";
    echo "   You can import them using:\n";
    echo "   - phpMyAdmin: Go to http://localhost/phpmyadmin, select 'hrms' database, click 'Import'\n";
    echo "   - Command line: mysql -u root -p hrms < [filename].sql\n";
    echo "\n";
} else {
    echo "4. All required tables exist! ✓\n\n";
}

// Check for default roles
echo "5. Checking default roles...\n";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM roles");
    $result = $stmt->fetch();
    if ($result['count'] >= 3) {
        echo "   ✓ Default roles are set up\n\n";
    } else {
        echo "   ⚠ Only {$result['count']} role(s) found. Expected 3 (ADMIN, HR, EMPLOYEE)\n";
        echo "   This should be fixed when importing hrms.sql\n\n";
    }
} catch (Exception $e) {
    echo "   ✗ Error checking roles: " . $e->getMessage() . "\n\n";
}

// Check uploads directory
echo "6. Checking uploads directory...\n";
if (is_dir('uploads') && is_writable('uploads')) {
    echo "   ✓ Uploads directory exists and is writable\n\n";
} else {
    if (!is_dir('uploads')) {
        mkdir('uploads', 0777, true);
        mkdir('uploads/timeoff', 0777, true);
        echo "   ✓ Created uploads directory\n\n";
    } else {
        echo "   ⚠ Uploads directory exists but may not be writable\n\n";
    }
}

// Final summary
echo "========================================\n";
echo "Setup Summary\n";
echo "========================================\n";
if (empty($missingTables)) {
    echo "✓ All database tables are set up correctly!\n";
    echo "✓ System is ready to use!\n\n";
    echo "Next steps:\n";
    echo "1. Start XAMPP (Apache and MySQL)\n";
    echo "2. Visit http://localhost/Gcet-hackathon/signup.php to create your company account\n";
    echo "3. Then login at http://localhost/Gcet-hackathon/signin.php\n";
} else {
    echo "⚠ Some tables are missing. Please import the SQL files mentioned above.\n";
    echo "After importing, run this setup script again to verify.\n";
}
echo "\n";

