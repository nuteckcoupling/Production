ALTER TABLE job_shifts
  ADD COLUMN report_edited_at DATETIME DEFAULT NULL AFTER remarks,
  ADD COLUMN report_edited_by_user_id INT DEFAULT NULL AFTER report_edited_at,
  ADD COLUMN report_edit_reason VARCHAR(255) DEFAULT NULL AFTER report_edited_by_user_id,
  ADD COLUMN report_original_data LONGTEXT DEFAULT NULL AFTER report_edit_reason,
  ADD KEY idx_job_shifts_report_editor (report_edited_by_user_id),
  ADD CONSTRAINT fk_job_shifts_report_editor
    FOREIGN KEY (report_edited_by_user_id) REFERENCES users(id);
