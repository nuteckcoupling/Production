DELETE pc
FROM part_components pc
JOIN parts p ON p.id = pc.part_id
WHERE p.coupling_type = 'Roller Chain Couplings'
  AND pc.component_name <> 'Sprocket';
