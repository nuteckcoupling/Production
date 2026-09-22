ALTER TABLE production_jobs
  ADD COLUMN drg_no VARCHAR(100) DEFAULT NULL AFTER component,
  ADD COLUMN created_by_user_id INT DEFAULT NULL AFTER remarks,
  ADD CONSTRAINT fk_production_jobs_created_by FOREIGN KEY (created_by_user_id) REFERENCES users(id);

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
