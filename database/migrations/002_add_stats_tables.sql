-- Run this once against an existing database that was created before the
-- download/usage statistics feature existed. New installs don't need this
-- file - it's already included in database/schema.sql.

CREATE TABLE IF NOT EXISTS download_logs (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    app_id      INT UNSIGNED    NOT NULL,
    type        ENUM('apk','aab') NOT NULL,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_download_logs_app FOREIGN KEY (app_id) REFERENCES apps(id) ON DELETE CASCADE,
    INDEX idx_download_logs_app_date (app_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS usage_logs (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    app_id      INT UNSIGNED    NOT NULL,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_usage_logs_app FOREIGN KEY (app_id) REFERENCES apps(id) ON DELETE CASCADE,
    INDEX idx_usage_logs_app_date (app_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
