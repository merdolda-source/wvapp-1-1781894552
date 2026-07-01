-- Run this once against an existing database that was created before the
-- splash icon/duration feature existed. New installs don't need this file -
-- it's already included in database/schema.sql.

ALTER TABLE apps
    ADD COLUMN splash_show_icon TINYINT(1) NOT NULL DEFAULT 1 AFTER splash_text,
    ADD COLUMN splash_duration TINYINT UNSIGNED NOT NULL DEFAULT 2 AFTER splash_show_icon;
