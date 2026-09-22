ALTER TABLE parts
  ADD COLUMN IF NOT EXISTS coupling_type
    ENUM('Full Gear Coupling','Half Gear Coupling') DEFAULT NULL AFTER part_name;

ALTER TABLE daily_entries
  ADD COLUMN IF NOT EXISTS component
    ENUM('Hub','Sleeve') NULL AFTER part_id;

INSERT INTO parts (part_name, coupling_type)
SELECT seed.part_name, seed.coupling_type
FROM (
  SELECT 'GC-100' part_name, 'Full Gear Coupling' coupling_type UNION ALL
  SELECT 'GC-101', 'Full Gear Coupling' UNION ALL
  SELECT 'GC-102', 'Full Gear Coupling' UNION ALL
  SELECT 'GC-103', 'Full Gear Coupling' UNION ALL
  SELECT 'GC-104', 'Full Gear Coupling' UNION ALL
  SELECT 'GC-105', 'Full Gear Coupling' UNION ALL
  SELECT 'GC-106', 'Full Gear Coupling' UNION ALL
  SELECT 'GC-107', 'Full Gear Coupling' UNION ALL
  SELECT 'GC-108', 'Full Gear Coupling' UNION ALL
  SELECT 'GC-109', 'Full Gear Coupling' UNION ALL
  SELECT 'GC-110', 'Full Gear Coupling' UNION ALL
  SELECT 'GC-111', 'Full Gear Coupling' UNION ALL
  SELECT 'GC-112', 'Full Gear Coupling' UNION ALL
  SELECT 'GC-113', 'Full Gear Coupling' UNION ALL
  SELECT 'GC-114', 'Full Gear Coupling' UNION ALL
  SELECT 'GC-115', 'Full Gear Coupling' UNION ALL
  SELECT 'GC-115 M', 'Full Gear Coupling' UNION ALL
  SELECT 'GC-116', 'Full Gear Coupling' UNION ALL
  SELECT 'GC-116 M', 'Full Gear Coupling' UNION ALL
  SELECT 'GC-117', 'Full Gear Coupling' UNION ALL
  SELECT 'GC-118', 'Full Gear Coupling' UNION ALL
  SELECT 'HGC-100', 'Half Gear Coupling' UNION ALL
  SELECT 'HGC-101', 'Half Gear Coupling' UNION ALL
  SELECT 'HGC-102', 'Half Gear Coupling' UNION ALL
  SELECT 'HGC-103', 'Half Gear Coupling' UNION ALL
  SELECT 'HGC-104', 'Half Gear Coupling' UNION ALL
  SELECT 'HGC-105', 'Half Gear Coupling' UNION ALL
  SELECT 'HGC-106', 'Half Gear Coupling' UNION ALL
  SELECT 'HGC-107', 'Half Gear Coupling' UNION ALL
  SELECT 'HGC-108', 'Half Gear Coupling' UNION ALL
  SELECT 'HGC-109', 'Half Gear Coupling' UNION ALL
  SELECT 'HGC-110', 'Half Gear Coupling'
) seed
WHERE NOT EXISTS (
  SELECT 1 FROM parts p
  WHERE p.part_name = seed.part_name
    AND p.coupling_type = seed.coupling_type
);
