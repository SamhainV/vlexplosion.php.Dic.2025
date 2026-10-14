-- Isolated development only; follows 001, 002 and 003. No automatic production migration.
ALTER TABLE ENC_ALBUMS ADD imported_at DATETIME NULL;
ALTER TABLE ENC_EDITIONS ADD imported_at DATETIME NULL;
ALTER TABLE ENC_CREDITS ADD credit_key CHAR(64) CHARACTER SET ascii NULL UNIQUE;
ALTER TABLE ENC_COPIES ADD overrides_json LONGTEXT NULL CHECK (overrides_json IS NULL OR JSON_VALID(overrides_json));
