ALTER TABLE cutters
  MODIFY COLUMN status ENUM('Active','Retired','Not Active') DEFAULT 'Active';

UPDATE cutters SET status = 'Not Active' WHERE status = 'Retired';

ALTER TABLE cutters
  MODIFY COLUMN status ENUM('Active','Not Active') DEFAULT 'Active';

ALTER TABLE cutters
  ADD COLUMN IF NOT EXISTS remarks VARCHAR(255) DEFAULT NULL AFTER status;
