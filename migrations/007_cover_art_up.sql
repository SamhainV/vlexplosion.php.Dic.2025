-- Independent of musical credits. Requires the existing VINYLS_TBL schema.
CREATE TABLE IF NOT EXISTS CAA_IMAGES (
 scope enum('release','release-group') NOT NULL,
 mbid char(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 release_mbid char(36) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
 original_url varchar(1024) DEFAULT NULL,
 storage_key char(68) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
 sha256 char(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
 mime_type varchar(32) DEFAULT NULL,
 retrieved_at datetime DEFAULT NULL,
 checked_at datetime NOT NULL,
 status enum('ready','missing','error','waiting') NOT NULL,
 retry_at bigint unsigned NOT NULL DEFAULT 0,
 PRIMARY KEY(scope,mbid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS CAA_PREFERENCES (
 vinyl_id int NOT NULL PRIMARY KEY,
 prefer_auto tinyint unsigned NOT NULL DEFAULT 0,
 group_mbid char(36) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
 CONSTRAINT caa_pref_copy FOREIGN KEY(vinyl_id) REFERENCES VINYLS_TBL(Id) ON DELETE CASCADE,
 CONSTRAINT caa_preference_bool CHECK(prefer_auto IN (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS CAA_BATCHES (
 owner_id int NOT NULL PRIMARY KEY,
 state_json mediumtext NOT NULL,
 updated_at datetime NOT NULL DEFAULT current_timestamp(),
 CONSTRAINT caa_batch_owner FOREIGN KEY(owner_id) REFERENCES Users_TBL(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
