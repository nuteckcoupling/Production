ALTER TABLE daily_entries
  MODIFY COLUMN shift ENUM('1','2','3','Day Night','Night Day','Day Shift','Night Shift') NOT NULL;

UPDATE daily_entries SET shift = 'Day Shift' WHERE shift = 'Day Night';
UPDATE daily_entries SET shift = 'Night Shift' WHERE shift = 'Night Day';

ALTER TABLE daily_entries
  MODIFY COLUMN shift ENUM('1','2','3','Day Shift','Night Shift') NOT NULL;
