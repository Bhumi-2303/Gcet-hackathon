# Sign In / Sign Up demo — AuroraHQ

Files created:
- `signin.php`, `signup.php`, `welcome.php`
- `assets/css/styles.css`, `assets/js/main.js`
- `auth/login.php`, `auth/register.php`, `auth/logout.php`
- `users.json` (simple demo store), `uploads/` for uploaded logos

How to run (XAMPP):
1. Place this `hackathon` folder under `htdocs` in XAMPP (it already is if you're editing here).
2. Start Apache and MySQL (MariaDB) in the XAMPP control panel.
3. Import the database dump `hrms (1).sql` into MySQL (phpMyAdmin or mysql CLI) to create the `hrms` database and tables.
   - Example (CLI):
     mysql -u root -p < "hrms (1).sql"
4. Adjust DB credentials if needed in `config/db.php` (defaults assume `root` with no password on localhost).
5. Open http://localhost/hackathon/signup.php to create a new company + admin user (this will insert into the database).
6. Then sign in at http://localhost/hackathon/signin.php

Notes:
- The app now uses the `hrms` MySQL database (see `config/db.php`). Passwords are stored using `password_hash`.
- `users.json` is no longer used for new registrations (kept for backwards reference only).
- Uploads are stored in `uploads/` with a 2 MB max and basic MIME checks.

Security reminders:
- Do not use `users.json` in production — use a database.
- Add CSRF protection, stricter server validation, and file type checks for production.

Next steps you might want:
- Add server-side password reset / email verification
- Replace `users.json` with a database (MySQL)
- Add nicer UI/UX and unit tests
- UI polish: modern font, refined styles, drag-and-drop logo upload, and toast notifications were added.
- All form fields are now required (including phone and company logo).
