-- Review and apply ONLY to a backed-up staging copy first. MariaDB DDL implicitly commits.
CREATE TABLE ALBUMS_TBL (
 Id INT AUTO_INCREMENT PRIMARY KEY, User_Id INT NOT NULL, Title VARCHAR(65) NOT NULL,
 Artists_JSON LONGTEXT NOT NULL CHECK (JSON_VALID(Artists_JSON)), Original_Year SMALLINT NULL,
 Genre VARCHAR(120) NULL, MusicBrainz_Id CHAR(36) NULL, Legacy_Vinyl_Id INT NULL,
 Metadata_Source VARCHAR(32) NOT NULL DEFAULT 'manual',
 UNIQUE KEY album_owner (Id,User_Id), UNIQUE KEY album_mbid (User_Id,MusicBrainz_Id),
 UNIQUE KEY album_legacy (User_Id,Legacy_Vinyl_Id),
 FOREIGN KEY (User_Id) REFERENCES Users_TBL(id) ON DELETE RESTRICT,
 CHECK (Original_Year IS NULL OR Original_Year BETWEEN 1000 AND 2155)
) ENGINE=InnoDB;
CREATE TABLE MUSICAL_RELEASES_TBL (
 Id INT AUTO_INCREMENT PRIMARY KEY, Album_Id INT NOT NULL, User_Id INT NOT NULL,
 Country VARCHAR(80) NULL, Release_Year SMALLINT NULL, Record_Label VARCHAR(120) NULL,
 Catalogue_Number VARCHAR(100) NULL, Barcode VARCHAR(32) NULL, Format VARCHAR(80) NULL,
 Pressing VARCHAR(255) NULL, MusicBrainz_Id CHAR(36) NULL,
 UNIQUE KEY release_owner (Id,Album_Id,User_Id), UNIQUE KEY release_mbid (User_Id,MusicBrainz_Id),
 FOREIGN KEY (Album_Id,User_Id) REFERENCES ALBUMS_TBL(Id,User_Id) ON DELETE RESTRICT
) ENGINE=InnoDB;
ALTER TABLE VINYLS_TBL
 MODIFY Genres_Id INT NULL, MODIFY Format_Id INT NULL, MODIFY Condition_Id INT NULL,
 MODIFY Record_Label_Id INT NULL, MODIFY Producer VARCHAR(50) NULL,
 MODIFY Release_date YEAR NULL, MODIFY Edition_Id INT NULL,
 ADD Album_Id INT NULL, ADD Musical_Release_Id INT NULL, ADD Notes TEXT NULL,
 ADD INDEX idx_vinyl_album_user (Album_Id,User_Id),
 ADD INDEX idx_vinyl_release_album_user (Musical_Release_Id,Album_Id,User_Id),
 ADD CONSTRAINT vinyl_album_owner FOREIGN KEY (Album_Id,User_Id) REFERENCES ALBUMS_TBL(Id,User_Id) ON DELETE RESTRICT,
 ADD CONSTRAINT vinyl_release_owner FOREIGN KEY (Musical_Release_Id,Album_Id,User_Id) REFERENCES MUSICAL_RELEASES_TBL(Id,Album_Id,User_Id) ON DELETE RESTRICT,
 ADD CONSTRAINT vinyl_release_album CHECK (Musical_Release_Id IS NULL OR Album_Id IS NOT NULL);
CREATE TABLE AI_SAVE_KEYS_TBL (
 User_Id INT NOT NULL, Request_Key CHAR(64) NOT NULL, Payload_Hash CHAR(64) NOT NULL,
 Vinyl_Id INT NULL, Completed TINYINT NOT NULL DEFAULT 0, Created_At TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY (User_Id,Request_Key),
 FOREIGN KEY (User_Id) REFERENCES Users_TBL(id) ON DELETE RESTRICT,
 FOREIGN KEY (Vinyl_Id) REFERENCES VINYLS_TBL(Id) ON DELETE SET NULL
) ENGINE=InnoDB;
