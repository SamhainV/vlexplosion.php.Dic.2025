-- Independent CAA preference only; 007 and all musical tables remain unchanged.
ALTER TABLE CAA_PREFERENCES
 ADD COLUMN IF NOT EXISTS visual_source_json MEDIUMTEXT NULL
 CHECK (visual_source_json IS NULL OR JSON_VALID(visual_source_json));
