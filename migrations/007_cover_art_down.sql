-- Operator must first archive automatic-image provenance and stop cover jobs.
-- Does not delete images, manual Image_Path, musical data, or user records.
SET @caa_count_sql = IF((SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ('CAA_IMAGES','CAA_PREFERENCES','CAA_BATCHES'))=0,
 'SET @caa_rows=0',
 'SELECT (SELECT COUNT(*) FROM CAA_IMAGES)+(SELECT COUNT(*) FROM CAA_PREFERENCES)+(SELECT COUNT(*) FROM CAA_BATCHES) INTO @caa_rows');
PREPARE caa_count_stmt FROM @caa_count_sql;
EXECUTE caa_count_stmt;
DEALLOCATE PREPARE caa_count_stmt;
SET @caa_guard = IF(@caa_rows>0,
  'SIGNAL SQLSTATE ''45000'' SET MESSAGE_TEXT=''007 in use: archive provenance and jobs before an approved downgrade''', 'DO 0');
PREPARE caa_guard_stmt FROM @caa_guard;
EXECUTE caa_guard_stmt;
DEALLOCATE PREPARE caa_guard_stmt;
DROP TABLE IF EXISTS CAA_BATCHES;
DROP TABLE IF EXISTS CAA_PREFERENCES;
DROP TABLE IF EXISTS CAA_IMAGES;
