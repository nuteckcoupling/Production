-- Cutter vendor directory and batch dispatch/individual return workflow.
-- Apply once to the target database before deploying the matching PHP files.

ALTER TABLE cutter_service_events
  MODIFY event_type ENUM('Coating', 'Grinding', 'Both') NOT NULL;

CREATE TABLE IF NOT EXISTS cutter_service_vendors (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  address VARCHAR(500) NOT NULL,
  phone VARCHAR(30) DEFAULT NULL,
  status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_cutter_service_vendors_status (status),
  KEY idx_cutter_service_vendors_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cutter_service_challans (
  id INT AUTO_INCREMENT PRIMARY KEY,
  challan_no VARCHAR(32) DEFAULT NULL,
  vendor_id INT NOT NULL,
  dispatch_date DATE NOT NULL,
  expected_return_date DATE DEFAULT NULL,
  status ENUM('Issued', 'Partially Returned', 'Returned', 'Cancelled') NOT NULL DEFAULT 'Issued',
  remarks VARCHAR(255) DEFAULT NULL,
  created_by_user_id INT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_cutter_service_challan_no (challan_no),
  KEY idx_cutter_service_challan_vendor (vendor_id, dispatch_date),
  KEY idx_cutter_service_challan_status (status),
  CONSTRAINT fk_cutter_service_challan_vendor FOREIGN KEY (vendor_id) REFERENCES cutter_service_vendors(id),
  CONSTRAINT fk_cutter_service_challan_user FOREIGN KEY (created_by_user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cutter_service_challan_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  challan_id INT NOT NULL,
  cutter_id INT NOT NULL,
  service_type ENUM('Coating', 'Grinding', 'Both') NOT NULL,
  sent_remarks VARCHAR(255) DEFAULT NULL,
  received_at DATETIME DEFAULT NULL,
  service_done TINYINT(1) DEFAULT NULL,
  service_date DATE DEFAULT NULL,
  return_condition VARCHAR(80) DEFAULT NULL,
  return_remarks VARCHAR(255) DEFAULT NULL,
  service_event_id INT DEFAULT NULL,
  received_by_user_id INT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_service_event_per_challan_item (service_event_id),
  KEY idx_cutter_service_items_open (cutter_id, received_at),
  KEY idx_cutter_service_items_challan (challan_id, id),
  CONSTRAINT fk_cutter_service_item_challan FOREIGN KEY (challan_id) REFERENCES cutter_service_challans(id),
  CONSTRAINT fk_cutter_service_item_cutter FOREIGN KEY (cutter_id) REFERENCES cutters(id),
  CONSTRAINT fk_cutter_service_item_event FOREIGN KEY (service_event_id) REFERENCES cutter_service_events(id),
  CONSTRAINT fk_cutter_service_item_receiver FOREIGN KEY (received_by_user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
