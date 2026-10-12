-- mg #705. Run by hand in phpMyAdmin on Quick's DB, at the same time as the
-- code that writes `expires_at` (Database\CookieRepository) is deployed.
-- Code without this column = nobody can log in. This column without that
-- code = every login fails on INSERT.
--
-- Check `SHOW CREATE TABLE cookies` first: the index name below is taken from
-- the docblock IsLoggedIn.php used to carry, not from the live table.
--
-- Rows hold plaintext tokens (pre-#705) or hashes with no expiry. Neither can
-- be kept: empty the table. Effect: you log in once more.
DELETE FROM `cookies`;

ALTER TABLE `cookies`
  -- SHA-256 hex of the remember-me token. The plaintext lives only in the
  -- browser. Binary collation: hashes compare byte for byte.
  MODIFY `cookie` CHAR(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  -- When this token stops being accepted. The browser's own expiry is a
  -- request from us, not a rule: a captured value would otherwise work forever.
  ADD `expires_at` DATETIME NOT NULL,
  DROP INDEX `unique_cookie`,
  ADD UNIQUE KEY `cookie` (`cookie`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `expires_at` (`expires_at`);
