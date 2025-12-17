# Clearance System

Project: Clearance System (PHP + MySQL)

Overview

- A simple web-based student clearance management system built with PHP, MySQL and plain HTML/CSS/JS. It allows students to submit clearance requests, office staff to update statuses, and admins to view and manage requests.

Key Features

- Student registration and login
- Students submit clearance requests and view request details
- Office dashboard for processing and updating request status
- Admin dashboard for managing and deleting requests
- Simple role-based access controls via include checks

Tech stack

- PHP (runs on XAMPP)
- MySQL / MariaDB
- HTML, CSS, JavaScript

Setup / Installation

1. Install XAMPP (or any PHP + MySQL stack) and start Apache + MySQL.
2. Copy the project folder into XAMPP's htdocs, e.g. C:\xampp\htdocs\clearance_system.
3. Create a database and import the provided database.sql:

mysql -u root -p
CREATE DATABASE clearance_system;
exit
mysql -u root -p clearance_system < database.sql

4. Update database connection settings in [includes/db.php](includes/db.php) to match your MySQL credentials and database name.
5. (Optional) Create an admin account using the app's registration flow or by inserting a record into the appropriate users table.

How to use

- Open the app in your browser: http://localhost/clearance_system/ (or the path you placed the folder).
- Student flows: Use [student/request_clearance.php](student/request_clearance.php) to submit a clearance request; view requests via [student/student_dashboard.php](student/student_dashboard.php) and [student/request_details.php](student/request_details.php).
- Office flows: Office staff use [office/office_dashboard.php](office/office_dashboard.php) and [office/update_status.php](office/update_status.php) to manage requests.
- Admin flows: Admins use [admin/admin_dashboard.php](admin/admin_dashboard.php) and [admin/view_request.php](admin/view_request.php) / [admin/delete_request.php](admin/delete_request.php) to oversee and remove requests.

Project structure (high level)

- [index.html](index.html) — Landing page
- [login.php](login.php), [register.php](register.php), [logout.php](logout.php)
- [database.sql](database.sql) — Database schema and seed data
- [includes/](includes/) — Shared scripts: db.php, auth.php, headers/footers
- [student/](student/) — Student pages
- [office/](office/) — Office/staff pages
- [admin/](admin/) — Admin pages
- [assets/](assets/) — CSS/JS and images

Security & Notes

- This is a simple educational/demo system. Review and harden authentication, input validation, and SQL handling before deploying to production.
- Replace default or empty database passwords and use prepared statements to prevent SQL injection.

Contributing

- Open issues or submit pull requests. Describe the change and provide testing steps.

License

- Add a license file if you plan to open-source this project.

If you want, I can also:

- Add setup screenshots or a quick installer script
- Create SQL to seed an initial admin account
- Run a quick check to ensure includes/db.php matches your local DB credentials
