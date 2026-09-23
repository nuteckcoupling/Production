-- NU-TECK Couplings - Daily Production Entry
-- Import this once via phpMyAdmin (cPanel)

CREATE TABLE IF NOT EXISTS machines (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(30) NOT NULL UNIQUE,
  name VARCHAR(100) NOT NULL,
  status ENUM('Active','Inactive') DEFAULT 'Active'
);

INSERT INTO machines (code, name) VALUES
('HOB/01','Hobbing Machine 01'),
('HOB/02','Hobbing Machine 02'),
('HOB/03','Hobbing Machine 03'),
('HOB/04','Hobbing Machine 04'),
('HOB/05','Hobbing Machine 05'),
('HOB/06','Hobbing Machine 06'),
('SHP/01','Shaping Machine 01'),
('SHP/02','Shaping Machine 02'),
('SHP/03','Shaping Machine 03'),
('SHP/04','Shaping Machine 04'),
('SHP/05','Shaping Machine 05'),
('SHP/06','Shaping Machine 06'),
('VTL','VTL Machine'),
('DRILL','Drilling Machine'),
('KEYWAY','Keyway Machine');

CREATE TABLE IF NOT EXISTS operators (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  status ENUM('Active','Inactive') DEFAULT 'Active'
);

INSERT INTO operators (name, status) VALUES
('Mukesh','Active'),
('Prakash','Active'),
('vijay','Active'),
('subdeep','Active');

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('Operator/Supervisor','Admin') NOT NULL DEFAULT 'Operator/Supervisor',
  operator_id INT DEFAULT NULL UNIQUE,
  status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
  last_login DATETIME DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (operator_id) REFERENCES operators(id)
);

INSERT INTO users (username, password_hash, role, operator_id, status) VALUES
('admin', '$2y$10$pVxUqEd/sZV0VzgcVcnFXezVQLycosjLU02kjI90U6tDNnAtSLEq.', 'Admin', NULL, 'Active'),
('mukesh', '$2y$10$PfnZRFmiQ5qvjlTGrjxsOuggRHuu4.iAdFn2PNy7fmycCY6R9.iF.', 'Operator/Supervisor', 1, 'Active'),
('prakash', '$2y$10$PfnZRFmiQ5qvjlTGrjxsOuggRHuu4.iAdFn2PNy7fmycCY6R9.iF.', 'Operator/Supervisor', 2, 'Active'),
('vijay', '$2y$10$PfnZRFmiQ5qvjlTGrjxsOuggRHuu4.iAdFn2PNy7fmycCY6R9.iF.', 'Operator/Supervisor', 3, 'Active'),
('subdeep', '$2y$10$PfnZRFmiQ5qvjlTGrjxsOuggRHuu4.iAdFn2PNy7fmycCY6R9.iF.', 'Operator/Supervisor', 4, 'Active')
ON DUPLICATE KEY UPDATE
  password_hash = VALUES(password_hash),
  role = VALUES(role),
  operator_id = VALUES(operator_id),
  status = VALUES(status);

CREATE TABLE IF NOT EXISTS parts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  part_name VARCHAR(150) NOT NULL UNIQUE,
  coupling_type ENUM('GC Gear Coupling','NA Gear Coupling','Roller Chain Coupling','Gear','Sprocket') DEFAULT NULL,
  drg_no VARCHAR(100),
  planned_qty INT DEFAULT 0,     -- if multiple rows exist for same part, app uses MAX()
  status ENUM('Active','Inactive') DEFAULT 'Active'
);

INSERT INTO parts (part_name, coupling_type) VALUES
('GC-100','GC Gear Coupling'),
('GC-101','GC Gear Coupling'),
('GC-102','GC Gear Coupling'),
('GC-103','GC Gear Coupling'),
('GC-104','GC Gear Coupling'),
('GC-105','GC Gear Coupling'),
('GC-106','GC Gear Coupling'),
('GC-107','GC Gear Coupling'),
('GC-108','GC Gear Coupling'),
('GC-109','GC Gear Coupling'),
('GC-110','GC Gear Coupling'),
('GC-111','GC Gear Coupling'),
('GC-112','GC Gear Coupling'),
('GC-113','GC Gear Coupling'),
('GC-114','GC Gear Coupling'),
('GC-115','GC Gear Coupling'),
('GC-115 M','GC Gear Coupling'),
('GC-116','GC Gear Coupling'),
('GC-116 M','GC Gear Coupling'),
('GC-117','GC Gear Coupling'),
('GC-118','GC Gear Coupling'),
('HGC-100','NA Gear Coupling'),
('HGC-101','NA Gear Coupling'),
('HGC-102','NA Gear Coupling'),
('HGC-103','NA Gear Coupling'),
('HGC-104','NA Gear Coupling'),
('HGC-105','NA Gear Coupling'),
('HGC-106','NA Gear Coupling'),
('HGC-107','NA Gear Coupling'),
('HGC-108','NA Gear Coupling'),
('HGC-109','NA Gear Coupling'),
('HGC-110','NA Gear Coupling'),
('NAF 10','NA Gear Coupling'),
('NAF 15','NA Gear Coupling'),
('NAF 20','NA Gear Coupling'),
('NAF 25','NA Gear Coupling'),
('NAF 30','NA Gear Coupling'),
('NAF 35','NA Gear Coupling'),
('NAF 40','NA Gear Coupling'),
('NAF 45','NA Gear Coupling'),
('NAF 50','NA Gear Coupling'),
('NAF 55','NA Gear Coupling'),
('NAF 60','NA Gear Coupling'),
('NAF 70','NA Gear Coupling'),
('NAF 80','NA Gear Coupling'),
('NAF 90','NA Gear Coupling'),
('NAF 100','NA Gear Coupling'),
('NAF 110','NA Gear Coupling'),
('NAF120','NA Gear Coupling'),
('NAF130','NA Gear Coupling'),
('NAF 140','NA Gear Coupling'),
('NAF 150','NA Gear Coupling'),
('NT1016','Roller Chain Coupling'),
('NT1018','Roller Chain Coupling'),
('NT1218','Roller Chain Coupling'),
('NT1222','Roller Chain Coupling'),
('NT1618','Roller Chain Coupling'),
('NT1622','Roller Chain Coupling'),
('NT2020','Roller Chain Coupling'),
('NT2418','Roller Chain Coupling'),
('NT2422','Roller Chain Coupling'),
('NT3218','Roller Chain Coupling'),
('NT8316','Roller Chain Coupling'),
('NT8312','Roller Chain Coupling'),
('NT6112','Roller Chain Coupling'),
('NT3222','Roller Chain Coupling'),
('NT4018','Roller Chain Coupling'),
('NT4022','Roller Chain Coupling');

CREATE TABLE IF NOT EXISTS part_components (
  id INT AUTO_INCREMENT PRIMARY KEY,
  part_id INT NOT NULL,
  component_name VARCHAR(50) NOT NULL,
  sort_order INT DEFAULT 0,
  UNIQUE KEY unique_part_component (part_id, component_name),
  FOREIGN KEY (part_id) REFERENCES parts(id) ON DELETE CASCADE
);

INSERT INTO part_components (part_id, component_name, sort_order)
SELECT id, 'Hub', 1 FROM parts WHERE coupling_type IN ('GC Gear Coupling','NA Gear Coupling')
UNION ALL
SELECT id, 'Sleeve', 2 FROM parts WHERE coupling_type IN ('GC Gear Coupling','NA Gear Coupling');

INSERT INTO part_components (part_id, component_name, sort_order)
SELECT p.id, c.component_name, c.sort_order
FROM parts p
JOIN (
  SELECT 'NT1016' part_name, 'Sprocket' component_name, 1 sort_order UNION ALL
  SELECT 'NT1016','Rubber Washer',2 UNION ALL SELECT 'NT1016','Steel Ring',3 UNION ALL SELECT 'NT1016','Aluminium Cover',4 UNION ALL SELECT 'NT1016','Rubber Packing',5 UNION ALL
  SELECT 'NT1018','Sprocket',1 UNION ALL SELECT 'NT1018','Rubber Washer',2 UNION ALL SELECT 'NT1018','Steel Ring',3 UNION ALL SELECT 'NT1018','Aluminium Cover',4 UNION ALL SELECT 'NT1018','Rubber Packing',5 UNION ALL
  SELECT 'NT1218','Sprocket',1 UNION ALL SELECT 'NT1218','Rubber Washer',2 UNION ALL SELECT 'NT1218','Steel Ring',3 UNION ALL SELECT 'NT1218','Aluminium Cover',4 UNION ALL SELECT 'NT1218','Rubber Packing',5 UNION ALL
  SELECT 'NT1222','Sprocket',1 UNION ALL SELECT 'NT1222','Rubber Washer',2 UNION ALL SELECT 'NT1222','Steel Ring',3 UNION ALL SELECT 'NT1222','Aluminium Cover',4 UNION ALL SELECT 'NT1222','Rubber Packing',5 UNION ALL
  SELECT 'NT1618','Sprocket',1 UNION ALL SELECT 'NT1618','Rubber Washer',2 UNION ALL SELECT 'NT1618','Aluminium Cover',3 UNION ALL SELECT 'NT1618','Rubber Packing',4 UNION ALL
  SELECT 'NT1622','Sprocket',1 UNION ALL SELECT 'NT1622','Rubber Washer',2 UNION ALL SELECT 'NT1622','Aluminium Cover',3 UNION ALL SELECT 'NT1622','Rubber Packing',4 UNION ALL
  SELECT 'NT2020','Sprocket',1 UNION ALL SELECT 'NT2020','Rubber Washer',2 UNION ALL SELECT 'NT2020','Aluminium Cover',3 UNION ALL SELECT 'NT2020','Rubber Packing',4 UNION ALL
  SELECT 'NT2418','Sprocket',1 UNION ALL SELECT 'NT2418','Rubber Washer',2 UNION ALL SELECT 'NT2418','Aluminium Cover',3 UNION ALL SELECT 'NT2418','Rubber Packing',4 UNION ALL
  SELECT 'NT2422','Sprocket',1 UNION ALL SELECT 'NT2422','Rubber Washer',2 UNION ALL SELECT 'NT2422','Aluminium Cover',3 UNION ALL SELECT 'NT2422','Rubber Packing',4 UNION ALL
  SELECT 'NT3218','Sprocket',1 UNION ALL SELECT 'NT3218','Rubber Washer',2 UNION ALL SELECT 'NT3218','Aluminium Cover',3 UNION ALL SELECT 'NT3218','Rubber Packing',4 UNION ALL
  SELECT 'NT8316','Sprocket',1 UNION ALL SELECT 'NT8316','Rubber Washer',2 UNION ALL SELECT 'NT8316','Steel Ring',3 UNION ALL SELECT 'NT8316','Rubber Packing',4 UNION ALL SELECT 'NT8316','Aluminium Cover',5 UNION ALL
  SELECT 'NT8312','Sprocket',1 UNION ALL SELECT 'NT8312','Rubber Washer',2 UNION ALL SELECT 'NT8312','Steel Ring',3 UNION ALL
  SELECT 'NT6112','Sprocket',1 UNION ALL SELECT 'NT6112','Rubber Washer',2 UNION ALL SELECT 'NT6112','Steel Ring',3 UNION ALL
  SELECT 'NT3222','Sprocket',1 UNION ALL SELECT 'NT3222','M.S Cover',2 UNION ALL
  SELECT 'NT4018','Sprocket',1 UNION ALL
  SELECT 'NT4022','Sprocket',1 UNION ALL SELECT 'NT4022','M.S Cover',2
) c ON c.part_name = p.part_name
WHERE p.coupling_type = 'Roller Chain Coupling';

-- Roller Chain Coupling uses Sprocket only.
DELETE pc
FROM part_components pc
JOIN parts p ON p.id = pc.part_id
WHERE p.coupling_type = 'Roller Chain Coupling'
  AND pc.component_name <> 'Sprocket';

CREATE TABLE IF NOT EXISTS cutters (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cutter_num VARCHAR(50) NOT NULL UNIQUE,
  cutter_type VARCHAR(100),
  lead_angle DECIMAL(6,2) DEFAULT NULL,
  rpm_stroke VARCHAR(50) DEFAULT NULL,
  status ENUM('Active','Not Active') DEFAULT 'Active',
  remarks VARCHAR(255) DEFAULT NULL,
  deleted_at DATETIME DEFAULT NULL,
  KEY idx_cutters_deleted_at (deleted_at)
);

CREATE TABLE IF NOT EXISTS daily_entries (
  id INT AUTO_INCREMENT PRIMARY KEY,
  entry_date DATE NOT NULL,
  shift ENUM('1','2','3','Day Shift','Night Shift') NOT NULL,
  shift_hours TINYINT UNSIGNED NOT NULL DEFAULT 12,
  machine_id INT NOT NULL,
  operator_id INT NOT NULL,
  part_id INT NOT NULL,
  component VARCHAR(50) NOT NULL,
  drg_no VARCHAR(100),
  operation VARCHAR(150),
  cutter_id INT,
  planned_qty INT DEFAULT 0,
  total_qty INT DEFAULT 0,
  ok_qty INT DEFAULT 0,
  mc_reject_qty INT DEFAULT 0,
  rm_defect_qty INT DEFAULT 0,
  rework_qty INT DEFAULT 0,
  machine_status ENUM('Running','Idle','Breakdown') DEFAULT 'Running',
  downtime_min INT DEFAULT 0,
  issue_code VARCHAR(100),
  remarks VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (machine_id) REFERENCES machines(id),
  FOREIGN KEY (operator_id) REFERENCES operators(id),
  FOREIGN KEY (part_id) REFERENCES parts(id),
  FOREIGN KEY (cutter_id) REFERENCES cutters(id)
);

CREATE TABLE IF NOT EXISTS production_jobs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  machine_id INT NOT NULL,
  part_id INT NOT NULL,
  component VARCHAR(50) NOT NULL,
  drg_no VARCHAR(100) DEFAULT NULL,
  operation VARCHAR(150) DEFAULT NULL,
  cutter_id INT DEFAULT NULL,
  planned_qty INT NOT NULL DEFAULT 0,
  cumulative_ok_qty INT NOT NULL DEFAULT 0,
  status ENUM('Running','Handover Pending','Breakdown','Stopped','Completed') NOT NULL DEFAULT 'Running',
  current_operator_id INT DEFAULT NULL,
  current_shift VARCHAR(30) DEFAULT NULL,
  started_at DATETIME DEFAULT NULL,
  status_changed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  completed_at DATETIME DEFAULT NULL,
  remarks VARCHAR(255) DEFAULT NULL,
  created_by_user_id INT DEFAULT NULL,
  active_machine_id INT AS (
    CASE WHEN status IN ('Running','Handover Pending','Breakdown','Stopped') THEN machine_id ELSE NULL END
  ) PERSISTENT,
  UNIQUE KEY unique_active_job_per_machine (active_machine_id),
  KEY idx_production_jobs_machine (machine_id),
  KEY idx_production_jobs_status (status),
  FOREIGN KEY (machine_id) REFERENCES machines(id),
  FOREIGN KEY (part_id) REFERENCES parts(id),
  FOREIGN KEY (cutter_id) REFERENCES cutters(id),
  FOREIGN KEY (current_operator_id) REFERENCES operators(id),
  FOREIGN KEY (created_by_user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS job_shifts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  job_id INT NOT NULL,
  shift_date DATE NOT NULL,
  shift VARCHAR(30) NOT NULL,
  shift_hours TINYINT UNSIGNED NOT NULL,
  operator_id INT NOT NULL,
  started_at DATETIME NOT NULL,
  ended_at DATETIME DEFAULT NULL,
  status ENUM('Running','Ended') NOT NULL DEFAULT 'Running',
  total_qty INT NOT NULL DEFAULT 0,
  ok_qty INT NOT NULL DEFAULT 0,
  mc_reject_qty INT NOT NULL DEFAULT 0,
  rm_defect_qty INT NOT NULL DEFAULT 0,
  rework_qty INT NOT NULL DEFAULT 0,
  downtime_min INT NOT NULL DEFAULT 0,
  machine_status ENUM('Running','Idle','Breakdown') NOT NULL DEFAULT 'Running',
  job_outcome ENUM('Continue Next Shift','Job Completed','Job Stopped') DEFAULT NULL,
  issue_code VARCHAR(100) DEFAULT NULL,
  remarks VARCHAR(255) DEFAULT NULL,
  started_by_user_id INT NOT NULL,
  active_job_id INT AS (CASE WHEN status = 'Running' THEN job_id ELSE NULL END) PERSISTENT,
  UNIQUE KEY unique_running_shift_per_job (active_job_id),
  KEY idx_job_shifts_job (job_id),
  KEY idx_job_shifts_operator (operator_id),
  FOREIGN KEY (job_id) REFERENCES production_jobs(id),
  FOREIGN KEY (operator_id) REFERENCES operators(id),
  FOREIGN KEY (started_by_user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS job_cutter_changes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  job_id INT NOT NULL,
  shift_id INT NOT NULL,
  old_cutter_id INT DEFAULT NULL,
  new_cutter_id INT NOT NULL,
  reason VARCHAR(150) NOT NULL,
  remarks VARCHAR(255) DEFAULT NULL,
  started_at DATETIME NOT NULL,
  completed_at DATETIME DEFAULT NULL,
  status ENUM('In Progress','Completed') NOT NULL DEFAULT 'In Progress',
  started_by_user_id INT NOT NULL,
  completed_by_user_id INT DEFAULT NULL,
  active_job_id INT AS (CASE WHEN status = 'In Progress' THEN job_id ELSE NULL END) PERSISTENT,
  UNIQUE KEY unique_active_cutter_change_per_job (active_job_id),
  KEY idx_cutter_changes_job (job_id),
  KEY idx_cutter_changes_shift (shift_id),
  FOREIGN KEY (job_id) REFERENCES production_jobs(id),
  FOREIGN KEY (shift_id) REFERENCES job_shifts(id),
  FOREIGN KEY (old_cutter_id) REFERENCES cutters(id),

  FOREIGN KEY (new_cutter_id) REFERENCES cutters(id),
  FOREIGN KEY (started_by_user_id) REFERENCES users(id),
  FOREIGN KEY (completed_by_user_id) REFERENCES users(id)
);


CREATE TABLE IF NOT EXISTS job_setting_changes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  old_job_id INT NOT NULL,
  old_shift_id INT NOT NULL,
  machine_id INT NOT NULL,
  operator_id INT NOT NULL,
  new_part_id INT NOT NULL,
  new_component VARCHAR(50) NOT NULL,
  new_drg_no VARCHAR(100) DEFAULT NULL,
  new_operation VARCHAR(150) DEFAULT NULL,
  new_cutter_id INT DEFAULT NULL,
  new_planned_qty INT NOT NULL DEFAULT 0,
  reason VARCHAR(150) NOT NULL,
  remarks VARCHAR(255) DEFAULT NULL,
  total_qty INT NOT NULL DEFAULT 0,
  ok_qty INT NOT NULL DEFAULT 0,
  mc_reject_qty INT NOT NULL DEFAULT 0,
  rm_defect_qty INT NOT NULL DEFAULT 0,
  rework_qty INT NOT NULL DEFAULT 0,
  started_at DATETIME NOT NULL,
  completed_at DATETIME DEFAULT NULL,
  status ENUM('In Progress','Completed') NOT NULL DEFAULT 'In Progress',
  new_job_id INT DEFAULT NULL,
  started_by_user_id INT NOT NULL,
  completed_by_user_id INT DEFAULT NULL,
  active_machine_id INT AS (CASE WHEN status = 'In Progress' THEN machine_id ELSE NULL END) PERSISTENT,
  UNIQUE KEY unique_active_setting_change_per_machine (active_machine_id),
  KEY idx_setting_changes_old_job (old_job_id),
  KEY idx_setting_changes_old_shift (old_shift_id),
  KEY idx_setting_changes_new_job (new_job_id),
  FOREIGN KEY (old_job_id) REFERENCES production_jobs(id),
  FOREIGN KEY (old_shift_id) REFERENCES job_shifts(id),
  FOREIGN KEY (machine_id) REFERENCES machines(id),
  FOREIGN KEY (operator_id) REFERENCES operators(id),
  FOREIGN KEY (new_part_id) REFERENCES parts(id),
  FOREIGN KEY (new_cutter_id) REFERENCES cutters(id),
  FOREIGN KEY (new_job_id) REFERENCES production_jobs(id),
  FOREIGN KEY (started_by_user_id) REFERENCES users(id),
  FOREIGN KEY (completed_by_user_id) REFERENCES users(id)
);

-- Independent ISO planning records. These records do not drive production jobs,
-- Daily Entry, the machine dashboard, or production reports.
CREATE TABLE IF NOT EXISTS weekly_plans (
  id INT AUTO_INCREMENT PRIMARY KEY,
  week_start DATE NOT NULL,
  week_end DATE NOT NULL,
  machine_id INT NOT NULL,
  shift VARCHAR(30) NOT NULL,
  part_id INT NOT NULL,
  operation VARCHAR(150) NOT NULL,
  planned_qty INT UNSIGNED NOT NULL,
  priority ENUM('Low','Normal','High','Urgent') NOT NULL DEFAULT 'Normal',
  remarks VARCHAR(255) DEFAULT NULL,
  status ENUM('Draft','Final') NOT NULL DEFAULT 'Draft',
  created_by_user_id INT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_weekly_plan_line (week_start, machine_id, shift, part_id, operation),
  KEY idx_weekly_plans_week (week_start),
  KEY idx_weekly_plans_machine (machine_id),
  FOREIGN KEY (machine_id) REFERENCES machines(id),
  FOREIGN KEY (part_id) REFERENCES parts(id),
  FOREIGN KEY (created_by_user_id) REFERENCES users(id)
);

-- Locked monthly ISO snapshots generated only from weekly_plans.
-- They remain independent from production jobs and production reporting.
CREATE TABLE IF NOT EXISTS monthly_plans (
  id INT AUTO_INCREMENT PRIMARY KEY,
  plan_month DATE NOT NULL UNIQUE,
  status ENUM('Draft','Locked') NOT NULL DEFAULT 'Draft',
  created_by_user_id INT NOT NULL,
  locked_by_user_id INT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  locked_at DATETIME DEFAULT NULL,
  FOREIGN KEY (created_by_user_id) REFERENCES users(id),
  FOREIGN KEY (locked_by_user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS monthly_plan_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  monthly_plan_id INT NOT NULL,
  machine_id INT NOT NULL,
  shift VARCHAR(30) NOT NULL,
  part_id INT NOT NULL,
  operation VARCHAR(150) NOT NULL,
  planned_qty INT UNSIGNED NOT NULL,
  weekly_line_count INT UNSIGNED NOT NULL DEFAULT 0,
  UNIQUE KEY unique_monthly_plan_line (monthly_plan_id, machine_id, shift, part_id, operation),
  KEY idx_monthly_plan_items_plan (monthly_plan_id),
  KEY idx_monthly_plan_items_machine (machine_id),
  FOREIGN KEY (monthly_plan_id) REFERENCES monthly_plans(id) ON DELETE CASCADE,
  FOREIGN KEY (machine_id) REFERENCES machines(id),
  FOREIGN KEY (part_id) REFERENCES parts(id)
);
