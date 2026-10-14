-- Development-only additive schema. Apply once, after tests/schema.sql in a NEW
-- isolated database. Does not require migrations 001/002. Never run on production.
-- DDL commits implicitly: a failed rehearsal must be abandoned, not rolled back.
CREATE TABLE ENC_ARTISTS (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(255) NOT NULL,
 kind ENUM('unknown','person','group') NOT NULL DEFAULT 'unknown',
 musicbrainz_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NULL UNIQUE,
 discogs_id BIGINT UNSIGNED NULL UNIQUE,
 INDEX artist_name (name)
) ENGINE=InnoDB;

CREATE TABLE ENC_ALBUMS (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 title VARCHAR(255) NOT NULL,
 original_date VARCHAR(10) NULL,
 release_type VARCHAR(80) NULL,
 language VARCHAR(32) NULL,
 description TEXT NULL,
 musicbrainz_group_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NULL UNIQUE,
 discogs_master_id BIGINT UNSIGNED NULL UNIQUE,
 -- Legacy records are private drafts until explicitly reviewed for publication.
 draft_owner_id INT NULL,
 legacy_vinyl_id INT NULL UNIQUE,
 FOREIGN KEY (draft_owner_id) REFERENCES Users_TBL(id) ON DELETE RESTRICT,
 INDEX album_title (title), INDEX album_draft_owner (draft_owner_id),
 CHECK (legacy_vinyl_id IS NULL OR draft_owner_id IS NOT NULL)
) ENGINE=InnoDB;

CREATE TABLE ENC_ALBUM_ARTISTS (
 album_id BIGINT UNSIGNED NOT NULL,
 artist_id BIGINT UNSIGNED NOT NULL,
 position SMALLINT UNSIGNED NOT NULL,
 credited_as VARCHAR(255) NULL,
 role VARCHAR(80) NOT NULL DEFAULT 'artist',
 PRIMARY KEY (album_id, position),
 FOREIGN KEY (album_id) REFERENCES ENC_ALBUMS(id) ON DELETE RESTRICT,
 FOREIGN KEY (artist_id) REFERENCES ENC_ARTISTS(id) ON DELETE RESTRICT,
 INDEX artist_albums (artist_id, album_id)
) ENGINE=InnoDB;

CREATE TABLE ENC_TERMS (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 kind ENUM('genre','style') NOT NULL,
 name VARCHAR(120) NOT NULL,
 UNIQUE KEY term_name (kind,name)
) ENGINE=InnoDB;
CREATE TABLE ENC_ALBUM_TERMS (
 album_id BIGINT UNSIGNED NOT NULL,
 term_id BIGINT UNSIGNED NOT NULL,
 PRIMARY KEY (album_id,term_id),
 FOREIGN KEY (album_id) REFERENCES ENC_ALBUMS(id) ON DELETE RESTRICT,
 FOREIGN KEY (term_id) REFERENCES ENC_TERMS(id) ON DELETE RESTRICT,
 INDEX term_albums (term_id,album_id)
) ENGINE=InnoDB;

CREATE TABLE ENC_LABELS (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(255) NOT NULL,
 musicbrainz_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NULL UNIQUE,
 discogs_id BIGINT UNSIGNED NULL UNIQUE,
 INDEX label_name (name)
) ENGINE=InnoDB;

CREATE TABLE ENC_EDITIONS (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 album_id BIGINT UNSIGNED NOT NULL,
 country VARCHAR(80) NULL,
 manufacture_country VARCHAR(80) NULL,
 release_date VARCHAR(10) NULL,
 barcode VARCHAR(64) NULL,
 packaging VARCHAR(255) NULL,
 edition_type VARCHAR(120) NULL,
 remastered BOOLEAN NULL,
 pressing_plant VARCHAR(255) NULL,
 musicbrainz_release_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NULL UNIQUE,
 discogs_release_id BIGINT UNSIGNED NULL UNIQUE,
 UNIQUE KEY edition_album (id,album_id),
 FOREIGN KEY (album_id) REFERENCES ENC_ALBUMS(id) ON DELETE RESTRICT,
 INDEX edition_album_date (album_id,release_date), INDEX edition_barcode (barcode),
 CHECK (remastered IS NULL OR remastered IN (0,1))
) ENGINE=InnoDB;
CREATE TABLE ENC_EDITION_LABELS (
 edition_id BIGINT UNSIGNED NOT NULL,
 label_id BIGINT UNSIGNED NOT NULL,
 catalogue_number VARCHAR(120) NOT NULL DEFAULT '',
 PRIMARY KEY (edition_id,label_id,catalogue_number),
 FOREIGN KEY (edition_id) REFERENCES ENC_EDITIONS(id) ON DELETE RESTRICT,
 FOREIGN KEY (label_id) REFERENCES ENC_LABELS(id) ON DELETE RESTRICT,
 INDEX catalogue_search (catalogue_number), INDEX label_editions (label_id,edition_id)
) ENGINE=InnoDB;

CREATE TABLE ENC_MEDIA (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 edition_id BIGINT UNSIGNED NOT NULL,
 position SMALLINT UNSIGNED NOT NULL,
 format VARCHAR(80) NULL,
 rpm DECIMAL(6,3) NULL,
 diameter_inches DECIMAL(5,2) NULL,
 color VARCHAR(120) NULL,
 weight_grams SMALLINT UNSIGNED NULL,
 UNIQUE KEY medium_position (edition_id,position),
 FOREIGN KEY (edition_id) REFERENCES ENC_EDITIONS(id) ON DELETE RESTRICT,
 CHECK (position > 0), CHECK (rpm IS NULL OR rpm > 0),
 CHECK (diameter_inches IS NULL OR diameter_inches > 0)
) ENGINE=InnoDB;
CREATE TABLE ENC_RUNOUTS (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 medium_id BIGINT UNSIGNED NOT NULL,
 side VARCHAR(16) NULL,
 inscription TEXT NOT NULL,
 variant VARCHAR(120) NULL,
 FOREIGN KEY (medium_id) REFERENCES ENC_MEDIA(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE ENC_WORKS (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 title VARCHAR(255) NOT NULL,
 musicbrainz_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NULL UNIQUE,
 INDEX work_title (title)
) ENGINE=InnoDB;
CREATE TABLE ENC_RECORDINGS (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 title VARCHAR(255) NOT NULL,
 duration_ms INT UNSIGNED NULL,
 musicbrainz_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NULL UNIQUE,
 INDEX recording_title (title)
) ENGINE=InnoDB;
CREATE TABLE ENC_RECORDING_WORKS (
 recording_id BIGINT UNSIGNED NOT NULL,
 work_id BIGINT UNSIGNED NOT NULL,
 PRIMARY KEY (recording_id,work_id),
 FOREIGN KEY (recording_id) REFERENCES ENC_RECORDINGS(id) ON DELETE RESTRICT,
 FOREIGN KEY (work_id) REFERENCES ENC_WORKS(id) ON DELETE RESTRICT,
 INDEX work_recordings (work_id,recording_id)
) ENGINE=InnoDB;
CREATE TABLE ENC_TRACKS (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 medium_id BIGINT UNSIGNED NOT NULL,
 position SMALLINT UNSIGNED NOT NULL,
 printed_number VARCHAR(32) NULL,
 side VARCHAR(16) NULL,
 title VARCHAR(255) NOT NULL,
 duration_ms INT UNSIGNED NULL,
 recording_id BIGINT UNSIGNED NULL,
 UNIQUE KEY track_position (medium_id,position),
 FOREIGN KEY (medium_id) REFERENCES ENC_MEDIA(id) ON DELETE RESTRICT,
 FOREIGN KEY (recording_id) REFERENCES ENC_RECORDINGS(id) ON DELETE RESTRICT,
 CHECK (position > 0)
) ENGINE=InnoDB;

CREATE TABLE ENC_SOURCES (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 provider VARCHAR(32) NOT NULL,
 entity_type VARCHAR(32) NOT NULL,
 external_id VARCHAR(100) NOT NULL,
 source_url VARCHAR(1000) NULL,
 fetched_at DATETIME NOT NULL,
 expires_at DATETIME NULL,
 license_note VARCHAR(1000) NULL,
 UNIQUE KEY source_identity (provider,entity_type,external_id)
) ENGINE=InnoDB;

CREATE TABLE ENC_CREDITS (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 album_id BIGINT UNSIGNED NULL,
 edition_id BIGINT UNSIGNED NULL,
 work_id BIGINT UNSIGNED NULL,
 recording_id BIGINT UNSIGNED NULL,
 track_id BIGINT UNSIGNED NULL,
 artist_id BIGINT UNSIGNED NULL,
 -- Studios and organisations can be credited by name without inventing artists.
 organisation VARCHAR(255) NULL,
 role VARCHAR(120) NOT NULL,
 credited_as VARCHAR(255) NULL,
 source_id BIGINT UNSIGNED NULL,
 FOREIGN KEY (album_id) REFERENCES ENC_ALBUMS(id) ON DELETE RESTRICT,
 FOREIGN KEY (edition_id) REFERENCES ENC_EDITIONS(id) ON DELETE RESTRICT,
 FOREIGN KEY (work_id) REFERENCES ENC_WORKS(id) ON DELETE RESTRICT,
 FOREIGN KEY (recording_id) REFERENCES ENC_RECORDINGS(id) ON DELETE RESTRICT,
 FOREIGN KEY (track_id) REFERENCES ENC_TRACKS(id) ON DELETE RESTRICT,
 FOREIGN KEY (artist_id) REFERENCES ENC_ARTISTS(id) ON DELETE RESTRICT,
 FOREIGN KEY (source_id) REFERENCES ENC_SOURCES(id) ON DELETE RESTRICT,
 CHECK ((album_id IS NOT NULL)+(edition_id IS NOT NULL)+(work_id IS NOT NULL)+(recording_id IS NOT NULL)+(track_id IS NOT NULL)=1),
 CHECK ((artist_id IS NOT NULL)+(organisation IS NOT NULL)=1),
 INDEX participant_role (artist_id,role)
) ENGINE=InnoDB;

-- Field assertions are proposals/history. Imports must not UPDATE approved fields.
CREATE TABLE ENC_ASSERTIONS (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 album_id BIGINT UNSIGNED NULL,
 edition_id BIGINT UNSIGNED NULL,
 field_name VARCHAR(80) NOT NULL,
 value_json LONGTEXT NOT NULL CHECK (JSON_VALID(value_json)),
 source_id BIGINT UNSIGNED NULL,
 author_user_id INT NULL,
 status ENUM('proposed','accepted','rejected','conflict') NOT NULL DEFAULT 'proposed',
 manually_locked BOOLEAN NOT NULL DEFAULT 0,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (album_id) REFERENCES ENC_ALBUMS(id) ON DELETE RESTRICT,
 FOREIGN KEY (edition_id) REFERENCES ENC_EDITIONS(id) ON DELETE RESTRICT,
 FOREIGN KEY (source_id) REFERENCES ENC_SOURCES(id) ON DELETE RESTRICT,
 FOREIGN KEY (author_user_id) REFERENCES Users_TBL(id) ON DELETE RESTRICT,
 CHECK ((album_id IS NOT NULL)+(edition_id IS NOT NULL)=1),
 CHECK (source_id IS NOT NULL OR author_user_id IS NOT NULL),
 CHECK (manually_locked IN (0,1)),
 INDEX album_field_status (album_id,field_name,status),
 INDEX edition_field_status (edition_id,field_name,status)
) ENGINE=InnoDB;

-- VINYLS_TBL remains the canonical personal copy and keeps all legacy fields.
-- Only private extension rows cascade when that personal copy is deleted.
ALTER TABLE VINYLS_TBL ADD UNIQUE KEY enc_copy_owner (Id,User_Id);
CREATE TABLE ENC_COPIES (
 vinyl_id INT PRIMARY KEY,
 owner_id INT NOT NULL,
 album_id BIGINT UNSIGNED NOT NULL,
 edition_id BIGINT UNSIGNED NULL,
 sleeve_condition VARCHAR(80) NULL,
 purchase_price DECIMAL(12,2) NULL,
 currency CHAR(3) NULL,
 purchase_date DATE NULL,
 seller VARCHAR(255) NULL,
 location VARCHAR(255) NULL,
 notes TEXT NULL,
 rating TINYINT UNSIGNED NULL,
 added_at DATETIME NULL,
 -- Private original snapshot; never expose through public encyclopedia queries.
 legacy_snapshot LONGTEXT NULL CHECK (legacy_snapshot IS NULL OR JSON_VALID(legacy_snapshot)),
 FOREIGN KEY (vinyl_id,owner_id) REFERENCES VINYLS_TBL(Id,User_Id) ON DELETE CASCADE,
 FOREIGN KEY (album_id) REFERENCES ENC_ALBUMS(id) ON DELETE RESTRICT,
 FOREIGN KEY (edition_id,album_id) REFERENCES ENC_EDITIONS(id,album_id) ON DELETE RESTRICT,
 CHECK (purchase_price IS NULL OR purchase_price >= 0),
 CHECK (rating IS NULL OR rating BETWEEN 1 AND 5),
 CHECK ((purchase_price IS NULL AND currency IS NULL) OR (purchase_price IS NOT NULL AND currency IS NOT NULL)),
 INDEX owner_album (owner_id,album_id), INDEX owner_edition (owner_id,edition_id)
) ENGINE=InnoDB;
CREATE TABLE ENC_PHOTOS (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 vinyl_id INT NOT NULL,
 storage_key VARCHAR(255) NOT NULL UNIQUE,
 kind ENUM('cover','back','label','barcode','runout','other') NOT NULL,
 mime_type VARCHAR(32) NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (vinyl_id) REFERENCES ENC_COPIES(vinyl_id) ON DELETE CASCADE
) ENGINE=InnoDB;
