CREATE TABLE IF NOT EXISTS production_jobs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  machine_id INT NOT NULL,
  part_id INT NOT NULL,
  component VARCHAR(50) NOT NULL,
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
  active_machine_id INT AS (
    CASE WHEN status IN ('Running','Handover Pending','Breakdown','Stopped') THEN machine_id ELSE NULL END
  ) PERSISTENT,
  UNIQUE KEY unique_active_job_per_machine (active_machine_id),
  KEY idx_production_jobs_machine (machine_id),
  KEY idx_production_jobs_status (status),
  FOREIGN KEY (machine_id) REFERENCES machines(id),
  FOREIGN KEY (part_id) REFERENCES parts(id),
  FOREIGN KEY (cutter_id) REFERENCES cutters(id),
  FOREIGN KEY (current_operator_id) REFERENCES operators(id)
);
