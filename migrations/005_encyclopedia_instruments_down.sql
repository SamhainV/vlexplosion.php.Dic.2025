-- Requires explicit approval and export of instrument values before reversal.
-- Dropping this column discards its values; never run automatically.
ALTER TABLE ENC_CREDITS DROP COLUMN instrument;
