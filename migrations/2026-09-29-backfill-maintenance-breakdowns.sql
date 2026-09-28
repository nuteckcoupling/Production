INSERT INTO maintenance_tickets
  (machine_id, job_id, breakdown_type, problem_description, status, previous_job_status,
   reported_by_user_id, breakdown_started_at)
SELECT j.machine_id, j.id, 'Other', 'Existing breakdown migrated from V1', 'Open', 'Running',
       COALESCE(j.created_by_user_id, (SELECT MIN(u.id) FROM users u)), j.status_changed_at
FROM production_jobs j
WHERE j.status = 'Breakdown'
  AND NOT EXISTS (SELECT 1 FROM maintenance_tickets mt WHERE mt.machine_id = j.machine_id AND mt.status <> 'Closed');

UPDATE maintenance_tickets
SET ticket_no = CONCAT('BD-', LPAD(id, 6, '0'))
WHERE ticket_no IS NULL;
