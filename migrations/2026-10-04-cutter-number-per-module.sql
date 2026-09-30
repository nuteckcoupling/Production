UPDATE cutters SET cutter_module = '' WHERE cutter_module IS NULL;

ALTER TABLE cutters
  DROP INDEX cutter_num,
  ADD UNIQUE KEY unique_cutter_number_module (cutter_num, cutter_module);
