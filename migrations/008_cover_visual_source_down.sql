-- Refuse to discard any confirmed visual source. Never run automatically.
SET @visual_exists=(SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='CAA_PREFERENCES' AND COLUMN_NAME='visual_source_json');
SET @visual_sql=IF(@visual_exists=1,'SELECT COUNT(*) INTO @visual_used FROM CAA_PREFERENCES WHERE visual_source_json IS NOT NULL','SET @visual_used=0');
PREPARE visual_stmt FROM @visual_sql; EXECUTE visual_stmt; DEALLOCATE PREPARE visual_stmt;
SET @visual_guard=IF(@visual_used>0,'SIGNAL SQLSTATE ''45000'' SET MESSAGE_TEXT=''008 in use: explicit recovery of visual preferences required''','DO 0');
PREPARE visual_stmt FROM @visual_guard; EXECUTE visual_stmt; DEALLOCATE PREPARE visual_stmt;
ALTER TABLE CAA_PREFERENCES DROP COLUMN IF EXISTS visual_source_json;
