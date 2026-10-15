-- Synthetic accounts and vocabulary only; socket-isolated server required by runner.
INSERT INTO Users_TBL(id,username,password,email) VALUES(7,'fiction','fiction','fiction@example.invalid'),(8,'other','fiction','other@example.invalid');
INSERT INTO GENRES_TBL(Id,Genre_Name) VALUES(1,'Fictional electronic');
INSERT INTO FORMAT_TBL(Id,Format_Name) VALUES(1,'Vinyl');
INSERT INTO CONDITION_TBL(Id,Condition_Name) VALUES(1,'Fictional good');
INSERT INTO RECORD_LABEL_TBL(Id,Record_Label_Name) VALUES(1,'Fictional label');
INSERT INTO EDITION_TBL(Id,Edition_Name) VALUES(1,'Fiction');
CREATE USER 'test_runner'@'localhost' IDENTIFIED BY '';
GRANT SELECT,INSERT,UPDATE,DELETE ON vlexplosion_test.* TO 'test_runner'@'localhost';
DELIMITER //
CREATE TRIGGER credit_failure BEFORE INSERT ON ENC_CREDITS FOR EACH ROW
BEGIN
 IF NEW.role='FORCE_ROLLBACK' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Synthetic transactional failure'; END IF;
END//
CREATE TRIGGER legacy_failure BEFORE INSERT ON VINYLS_TBL FOR EACH ROW
BEGIN
 IF NEW.Title='HTTP rollback upload' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Synthetic legacy transactional failure'; END IF;
END//
DELIMITER ;
