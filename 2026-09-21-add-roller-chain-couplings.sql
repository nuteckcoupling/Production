ALTER TABLE parts
  MODIFY COLUMN coupling_type
    ENUM('Full Gear Coupling','Half Gear Coupling','Roller Chain Couplings') DEFAULT NULL;

ALTER TABLE daily_entries
  MODIFY COLUMN component VARCHAR(50) NULL;

CREATE TABLE IF NOT EXISTS part_components (
  id INT AUTO_INCREMENT PRIMARY KEY,
  part_id INT NOT NULL,
  component_name VARCHAR(50) NOT NULL,
  sort_order INT DEFAULT 0,
  UNIQUE KEY unique_part_component (part_id, component_name),
  FOREIGN KEY (part_id) REFERENCES parts(id) ON DELETE CASCADE
);

INSERT INTO parts (part_name, coupling_type)
SELECT seed.part_name, 'Roller Chain Couplings'
FROM (
  SELECT 'NT1016' part_name UNION ALL SELECT 'NT1018' UNION ALL
  SELECT 'NT1218' UNION ALL SELECT 'NT1222' UNION ALL
  SELECT 'NT1618' UNION ALL SELECT 'NT1622' UNION ALL
  SELECT 'NT2020' UNION ALL SELECT 'NT2418' UNION ALL
  SELECT 'NT2422' UNION ALL SELECT 'NT3218' UNION ALL
  SELECT 'NT8316' UNION ALL SELECT 'NT8312' UNION ALL
  SELECT 'NT6112' UNION ALL SELECT 'NT3222' UNION ALL
  SELECT 'NT4018' UNION ALL SELECT 'NT4022'
) seed
WHERE NOT EXISTS (
  SELECT 1 FROM parts p
  WHERE p.part_name = seed.part_name
    AND p.coupling_type = 'Roller Chain Couplings'
);

INSERT IGNORE INTO part_components (part_id, component_name, sort_order)
SELECT id, 'Hub', 1 FROM parts WHERE coupling_type IN ('Full Gear Coupling','Half Gear Coupling')
UNION ALL
SELECT id, 'Sleeve', 2 FROM parts WHERE coupling_type IN ('Full Gear Coupling','Half Gear Coupling');

INSERT IGNORE INTO part_components (part_id, component_name, sort_order)
SELECT p.id, c.component_name, c.sort_order
FROM parts p
JOIN (
  SELECT 'NT1016' part_name, 'Sprocket' component_name, 1 sort_order UNION ALL
  SELECT 'NT1016','Rubber Washer',2 UNION ALL SELECT 'NT1016','Steel Ring',3 UNION ALL SELECT 'NT1016','Aluminium Cover',4 UNION ALL SELECT 'NT1016','Rubber Packing',5 UNION ALL
  SELECT 'NT1018','Sprocket',1 UNION ALL SELECT 'NT1018','Rubber Washer',2 UNION ALL SELECT 'NT1018','Steel Ring',3 UNION ALL SELECT 'NT1018','Aluminium Cover',4 UNION ALL SELECT 'NT1018','Rubber Packing',5 UNION ALL
  SELECT 'NT1218','Sprocket',1 UNION ALL SELECT 'NT1218','Rubber Washer',2 UNION ALL SELECT 'NT1218','Steel Ring',3 UNION ALL SELECT 'NT1218','Aluminium Cover',4 UNION ALL SELECT 'NT1218','Rubber Packing',5 UNION ALL
  SELECT 'NT1222','Sprocket',1 UNION ALL SELECT 'NT1222','Rubber Washer',2 UNION ALL SELECT 'NT1222','Steel Ring',3 UNION ALL SELECT 'NT1222','Aluminium Cover',4 UNION ALL SELECT 'NT1222','Rubber Packing',5 UNION ALL
  SELECT 'NT1618','Sprocket',1 UNION ALL SELECT 'NT1618','Rubber Washer',2 UNION ALL SELECT 'NT1618','Aluminium Cover',3 UNION ALL SELECT 'NT1618','Rubber Packing',4 UNION ALL
  SELECT 'NT1622','Sprocket',1 UNION ALL SELECT 'NT1622','Rubber Washer',2 UNION ALL SELECT 'NT1622','Aluminium Cover',3 UNION ALL SELECT 'NT1622','Rubber Packing',4 UNION ALL
  SELECT 'NT2020','Sprocket',1 UNION ALL SELECT 'NT2020','Rubber Washer',2 UNION ALL SELECT 'NT2020','Aluminium Cover',3 UNION ALL SELECT 'NT2020','Rubber Packing',4 UNION ALL
  SELECT 'NT2418','Sprocket',1 UNION ALL SELECT 'NT2418','Rubber Washer',2 UNION ALL SELECT 'NT2418','Aluminium Cover',3 UNION ALL SELECT 'NT2418','Rubber Packing',4 UNION ALL
  SELECT 'NT2422','Sprocket',1 UNION ALL SELECT 'NT2422','Rubber Washer',2 UNION ALL SELECT 'NT2422','Aluminium Cover',3 UNION ALL SELECT 'NT2422','Rubber Packing',4 UNION ALL
  SELECT 'NT3218','Sprocket',1 UNION ALL SELECT 'NT3218','Rubber Washer',2 UNION ALL SELECT 'NT3218','Aluminium Cover',3 UNION ALL SELECT 'NT3218','Rubber Packing',4 UNION ALL
  SELECT 'NT8316','Sprocket',1 UNION ALL SELECT 'NT8316','Rubber Washer',2 UNION ALL SELECT 'NT8316','Steel Ring',3 UNION ALL SELECT 'NT8316','Rubber Packing',4 UNION ALL SELECT 'NT8316','Aluminium Cover',5 UNION ALL
  SELECT 'NT8312','Sprocket',1 UNION ALL SELECT 'NT8312','Rubber Washer',2 UNION ALL SELECT 'NT8312','Steel Ring',3 UNION ALL
  SELECT 'NT6112','Sprocket',1 UNION ALL SELECT 'NT6112','Rubber Washer',2 UNION ALL SELECT 'NT6112','Steel Ring',3 UNION ALL
  SELECT 'NT3222','Sprocket',1 UNION ALL SELECT 'NT3222','M.S Cover',2 UNION ALL
  SELECT 'NT4018','Sprocket',1 UNION ALL
  SELECT 'NT4022','Sprocket',1 UNION ALL SELECT 'NT4022','M.S Cover',2
) c ON c.part_name = p.part_name
WHERE p.coupling_type = 'Roller Chain Couplings';
