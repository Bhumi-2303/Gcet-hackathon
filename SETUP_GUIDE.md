# HRMS System Setup Guide

## What Has Been Implemented

Your HRMS (Human Resource Management System) now includes the following features:

### ✅ Completed Features

1. **Authentication System**
   - User registration (signup.php)
   - User login (signin.php)
   - Session management
   - Password hashing
   - Login audit logging

2. **Dashboard**
   - Employee listing with status indicators
   - Real-time attendance check-in/check-out
   - Employee search functionality
   - Employee detail modal view

3. **Attendance System**
   - Check-in/Check-out functionality
   - Attendance history page with filters
   - Daily attendance tracking
   - IP address logging

4. **Employee Management** (Admin/HR only)
   - Add new employees
   - View all employees
   - Delete employees
   - Assign roles to employees

5. **Role-Based Access Control**
   - ADMIN: Full access to all features
   - HR: Can manage employees
   - EMPLOYEE: Can view own attendance and check in/out

## Database Setup

### Step 1: Import the Main Database
Import the `hrms (1).sql` file into your MySQL database using phpMyAdmin or command line:
```bash
mysql -u root -p < "hrms (1).sql"
```

### Step 2: Create Attendance Table
Run the SQL from `attendance_table.sql` to create the attendance tracking table:
```sql
-- You can copy and paste the contents of attendance_table.sql into phpMyAdmin
-- OR run: mysql -u root -p hrms < attendance_table.sql
```

### Step 3: Update Database Credentials
If needed, update `config/db.php` with your MySQL credentials:
```php
define('DB_USER', 'root');
define('DB_PASS', 'root'); // Change if your MySQL password is different
```

## File Structure

```
hackathon/
├── api/
│   ├── check_attendance.php    # Attendance check-in/out API
│   └── add_employee.php         # Add employee API
├── assets/
│   ├── css/
│   │   ├── styles.css          # Auth pages styles
│   │   └── dashboard.css        # Dashboard styles
│   └── js/
│       ├── main.js             # Auth pages JavaScript
│       └── dashboard.js        # Dashboard JavaScript
├── auth/
│   ├── login.php               # Login handler
│   ├── register.php            # Registration handler
│   └── logout.php              # Logout handler
├── config/
│   └── db.php                  # Database configuration
├── attendance.php              # Attendance history page
├── dashboard.php               # Main dashboard
├── employees.php               # Employee management (Admin/HR)
├── signin.php                  # Login page
├── signup.php                  # Registration page
├── welcome.php                 # Welcome page
└── attendance_table.sql        # Attendance table SQL

```

## How to Use

### 1. First Time Setup
1. Start XAMPP (Apache and MySQL)
2. Import the database files as described above
3. Visit `http://localhost/hackathon/signup.php`
4. Create your company account (this creates the first ADMIN user)

### 2. Login
- Visit `http://localhost/hackathon/signin.php`
- Use your login ID or email to sign in

### 3. Dashboard Features
- **View Employees**: See all employees in your company
- **Check In/Out**: Use the attendance panel on the right
- **Search**: Search employees by name or email
- **View Details**: Click on any employee card to see details

### 4. Attendance History
- Navigate to "Attendance" in the top menu
- Filter by date, employee (Admin only), or type (IN/OUT)
- View all attendance records

### 5. Employee Management (Admin/HR)
- Navigate to "Manage" in the top menu
- Click "+ Add Employee" to add new employees
- Assign roles and set temporary passwords
- Delete employees if needed

## Features by Role

### ADMIN (Role ID: 1)
- ✅ Full access to all features
- ✅ Can manage employees
- ✅ Can view all attendance records
- ✅ Can add/edit/delete employees

### HR (Role ID: 2)
- ✅ Can manage employees
- ✅ Can view all attendance records
- ✅ Can add/edit/delete employees

### EMPLOYEE (Role ID: 3)
- ✅ Can check in/out
- ✅ Can view own attendance history
- ✅ Can view employee directory
- ❌ Cannot manage other employees

## API Endpoints

### POST /api/check_attendance.php
Check in or check out
```javascript
// Check In
fetch('api/check_attendance.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'type=IN'
});

// Check Out
fetch('api/check_attendance.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'type=OUT'
});
```

### POST /api/add_employee.php
Add a new employee (Admin/HR only)
- Requires: first_name, last_name, email, phone, date_of_joining, role_id, password

## Security Notes

- Passwords are hashed using PHP's `password_hash()`
- SQL injection protection using prepared statements
- Session-based authentication
- Role-based access control
- IP address logging for attendance

## Next Steps (Optional Enhancements)

- [ ] Employee profile editing
- [ ] Password reset functionality
- [ ] Email notifications
- [ ] Attendance reports and analytics
- [ ] Leave management system
- [ ] Payroll integration
- [ ] Export attendance data to CSV/PDF

## Troubleshooting

### Database Connection Error
- Check `config/db.php` credentials
- Ensure MySQL is running in XAMPP
- Verify database name is `hrms`

### Attendance Not Working
- Make sure `attendance` table exists (run `attendance_table.sql`)
- Check browser console for JavaScript errors
- Verify API endpoint is accessible

### Permission Denied
- Ensure user has correct role (ADMIN or HR for management features)
- Check session is active (try logging out and back in)

## Support

For issues or questions, check:
1. Browser console for JavaScript errors
2. PHP error logs in XAMPP
3. Database connection in `config/db.php`

