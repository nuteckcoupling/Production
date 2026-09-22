NU-TECK COUPLINGS — DAILY PRODUCTION ENTRY
============================================
Domain: production.nuteckcouplings.com
Local URL: http://127.0.0.1/production%20sys/public_html/

CURRENT FEATURES
----------------
- Operator/Supervisor and Admin/Director login
- Live machine dashboard and automatic alerts
- Start Job, End Shift and operator handover workflow
- Cutter and coupling management
- Machine-wise daily, weekly and monthly production reports
- CSV/Excel download and Print/Save PDF report output

FOLDER GUIDE
------------
db.sql              -> Import once via phpMyAdmin (creates tables + 15 machines)
public_html/         -> Upload this ENTIRE folder's contents to your cPanel public_html
  index.html, assets/  -> Built React frontend (ready to use, no Node needed on server)
  api/*.php            -> Backend API (edit config.php first!)
react-source/         -> React source code (only needed if you want to edit the form later)


STEP-BY-STEP DEPLOYMENT (cPanel)
---------------------------------
1. cPanel > MySQL Databases > create a database (e.g. yourcpaneluser_nuteck)
   and a database user, add user to the database with ALL PRIVILEGES.

2. cPanel > phpMyAdmin > select your new database > Import tab > choose db.sql > Go.
   This creates: machines (15 pre-loaded), operators, parts, cutters, daily_entries.
   Parts are pre-loaded with 21 Full Gear Couplings (GC) and 11 Half Gear
   Couplings (HGC), plus 16 unique Roller Chain Coupling codes. The component
   list changes automatically for the selected code.

   Existing database only: import 2026-09-21-add-coupling-parts.sql once before
   importing 2026-09-21-add-roller-chain-couplings.sql, then upload the updated
   PHP/HTML files.

3. Open public_html/api/config.php and edit these 4 lines with your real values:
     $DB_HOST = "localhost";
     $DB_NAME = "yourcpaneluser_nuteck";
     $DB_USER = "yourcpaneluser_dbuser";
     $DB_PASS = "your_db_password";

4. Upload everything inside the public_html/ folder (index.html, assets/, api/)
   to your cPanel File Manager -> public_html/ (the root folder for
   production.nuteckcouplings.com).

5. Add sample data so dropdowns aren't empty:
   In phpMyAdmin, open "operators" table > Insert a few names.
   Open "parts" table > Insert part_name, drg_no, planned_qty for each part.
   Open "cutters" table > Insert cutter_num for each cutter.
   (Machines are already pre-loaded — 15 machines from your list.)

6. Visit http://production.nuteckcouplings.com/ — the Daily Entry form should
   load with dropdowns populated, and saving an entry should work.


HOW "PLANNED QTY AUTO-FILL" WORKS
-----------------------------------
If the same part_name has multiple rows in the "parts" table (e.g. entered from
different plans), the app automatically picks the row with the HIGHEST
planned_qty when the engineer selects that part. This is handled in
api/get_parts.php (SQL) — no manual picking needed.


EDITING THE REACT FORM LATER
------------------------------
cd react-source
npm install
npm run dev        (to preview changes locally)
npm run build       (creates a "dist" folder)
Upload the new dist/index.html and dist/assets/ to public_html/, replacing the old ones.


ACCESS MODEL
------------
- Admin/Director: monitoring, analysis and production reports
- Operator/Supervisor: production workflow, handover, cutter and coupling management
