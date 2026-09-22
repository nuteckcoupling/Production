INSERT INTO operators (name, status)
SELECT seed.name, 'Active'
FROM (
  SELECT 'Mukesh' name UNION ALL
  SELECT 'Prakash' UNION ALL
  SELECT 'vijay' UNION ALL
  SELECT 'subdeep'
) seed
WHERE NOT EXISTS (
  SELECT 1 FROM operators existing
  WHERE existing.name = seed.name
);
