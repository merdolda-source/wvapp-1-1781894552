-- WebView App Builder - MySQL schema
-- Import this once on your hosting's MySQL database.

CREATE TABLE IF NOT EXISTS users (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(120)        NOT NULL,
    email           VARCHAR(190)        NOT NULL UNIQUE,
    password_hash   VARCHAR(255)        NULL,
    google_id       VARCHAR(64)         NULL UNIQUE,
    avatar_url      VARCHAR(500)        NULL,
    created_at      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS apps (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id             INT UNSIGNED        NOT NULL,
    name                VARCHAR(120)        NOT NULL,
    package_id          VARCHAR(150)        NOT NULL UNIQUE,
    target_url          VARCHAR(500)        NOT NULL,
    icon_path           VARCHAR(255)        NULL,
    header_color        VARCHAR(9)          NOT NULL DEFAULT '#2563EB',
    splash_bg_color     VARCHAR(9)          NOT NULL DEFAULT '#2563EB',
    splash_text_color   VARCHAR(9)          NOT NULL DEFAULT '#FFFFFF',
    splash_text         VARCHAR(120)        NOT NULL DEFAULT '',
    splash_show_icon    TINYINT(1)          NOT NULL DEFAULT 1,
    splash_duration     TINYINT UNSIGNED    NOT NULL DEFAULT 2,
    font_name           VARCHAR(60)         NOT NULL DEFAULT 'default',
    version_code        INT UNSIGNED        NOT NULL DEFAULT 0,
    version_name        VARCHAR(30)         NOT NULL DEFAULT '1.0.0',
    key_alias           VARCHAR(60)         NOT NULL,
    key_password        VARCHAR(60)         NOT NULL,
    store_password      VARCHAR(60)         NOT NULL,
    keystore_base64     LONGTEXT            NULL,
    status              ENUM('draft','queued','building','ready','failed') NOT NULL DEFAULT 'draft',
    created_at          DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_apps_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS builds (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    app_id          INT UNSIGNED        NOT NULL,
    build_token     VARCHAR(64)         NOT NULL UNIQUE,
    version_code    INT UNSIGNED        NOT NULL,
    version_name    VARCHAR(30)         NOT NULL,
    github_run_id   BIGINT UNSIGNED     NULL,
    status          ENUM('queued','building','success','failed') NOT NULL DEFAULT 'queued',
    apk_path        VARCHAR(255)        NULL,
    aab_path        VARCHAR(255)        NULL,
    log_url         VARCHAR(500)        NULL,
    created_at      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_builds_app FOREIGN KEY (app_id) REFERENCES apps(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- One row per APK/AAB download click, used for the daily downloads chart.
CREATE TABLE IF NOT EXISTS download_logs (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    app_id      INT UNSIGNED    NOT NULL,
    type        ENUM('apk','aab') NOT NULL,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_download_logs_app FOREIGN KEY (app_id) REFERENCES apps(id) ON DELETE CASCADE,
    INDEX idx_download_logs_app_date (app_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- One row every time an installed app calls /api/config on launch, used for
-- the monthly active-usage chart.
CREATE TABLE IF NOT EXISTS usage_logs (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    app_id      INT UNSIGNED    NOT NULL,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_usage_logs_app FOREIGN KEY (app_id) REFERENCES apps(id) ON DELETE CASCADE,
    INDEX idx_usage_logs_app_date (app_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
