ALTER TABLE job_shifts
  ADD COLUMN IF NOT EXISTS machine_status ENUM('Running','Idle','Breakdown') NOT NULL DEFAULT 'Running' AFTER downtime_min,
  ADD COLUMN IF NOT EXISTS job_outcome ENUM('Continue Next Shift','Job Completed','Job Stopped') DEFAULT NULL AFTER machine_status;
