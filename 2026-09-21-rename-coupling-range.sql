ALTER TABLE parts
  MODIFY coupling_type ENUM(
    'Full Gear Coupling',
    'Half Gear Coupling',
    'Roller Chain Couplings',
    'GC Gear Coupling',
    'NA Gear Coupling',
    'Roller Chain Coupling',
    'Gear',
    'Sprocket'
  ) DEFAULT NULL;

UPDATE parts SET coupling_type = 'GC Gear Coupling' WHERE coupling_type = 'Full Gear Coupling';
UPDATE parts SET coupling_type = 'NA Gear Coupling' WHERE coupling_type = 'Half Gear Coupling';
UPDATE parts SET coupling_type = 'Roller Chain Coupling' WHERE coupling_type = 'Roller Chain Couplings';

ALTER TABLE parts
  MODIFY coupling_type ENUM(
    'GC Gear Coupling',
    'NA Gear Coupling',
    'Roller Chain Coupling',
    'Gear',
    'Sprocket'
  ) DEFAULT NULL;
