NU-TECK COUPLINGS — PRODUCTION MANAGEMENT SYSTEM
=================================================
Domain: production.nuteckcouplings.com
Local URL: http://127.0.0.1/production%20sys/public_html/

CURRENT FEATURES
----------------
- Operator/Supervisor and Admin/Director login
- Live machine dashboard and automatic alerts
- Start Job, End Shift and operator handover workflow
- Cutter and setting change workflow
- One Production Plan module with Weekly and Monthly ISO tabs, CSV/Excel and Print/Save PDF output
- Monthly ISO snapshots generated from Weekly Plans with final locking
- Admin-only Shift Management with automatic hours and safe deactivate rules
- Admin-only Machine Management with default operations and history-safe deletion
- Admin-only Staff Management with staff details, active status and history-safe deletion
- Admin-only User Management with role/staff linking, account status and password reset
- Secure self-service password change for every logged-in user
- Cutter and coupling management
- Machine-wise daily, weekly and monthly production reports
- Admin-only actual-production analysis with achievement, pending/overdue jobs, downtime and rejection details
- CSV/Excel download and Print/Save PDF report output
- Custom in-app confirmation popups and success/error toast notifications

FOLDER GUIDE
------------
database.sql           -> Complete schema and initial master/login data for a fresh database
public_html/index.php   -> Main application shell
public_html/views/      -> Login, sidebar and feature view sections
public_html/assets/js/  -> Frontend JavaScript separated by feature
public_html/style.css   -> Application styles
public_html/api/*.php   -> Backend API endpoints

LOCAL SETUP (XAMPP)
-------------------
1. Start Apache and MySQL in XAMPP.
2. Create an empty MySQL/MariaDB database named prodction.
3. Import database.sql once into the empty database.
4. Confirm public_html/api/config.php contains the correct database settings.
5. Open http://127.0.0.1/production%20sys/public_html/

CPANEL DEPLOYMENT
-----------------
1. In cPanel > MySQL Databases, create a database and database user.
2. Add the user to the database with ALL PRIVILEGES.
3. In phpMyAdmin, select the empty database and import database.sql once.
4. Update public_html/api/config.php with the hosting database credentials.
WEEKLY PLAN ISO NOTE
--------------------
- Weekly Plan records are documentation only and do not affect jobs, Daily Entry, dashboard, or production reports.
- Monthly Plan is generated only from Weekly Plans and remains separate from production data.

5. Upload everything inside public_html/ to the domain's public_html directory.
6. Visit the production domain and test login, dashboard and production workflow.

IMPORTANT DATABASE NOTE
-----------------------
- database.sql is for a fresh installation.
- Do not import it over an existing production database without taking a backup.

SHIFT MANAGEMENT
----------------
- Only Admin can add, edit, delete unused shifts, or deactivate shifts with history.
- Existing production records are not included in this repository file.

MACHINE MANAGEMENT
------------------
- Only Admin can add, edit, delete unused machines, or deactivate machines with history.
- A machine with a running job cannot be deactivated or deleted until that job is ended.
- Default Operation automatically fills Start Job and Change Setting forms; Other remains available.

STAFF MANAGEMENT
----------------
- Only Admin can add, edit, delete unused staff, or deactivate staff with production history/login accounts.
- Staff with a running job or shift cannot be deactivated or deleted until the work is ended.
- Active staff automatically appear in production and report dropdowns.

USER AND PASSWORD MANAGEMENT
----------------------------
- Only Admin can create/edit login accounts, assign roles, link staff, activate/deactivate users and reset passwords.
- Every logged-in user can change their own password after confirming the current password.
- Duplicate usernames and duplicate staff-account links are blocked.
- The current Admin cannot deactivate or remove Admin access from their own account, and one active Admin is always required.

ADMIN PRODUCTION ANALYSIS
-------------------------
- Uses actual production jobs, shift entries and cutter-change downtime only.
- Weekly and Monthly ISO Plan records are intentionally not used in analysis.
- Includes machine achievement, pending/overdue jobs, rejection, downtime, remarks, filters, CSV and Print/Save PDF.

PLANNED QTY AUTO-FILL
---------------------
Planned quantity is selected using the chosen machine and part configuration.
The production workflow then tracks actual quantity, rejection, rework and downtime.

ACCESS MODEL
------------
- Admin/Director: monitoring, analysis and production reports
- Operator/Supervisor: production workflow, handover, cutter and coupling management
