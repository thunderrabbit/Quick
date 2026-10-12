-- mg #705. Run by hand in phpMyAdmin on Quick's DB. Safe to run before or
-- after the code: Auth\LoginThrottle treats a missing table as "no throttle".
CREATE TABLE `login_attempts` (
  `login_attempt_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  -- One row per FAILED password login. Successes delete the login's rows.
  -- Lower-cased email as typed, not a user_id: guesses at logins that do not
  -- exist must count too, or the throttle itself reveals which logins exist.
  `username` VARCHAR(255) NOT NULL,
  `ip_address` VARBINARY(16) DEFAULT NULL,
  `attempted_at` DATETIME NOT NULL,
  KEY `username_attempted_at` (`username`, `attempted_at`),
  KEY `ip_attempted_at` (`ip_address`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
