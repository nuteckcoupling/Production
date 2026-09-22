ALTER TABLE daily_entries
  ADD COLUMN IF NOT EXISTS shift_hours TINYINT UNSIGNED NOT NULL DEFAULT 12 AFTER shift;

UPDATE daily_entries
SET shift_hours = CASE
  WHEN shift = '1' THEN 14
  ELSE 12
END;
