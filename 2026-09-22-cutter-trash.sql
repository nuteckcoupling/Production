USE prodction;

ALTER TABLE cutters
  ADD COLUMN IF NOT EXISTS deleted_at DATETIME DEFAULT NULL AFTER remarks,
  ADD INDEX IF NOT EXISTS idx_cutters_deleted_at (deleted_at);

