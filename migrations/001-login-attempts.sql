CREATE TABLE IF NOT EXISTS login_attempts (
    attempt_key CHAR(64) PRIMARY KEY,
    attempts INT NOT NULL DEFAULT 0,
    expires_at DATETIME NOT NULL,
    INDEX idx_login_expires (expires_at)
) ENGINE=InnoDB;
