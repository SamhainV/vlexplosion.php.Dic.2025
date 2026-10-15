-- Apply only after 005. Additive; no existing rows or 005 definitions modified.
ALTER TABLE ENC_CREDITS
 ADD COLUMN relation_key CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
 ADD COLUMN relation_json JSON NULL,
 ADD COLUMN context_edition_id BIGINT UNSIGNED NULL,
 ADD COLUMN sync_active TINYINT(1) NOT NULL DEFAULT 1,
 ADD COLUMN seen_at DATETIME NULL,
 ADD UNIQUE KEY credit_relation_identity (relation_key),
 ADD KEY credit_context (context_edition_id,sync_active),
 ADD CONSTRAINT credit_context_fk FOREIGN KEY (context_edition_id) REFERENCES ENC_EDITIONS(id);
CREATE TABLE ENC_CREDIT_JOBS (
 id CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY,
 owner_id INT NOT NULL,
 vinyl_id INT NOT NULL,
 edition_id BIGINT UNSIGNED NOT NULL,
 status VARCHAR(24) NOT NULL,
 state_json JSON NOT NULL,
 lease_token CHAR(32) NULL,
 lease_until DATETIME NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 KEY credit_jobs_owner (owner_id,vinyl_id),
 CONSTRAINT credit_job_copy FOREIGN KEY (vinyl_id,owner_id) REFERENCES VINYLS_TBL(Id,User_Id) ON DELETE CASCADE,
 CONSTRAINT credit_job_edition FOREIGN KEY (edition_id) REFERENCES ENC_EDITIONS(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
