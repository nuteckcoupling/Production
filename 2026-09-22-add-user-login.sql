CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('Operator/Supervisor','Admin') NOT NULL DEFAULT 'Operator/Supervisor',
  operator_id INT DEFAULT NULL UNIQUE,
  status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
  last_login DATETIME DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (operator_id) REFERENCES operators(id)
);

INSERT INTO users (username, password_hash, role, operator_id, status) VALUES
('admin', '$2y$10$pVxUqEd/sZV0VzgcVcnFXezVQLycosjLU02kjI90U6tDNnAtSLEq.', 'Admin', NULL, 'Active'),
('mukesh', '$2y$10$PfnZRFmiQ5qvjlTGrjxsOuggRHuu4.iAdFn2PNy7fmycCY6R9.iF.', 'Operator/Supervisor', 1, 'Active'),
('prakash', '$2y$10$PfnZRFmiQ5qvjlTGrjxsOuggRHuu4.iAdFn2PNy7fmycCY6R9.iF.', 'Operator/Supervisor', 2, 'Active'),
('vijay', '$2y$10$PfnZRFmiQ5qvjlTGrjxsOuggRHuu4.iAdFn2PNy7fmycCY6R9.iF.', 'Operator/Supervisor', 3, 'Active'),
('subdeep', '$2y$10$PfnZRFmiQ5qvjlTGrjxsOuggRHuu4.iAdFn2PNy7fmycCY6R9.iF.', 'Operator/Supervisor', 4, 'Active')
ON DUPLICATE KEY UPDATE
  password_hash = VALUES(password_hash),
  role = VALUES(role),
  operator_id = VALUES(operator_id),
  status = VALUES(status);
