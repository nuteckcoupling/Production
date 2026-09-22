ALTER TABLE parts
  ADD UNIQUE INDEX IF NOT EXISTS unique_part_name (part_name);
