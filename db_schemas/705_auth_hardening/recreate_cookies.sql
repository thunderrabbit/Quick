-- mg #705. Run by hand in phpMyAdmin on Quick's DB (plasticaddy), at the same
-- time as the code that writes `expires_at` (Database\CookieRepository) is
-- deployed. Code without this table = nobody can log in. This table without
-- that code = every login fails on INSERT.
--
-- The old rows hold plaintext tokens and cannot be kept, and Rob is the only
-- user, so the table is dropped and rebuilt rather than altered. Effect: you
-- log in once more.
DROP TABLE IF EXISTS `cookies`;

CREATE TABLE `cookies` (
  `cookie_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  -- SHA-256 hex of the remember-me token. The plaintext lives only in the
  -- browser. Binary collation: hashes compare byte for byte.
  `cookie` CHAR(64) COLLATE utf8mb4_bin NOT NULL,
  `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  -- When this token stops being accepted. The browser's own expiry is a
  -- request from us, not a rule: a captured value would otherwise work forever.
  `expires_at` DATETIME NOT NULL,
  UNIQUE KEY `cookie` (`cookie`),
  KEY `user_id` (`user_id`),
  KEY `expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
