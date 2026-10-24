-- Run once on the production database before deploying this feature.
ALTER TABLE job_shifts
  ADD COLUMN cycle_time_min_per_piece DECIMAL(10,2) DEFAULT NULL AFTER total_qty;
