# Quick Start Guide

## ✅ Setup Complete!

Your HRMS system has been successfully set up. All database tables have been created and configured.

## Next Steps

### 1. Start XAMPP Services
- Open XAMPP Control Panel
- Start **Apache** and **MySQL** services

### 2. Create Your Company Account
- Visit: `http://localhost/Gcet-hackathon/signup.php`
- Fill in your company details and create the first admin account

### 3. Login to the System
- Visit: `http://localhost/Gcet-hackathon/signin.php`
- Use your login ID or email to sign in

## System Features

### For Administrators (ADMIN role)
- ✅ Full access to all features
- ✅ Manage employees (add, edit, delete)
- ✅ View all attendance records
- ✅ Approve/reject time off requests

### For HR Staff (HR role)
- ✅ Manage employees
- ✅ View all attendance records
- ✅ Approve/reject time off requests

### For Employees (EMPLOYEE role)
- ✅ Check in/out for attendance
- ✅ View own attendance history
- ✅ Request time off
- ✅ View employee directory

## Important URLs

- **Sign Up**: `http://localhost/Gcet-hackathon/signup.php`
- **Sign In**: `http://localhost/Gcet-hackathon/signin.php`
- **Dashboard**: `http://localhost/Gcet-hackathon/dashboard.php`
- **Attendance**: `http://localhost/Gcet-hackathon/attendance.php`
- **Time Off**: `http://localhost/Gcet-hackathon/timeoff.php`
- **Employee Management**: `http://localhost/Gcet-hackathon/employees.php`

## Database Configuration

The database is configured in `config/db.php`:
- **Host**: 127.0.0.1
- **Database**: hrms
- **User**: root
- **Password**: (empty by default)

If you need to change these settings, edit `config/db.php`.

## Troubleshooting

### Database Connection Issues
- Ensure MySQL is running in XAMPP
- Check `config/db.php` for correct credentials
- Verify database name is `hrms`

### Tables Missing
- Run `setup.php` to check system status
- If tables are missing, run `import_tables.php`

### Permission Issues
- Ensure `uploads/` directory is writable
- Check file permissions if uploads fail

## Support

For detailed setup instructions, see `SETUP_GUIDE.md`

