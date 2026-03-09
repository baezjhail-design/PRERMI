-- Migration script: rename old `multas` table to `sanciones` if present
-- Run this once manually via phpMyAdmin or mysql CLI

SET @exists = (SELECT COUNT(*) FROM information_schema.tables 
               WHERE table_schema = DATABASE() AND table_name = 'multas');

IF @exists THEN
    RENAME TABLE multas TO sanciones;
    -- adjust foreign key names if necessary (optional, depends on configuration)
END IF;
