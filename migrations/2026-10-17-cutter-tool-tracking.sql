ALTER TABLE cutters
  ADD COLUMN IF NOT EXISTS cutter_name VARCHAR(100) DEFAULT NULL AFTER cutter_num,
  ADD COLUMN IF NOT EXISTS cutter_tooth_count INT UNSIGNED DEFAULT NULL AFTER cutter_thickness,
  ADD COLUMN IF NOT EXISTS cutter_tooth_thickness DECIMAL(8,2) DEFAULT NULL AFTER cutter_tooth_count,
  ADD COLUMN IF NOT EXISTS purchase_date DATE DEFAULT NULL AFTER cutter_tooth_thickness,
  ADD COLUMN IF NOT EXISTS scrap_date DATE DEFAULT NULL AFTER purchase_date;

CREATE TABLE IF NOT EXISTS cutter_service_events (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cutter_id INT NOT NULL,
  event_type ENUM('Coating','Grinding') NOT NULL,
  event_date DATE NOT NULL,
  remarks VARCHAR(255) DEFAULT NULL,
  created_by_user_id INT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_cutter_service_history (cutter_id, event_date, id),
  FOREIGN KEY (cutter_id) REFERENCES cutters(id),
  FOREIGN KEY (created_by_user_id) REFERENCES users(id)
);
