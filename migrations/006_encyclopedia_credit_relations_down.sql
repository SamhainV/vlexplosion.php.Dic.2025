-- Only reversible while the new structures are unused. Never use --force.
-- Once rich data/jobs exist, stop and plan recovery from a verified private backup.
-- A refused CALL may leave this diagnostic routine; do not retry automatically.
DELIMITER //
CREATE PROCEDURE vlex_credit006_can_down()
BEGIN
 IF EXISTS(SELECT 1 FROM ENC_CREDIT_JOBS LIMIT 1) OR
    EXISTS(SELECT 1 FROM ENC_CREDITS WHERE relation_key IS NOT NULL OR relation_json IS NOT NULL OR context_edition_id IS NOT NULL OR sync_active<>1 OR seen_at IS NOT NULL LIMIT 1)
 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='006 is in use: preserve rich credits and jobs; reviewed backup recovery required';
 END IF;
END//
CALL vlex_credit006_can_down()//
DROP PROCEDURE vlex_credit006_can_down//
DELIMITER ;
DROP TABLE ENC_CREDIT_JOBS;
ALTER TABLE ENC_CREDITS DROP FOREIGN KEY credit_context_fk,
 DROP INDEX credit_context, DROP INDEX credit_relation_identity,
 DROP COLUMN relation_key, DROP COLUMN relation_json,
 DROP COLUMN context_edition_id, DROP COLUMN sync_active, DROP COLUMN seen_at;
