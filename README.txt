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
- Login security with failed-attempt lockout, Admin unlock, forced password change and 60-minute idle timeout
- Secure self-service password change for every logged-in user
- Admin-only immutable Activity / Audit Log with filters and CSV download
- Admin-only protected database backup, download, restore and manual deletion
- Configurable automatic daily database backups with retention control
- Admin-only System Settings for company/report identity and live alert thresholds
- Cutter and coupling management
- Machine-wise daily, weekly and monthly production reports
- Admin-only actual-production analysis with achievement, pending/overdue jobs, downtime and rejection details
- CSV/Excel download and Print/Save PDF report output
- Custom in-app confirmation popups and success/error toast notifications
- V2 reliability dashboard with tracked database migrations and private error references
- V2 Maintenance Management with breakdown tickets, repair ownership, MTTR and protected photos
- V2 secure attachments for job drawings/photos, part photos, breakdown before/after photos and QC documents

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

ACTIVITY / AUDIT LOG
--------------------
- Admin can review login/logout, production, handover, cutter/setting, ISO plan and master-data actions.
- Filters are available for date, user, module and action, with CSV download.
- Audit records are read-only and store action details, username, role, IP address and timestamp.
- Passwords and password values are never stored in the audit log.

DATABASE BACKUP MANAGEMENT
--------------------------
- Only Admin can create, download, restore or delete database backup files.
- Backup files are stored under storage/backups outside public_html and are ignored by Git.
- Restore requires the current Admin password, typing RESTORE and a final confirmation popup.
- A safety backup is created automatically immediately before every restore.
- Backup files contain password hashes and production data; downloaded files must be stored privately.
- Automatic backups run on the first authenticated app request after the configured daily time.
- Admin can enable/disable the schedule, choose the time and retain the latest 1-90 automatic backups.
- Automatic retention never deletes manual or pre-restore safety backups.

V2 DATABASE MIGRATIONS AND ERROR LOG
------------------------------------
- Admin can review migration status under System Settings.
- Applying pending migrations requires the Admin password and typing MIGRATE.
- A safety backup is created before migrations run.
- Applied migration filename and checksum are recorded in schema_migrations.
- Changed or missing applied migration files block further migration for safety.
- Unexpected server errors return a short reference code instead of database details.
- The private Admin error log stores technical details without passwords or request bodies.

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
- Maintenance: assigned breakdown tickets, automatic repair timestamps and repair completion

V2 MAINTENANCE MANAGEMENT
-------------------------
- Operator/Supervisor raises Mechanical, Electrical or Other breakdown tickets from the machine dashboard.
- Optional JPG/PNG breakdown photos are stored in protected storage and served only through an authenticated endpoint.
- A running job and shift automatically move to Breakdown while repair is open.
- Maintenance login can assign a Maintenance department staff member, start work and complete the repair.
- Repair details are mandatory; spare-parts usage is optional.
- Operator/Supervisor enters mandatory testing remarks and confirms the machine Running.
- Breakdown duration is added to the linked production shift downtime and shown in maintenance MTTR.
- Admin/Director can monitor all tickets but cannot perform Operator or Maintenance actions.

V2 SECURE ATTACHMENTS
---------------------
- Job Drawing, Job/Part Photograph, Breakdown Before/After and QC Inspection categories are supported.
- Only PDF, JPG and PNG files up to 10 MB are accepted after server-side MIME validation.
- Files are stored under protected storage/attachments; the database stores metadata only.
- Downloads always pass through authenticated, role-checked API endpoints.
- Operator/Supervisor uploads job, part and before-breakdown files; Maintenance uploads after-repair photos; Admin has read-only access.
- Uploaded attachment metadata has no ordinary delete action, and linked records with attachments are protected from hard deletion.
