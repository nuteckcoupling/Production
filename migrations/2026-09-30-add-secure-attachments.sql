CREATE TABLE IF NOT EXISTS attachments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  entity_type ENUM('production_job','part','maintenance_ticket') NOT NULL,
  entity_id INT NOT NULL,
  category ENUM('Job Drawing','Job Photo','Part Photo','Breakdown Before','Breakdown After','QC Inspection') NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  stored_name VARCHAR(100) NOT NULL UNIQUE,
  relative_path VARCHAR(255) NOT NULL UNIQUE,
  mime_type VARCHAR(100) NOT NULL,
  file_size INT UNSIGNED NOT NULL,
  uploaded_by_user_id INT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_attachments_entity (entity_type, entity_id),
  KEY idx_attachments_category (category),
  FOREIGN KEY (uploaded_by_user_id) REFERENCES users(id)
);

INSERT INTO attachments
  (entity_type, entity_id, category, original_name, stored_name, relative_path, mime_type, file_size, uploaded_by_user_id, created_at)
SELECT 'maintenance_ticket', ticket_id, 'Breakdown Before', original_name, stored_name,
       CONCAT('uploads/breakdowns/', stored_name), mime_type, file_size, uploaded_by_user_id, created_at
FROM maintenance_attachments old_attachment
WHERE NOT EXISTS (
  SELECT 1 FROM attachments new_attachment
  WHERE new_attachment.relative_path = CONCAT('uploads/breakdowns/', old_attachment.stored_name)
);

DROP TRIGGER IF EXISTS protect_part_attachments;
CREATE TRIGGER protect_part_attachments BEFORE DELETE ON parts
FOR EACH ROW
BEGIN
  IF EXISTS (SELECT 1 FROM attachments WHERE entity_type = 'part' AND entity_id = OLD.id) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Part has protected attachments and cannot be deleted';
  END IF;
END;

DROP TRIGGER IF EXISTS protect_job_attachments;
CREATE TRIGGER protect_job_attachments BEFORE DELETE ON production_jobs
FOR EACH ROW
BEGIN
  IF EXISTS (SELECT 1 FROM attachments WHERE entity_type = 'production_job' AND entity_id = OLD.id) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Production job has protected attachments and cannot be deleted';
  END IF;
END;

DROP TRIGGER IF EXISTS protect_maintenance_attachments;
CREATE TRIGGER protect_maintenance_attachments BEFORE DELETE ON maintenance_tickets
FOR EACH ROW
BEGIN
  IF EXISTS (SELECT 1 FROM attachments WHERE entity_type = 'maintenance_ticket' AND entity_id = OLD.id) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Maintenance ticket has protected attachments and cannot be deleted';
  END IF;
END;
