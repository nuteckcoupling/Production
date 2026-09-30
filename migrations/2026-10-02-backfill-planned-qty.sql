UPDATE production_jobs j
SET j.planned_qty = GREATEST(
  COALESCE((SELECT MAX(de.total_qty) FROM daily_entries de
            WHERE de.machine_id = j.machine_id AND de.part_id = j.part_id AND de.shift_hours = 12), 0),
  COALESCE((SELECT MAX(js.total_qty) FROM job_shifts js
            JOIN production_jobs history_job ON history_job.id = js.job_id
            WHERE history_job.machine_id = j.machine_id AND history_job.part_id = j.part_id
              AND js.shift_hours = 12 AND js.status = 'Ended'), 0)
)
WHERE j.planned_qty = 0;
