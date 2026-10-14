-- GUARDED rollback for a migration rehearsal before any new musical data exist.
-- After adding new data, restore a reviewed backup instead; never discard that data automatically.
DELIMITER //
CREATE PROCEDURE vlex_guarded_rollback()
BEGIN
 IF EXISTS(SELECT 1 FROM ALBUMS_TBL WHERE Legacy_Vinyl_Id IS NULL)
 OR EXISTS(SELECT 1 FROM MUSICAL_RELEASES_TBL)
 OR EXISTS(SELECT 1 FROM AI_SAVE_KEYS_TBL)
 OR EXISTS(SELECT 1 FROM VINYLS_TBL WHERE Genres_Id IS NULL OR Format_Id IS NULL OR Condition_Id IS NULL OR Record_Label_Id IS NULL OR Producer IS NULL OR Release_date IS NULL OR Edition_Id IS NULL OR Notes IS NOT NULL)
 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Rollback blocked: preserve new data and restore a reviewed backup'; END IF;
 ALTER TABLE VINYLS_TBL DROP FOREIGN KEY vinyl_release_owner, DROP FOREIGN KEY vinyl_album_owner, DROP CONSTRAINT vinyl_release_album,
 DROP INDEX idx_vinyl_release_album_user, DROP INDEX idx_vinyl_album_user,
 DROP COLUMN Musical_Release_Id, DROP COLUMN Album_Id, DROP COLUMN Notes,
 MODIFY Genres_Id INT NOT NULL, MODIFY Format_Id INT NOT NULL, MODIFY Condition_Id INT NOT NULL,
 MODIFY Record_Label_Id INT NOT NULL, MODIFY Producer VARCHAR(50) NOT NULL, MODIFY Release_date YEAR NOT NULL, MODIFY Edition_Id INT NOT NULL;
 DROP TABLE AI_SAVE_KEYS_TBL; DROP TABLE MUSICAL_RELEASES_TBL; DROP TABLE ALBUMS_TBL;
END//
DELIMITER ;
CALL vlex_guarded_rollback();
DROP PROCEDURE vlex_guarded_rollback;
