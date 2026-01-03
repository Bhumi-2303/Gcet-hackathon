# HRMS - Human Resource Management System

A modern, web-based Human Resource Management System built with PHP and MySQL. Manage employees, track attendance, handle time-off requests, and maintain employee profiles all in one place.

![PHP](https://img.shields.io/badge/PHP-8.5+-777BB4?style=flat&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?style=flat&logo=mysql&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green?style=flat)

---

## ✨ Features

### 👥 Employee Management
- Add, view, and manage employee records
- Employee profile management with skills and certifications
- Role-based access control (Admin, HR, Employee)
- Employee directory with search functionality

### ⏰ Attendance Tracking
- Real-time check-in/check-out system
- Attendance history with date filters
- IP address logging for security
- Daily attendance monitoring

### 📅 Time Off Management
- Request and approve time-off requests
- Document upload support
- Status tracking (Pending, Approved, Rejected)
- Role-based approval workflow

### 🔐 Security & Authentication
- Secure user authentication with password hashing
- Session-based access control
- Login audit logging
- SQL injection protection

---

## 🚀 Quick Start

### Prerequisites
- PHP 8.5 or higher
- MySQL/MariaDB 8.0 or higher
- Web server (Apache/Nginx) or PHP built-in server

### Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd Gcet-hackathon
   ```

2. **Set up the database**
   ```bash
   # Create database and import schema
   sudo mysql < hrms.sql
   sudo mysql hrms < attendance_table.sql
   sudo mysql hrms < time_off_table.sql
   sudo mysql hrms < profile_tables.sql
   ```

3. **Configure database connection**
   
   Edit `config/db.php` with your MySQL credentials:
   ```php
   define('DB_HOST', '127.0.0.1');
   define('DB_NAME', 'hrms');
   define('DB_USER', 'root');
   define('DB_PASS', 'your_password');
   ```

4. **Start the server**
   
   **Option A: PHP Built-in Server (Development)**
   ```bash
   ./start_server_fixed.sh
   # Or manually:
   php -S localhost:8000
   ```
   
   **Option B: Apache/Nginx (Production)**
   - Configure your web server to point to the project directory
   - Ensure PHP and MySQL extensions are enabled

5. **Access the application**
   - Open your browser and navigate to `http://localhost:8000`
   - Sign up at `http://localhost:8000/signup.php` to create your company account
   - Login at `http://localhost:8000/signin.php`

---

## 📖 Usage

### First Time Setup

1. **Create Company Account**
   - Visit the signup page
   - Enter company details and create the first admin account
   - Upload your company logo (optional)

2. **Login**
   - Use your login ID or email to sign in
   - You'll be redirected to the dashboard based on your role

### Role-Based Features

#### 👨‍💼 Administrator / HR Staff
- Full system access
- Manage all employees
- View all attendance records
- Approve/reject time-off requests
- System configuration
- Manage employees
- View all attendance records
- Approve/reject time-off requests
- Employee profile management

#### 👤 Employee
- Check in/out for attendance
- View personal attendance history
- Request time off
- View employee directory
- Update personal profile

---

## 🛠️ Tech Stack

- **Backend**: PHP 8.5+
- **Database**: MySQL/MariaDB
- **Frontend**: HTML, CSS, JavaScript
- **Security**: Password hashing, Prepared statements, Session management

---

## 📁 Project Structure

```
Gcet-hackathon/
├── api/                 # API endpoints
├── assets/             # CSS and JavaScript files
│   ├── css/
│   └── js/
├── auth/               # Authentication handlers
├── config/             # Configuration files
│   └── db.php         # Database configuration
├── uploads/            # Uploaded files (logos, documents)
├── dashboard.php       # Main dashboard
├── attendance.php      # Attendance management
├── timeoff.php         # Time-off management
├── employees.php       # Employee management
├── signin.php          # Login page
├── signup.php          # Registration page
└── *.sql              # Database schema files
```

---

## 🔧 Configuration

### Database Setup
The system uses MySQL/MariaDB. Update `config/db.php` with your database credentials:

```php
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'hrms');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### PHP Extensions
Ensure the following PHP extensions are enabled:
- `pdo_mysql`
- `mysqli`
- `session`
- `gd` (for image processing)

---

## 🐛 Troubleshooting

### Database Connection Error
- Verify MySQL/MariaDB is running
- Check database credentials in `config/db.php`
- Ensure the `hrms` database exists

### "Could not find driver" Error
- Enable PHP MySQL extensions (see `ARCH_LINUX_SETUP.md` for details)
- Run: `sudo ./enable_php_mysql.sh` (Linux) or enable in `php.ini`

### Port Already in Use
- Kill the process using port 8000: `kill $(lsof -ti:8000)`
- Or use a different port: `php -S localhost:8080`

### Permission Issues
- Ensure `uploads/` directory is writable: `chmod -R 755 uploads/`

---

## 📚 Documentation

- [Quick Start Guide](QUICK_START.md) - Get started quickly
- [Setup Guide](SETUP_GUIDE.md) - Detailed setup instructions
- [Arch Linux Setup](ARCH_LINUX_SETUP.md) - Linux-specific setup
- [Database Fix Guide](FIX_DATABASE.md) - Database troubleshooting

---

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

---

## 📝 License

This project is licensed under the MIT License - see the LICENSE file for details.

---

## 👨‍💻 Support

For issues, questions, or contributions, please open an issue on the repository.

---

**Made with ❤️ for efficient HR management**
