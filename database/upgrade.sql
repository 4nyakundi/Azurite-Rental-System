-- Existing installations: back up first. No rows are deleted.
ALTER TABLE users MODIFY password_md5 VARCHAR(255) NOT NULL;
-- The legacy column name is retained for compatibility. New values are PHP password hashes.
-- Existing MD5 values are upgraded after a successful sign-in.
