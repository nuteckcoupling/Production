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
- Independent Weekly Plan ISO records with CSV/Excel and Print/Save PDF output
- Cutter and coupling management
- Machine-wise daily, weekly and monthly production reports
- CSV/Excel download and Print/Save PDF report output

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

5. Upload everything inside public_html/ to the domain's public_html directory.
6. Visit the production domain and test login, dashboard and production workflow.

IMPORTANT DATABASE NOTE
-----------------------
- database.sql is for a fresh installation.
- Do not import it over an existing production database without taking a backup.
- Existing production records are not included in this repository file.

PLANNED QTY AUTO-FILL
---------------------
Planned quantity is selected using the chosen machine and part configuration.
The production workflow then tracks actual quantity, rejection, rework and downtime.

ACCESS MODEL
------------
- Admin/Director: monitoring, analysis and production reports
- Operator/Supervisor: production workflow, handover, cutter and coupling management
