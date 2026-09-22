INSERT IGNORE INTO parts (part_name, coupling_type) VALUES
('NAF 10','NA Gear Coupling'),
('NAF 15','NA Gear Coupling'),
('NAF 20','NA Gear Coupling'),
('NAF 25','NA Gear Coupling'),
('NAF 30','NA Gear Coupling'),
('NAF 35','NA Gear Coupling'),
('NAF 40','NA Gear Coupling'),
('NAF 45','NA Gear Coupling'),
('NAF 50','NA Gear Coupling'),
('NAF 55','NA Gear Coupling'),
('NAF 60','NA Gear Coupling'),
('NAF 70','NA Gear Coupling'),
('NAF 80','NA Gear Coupling'),
('NAF 90','NA Gear Coupling'),
('NAF 100','NA Gear Coupling'),
('NAF 110','NA Gear Coupling'),
('NAF120','NA Gear Coupling'),
('NAF130','NA Gear Coupling'),
('NAF 140','NA Gear Coupling'),
('NAF 150','NA Gear Coupling');

INSERT IGNORE INTO part_components (part_id, component_name, sort_order)
SELECT id, 'Hub', 1 FROM parts WHERE coupling_type = 'NA Gear Coupling' AND part_name LIKE 'NAF%';

INSERT IGNORE INTO part_components (part_id, component_name, sort_order)
SELECT id, 'Sleeve', 2 FROM parts WHERE coupling_type = 'NA Gear Coupling' AND part_name LIKE 'NAF%';
