<?php

declare(strict_types=1);

namespace Database;

/**
 * Persistence for remember-me cookies (the `cookies` table).
 *
 * Trimmed from new-DH-user-site's: Quick has one user and no devices page,
 * so rows are not bound to a User-Agent and carry no IP or last_access.
 *
 * Rows hold the SHA-256 of the token, never the token: callers hash before
 * they get here (see Auth\IsLoggedIn). Every lookup honours `expires_at`, so
 * a token captured from a browser or a backup dies with the row's lifetime
 * instead of living as long as the browser says. Issuing a cookie also purges
 * expired rows, which keeps the table bounded without a cron entry.
 *
 * Timestamps are computed in PHP and the SQL kept portable so the Unit suite
 * can drive this on an in-memory SQLite.
 */
class CookieRepository
{
    private \DateTimeImmutable $now;

    public function __construct(
        private \PDO $di_pdo,
        ?\DateTimeImmutable $now = null,
    ) {
        $this->now = $now ?? new \DateTimeImmutable();
    }

    public function issue(int $user_id, string $cookie_hash, int $lifetime_seconds): void
    {
        $stmt = $this->di_pdo->prepare(
            "INSERT INTO `cookies` (`user_id`, `cookie`, `expires_at`) VALUES (?, ?, ?)"
        );
        $stmt->execute([
            $user_id,
            $cookie_hash,
            $this->format($this->now->modify("+{$lifetime_seconds} seconds")),
        ]);
        $this->purgeExpired();
    }

    /**
     * The user a presented cookie belongs to, or 0 when it is unknown or has
     * expired.
     */
    public function findUserId(string $cookie_hash): int
    {
        $stmt = $this->di_pdo->prepare(
            "SELECT `user_id` FROM `cookies` WHERE `cookie` = ? AND `expires_at` > ? LIMIT 1"
        );
        $stmt->execute([$cookie_hash, $this->format($this->now)]);
        $user_id = $stmt->fetchColumn();
        return $user_id === false ? 0 : (int) $user_id;
    }

    public function revoke(string $cookie_hash): void
    {
        $stmt = $this->di_pdo->prepare("DELETE FROM `cookies` WHERE `cookie` = ?");
        $stmt->execute([$cookie_hash]);
    }

    /**
     * Sign a user out everywhere. Pass the hash of the cookie in hand to keep
     * the current browser logged in ("everywhere else"). Returns the number of
     * sessions revoked.
     */
    public function revokeAllForUser(int $user_id, ?string $keep_cookie_hash = null): int
    {
        if ($keep_cookie_hash === null) {
            $stmt = $this->di_pdo->prepare("DELETE FROM `cookies` WHERE `user_id` = ?");
            $stmt->execute([$user_id]);
        } else {
            $stmt = $this->di_pdo->prepare("DELETE FROM `cookies` WHERE `user_id` = ? AND `cookie` <> ?");
            $stmt->execute([$user_id, $keep_cookie_hash]);
        }
        return $stmt->rowCount();
    }

    public function purgeExpired(): void
    {
        $stmt = $this->di_pdo->prepare("DELETE FROM `cookies` WHERE `expires_at` <= ?");
        $stmt->execute([$this->format($this->now)]);
    }

    private function format(\DateTimeImmutable $when): string
    {
        return $when->format('Y-m-d H:i:s');
    }
}
