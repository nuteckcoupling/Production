ALTER TABLE daily_entries
  MODIFY COLUMN shift ENUM('1','2','3','Day Night','Night Day') NOT NULL;
