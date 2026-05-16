USE noisescope;

-- Add new columns to existing noise_data table
ALTER TABLE noise_data
    ADD COLUMN peak_db          INT(3)      DEFAULT NULL  AFTER avg_noise_level_db,
    ADD COLUMN place_type       VARCHAR(100) DEFAULT NULL AFTER environment_type,
    ADD COLUMN time_of_day      ENUM('morning','afternoon','evening','night') DEFAULT NULL AFTER place_type,
    ADD COLUMN dominant_sound   VARCHAR(100) DEFAULT NULL AFTER time_of_day,
    ADD COLUMN weather          VARCHAR(50)  DEFAULT NULL AFTER dominant_sound,
    ADD COLUMN hour_of_day      TINYINT     DEFAULT NULL AFTER weather,
    ADD COLUMN day_of_week      TINYINT     DEFAULT NULL AFTER hour_of_day,
    ADD COLUMN day_type         ENUM('weekday','weekend') DEFAULT NULL AFTER day_of_week,
    ADD COLUMN ip_hash          CHAR(64)    DEFAULT NULL AFTER day_type;

-- Add index for location queries (speeds up map loading)
ALTER TABLE noise_data
    ADD INDEX idx_location (latitude, longitude),
    ADD INDEX idx_time     (timestamp);