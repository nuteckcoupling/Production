ALTER TABLE attachments
  MODIFY category ENUM('Job Drawing','Job Photo','Part Photo','Coupling Drawing','Breakdown Before','Breakdown After','QC Inspection') NOT NULL,
  ADD COLUMN component VARCHAR(50) DEFAULT NULL AFTER category,
  ADD COLUMN drawing_no VARCHAR(100) DEFAULT NULL AFTER component,
  ADD UNIQUE KEY unique_coupling_drawing (entity_type, entity_id, category, component, drawing_no);
