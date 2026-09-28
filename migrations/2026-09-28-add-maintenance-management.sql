ALTER TABLE users
  MODIFY COLUMN role ENUM('Operator/Supervisor','Maintenance','Admin') NOT NULL DEFAULT 'Operator/Supervisor';

CREATE TABLE IF NOT EXISTS maintenance_tickets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ticket_no VARCHAR(30) DEFAULT NULL UNIQUE,
  machine_id INT NOT NULL,
  job_id INT DEFAULT NULL,
  breakdown_type ENUM('Mechanical','Electrical','Other') NOT NULL,
  problem_description VARCHAR(500) NOT NULL,
  status ENUM('Open','Assigned','In Progress','Repair Completed','Closed') NOT NULL DEFAULT 'Open',
  previous_job_status VARCHAR(30) DEFAULT NULL,
  reported_by_user_id INT NOT NULL,
  breakdown_started_at DATETIME NOT NULL,
  assigned_staff_id INT DEFAULT NULL,
  assigned_by_user_id INT DEFAULT NULL,
  assigned_at DATETIME DEFAULT NULL,
  work_started_at DATETIME DEFAULT NULL,
  repair_details VARCHAR(1000) DEFAULT NULL,
  spare_parts_used VARCHAR(500) DEFAULT NULL,
  repair_completed_at DATETIME DEFAULT NULL,
  testing_remarks VARCHAR(500) DEFAULT NULL,
  machine_running_confirmed_at DATETIME DEFAULT NULL,
  closed_by_user_id INT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  active_machine_id INT AS (CASE WHEN status <> 'Closed' THEN machine_id ELSE NULL END) STORED,
  UNIQUE KEY unique_open_maintenance_ticket_per_machine (active_machine_id),
  KEY idx_maintenance_status (status),
  KEY idx_maintenance_machine (machine_id),
  KEY idx_maintenance_staff (assigned_staff_id),
  FOREIGN KEY (machine_id) REFERENCES machines(id),
  FOREIGN KEY (job_id) REFERENCES production_jobs(id),
  FOREIGN KEY (reported_by_user_id) REFERENCES users(id),
  FOREIGN KEY (assigned_staff_id) REFERENCES operators(id),
  FOREIGN KEY (assigned_by_user_id) REFERENCES users(id),
  FOREIGN KEY (closed_by_user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS maintenance_attachments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ticket_id INT NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  stored_name VARCHAR(100) NOT NULL UNIQUE,
  mime_type VARCHAR(100) NOT NULL,
  file_size INT UNSIGNED NOT NULL,
  uploaded_by_user_id INT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_maintenance_attachment_ticket (ticket_id),
  FOREIGN KEY (ticket_id) REFERENCES maintenance_tickets(id) ON DELETE CASCADE,
  FOREIGN KEY (uploaded_by_user_id) REFERENCES users(id)
);
