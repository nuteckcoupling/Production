ALTER TABLE cutters
  ADD COLUMN IF NOT EXISTS hob_pressure_angle DECIMAL(5,2) DEFAULT NULL AFTER lead_angle,
  ADD COLUMN IF NOT EXISTS cutter_inner_diameter DECIMAL(8,2) DEFAULT NULL AFTER hob_pressure_angle,
  ADD COLUMN IF NOT EXISTS cutter_outer_diameter DECIMAL(8,2) DEFAULT NULL AFTER cutter_inner_diameter,
  ADD COLUMN IF NOT EXISTS cutter_thickness DECIMAL(8,2) DEFAULT NULL AFTER cutter_outer_diameter;
