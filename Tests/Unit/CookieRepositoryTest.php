<?php

declare(strict_types=1);

namespace Tests\Unit;

use Codeception\Test\Unit;

/**
 * Runs against an in-memory SQLite, not the site's MySQL: no server, no
 * credentials, no network, gone when the process exits. The table mirrors
 * db_schemas/705_auth_hardening/alter_cookies_hash_expiry.sql.
 */
class CookieRepositoryTest extends Unit
{
    private const DAY = 86400;

    private \PDO $pdo;
    private \DateTimeImmutable $t0;

    protected function _before(): void
    {
        $this->pdo = new \PDO('sqlite::memory:');
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec(
            "CREATE TABLE cookies (
                cookie_id INTEGER PRIMARY KEY AUTOINCREMENT,
                cookie TEXT NOT NULL UNIQUE,
                user_id INTEGER NOT NULL,
                expires_at TEXT NOT NULL
            )"
        );
        $this->t0 = new \DateTimeImmutable('2026-10-12 12:00:00');
    }

    private function at(int $seconds_after_t0): \Database\CookieRepository
    {
        return new \Database\CookieRepository($this->pdo, $this->t0->modify("+{$seconds_after_t0} seconds"));
    }

    private function rowCount(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM cookies");
        $count = $stmt === false ? false : $stmt->fetchColumn();
        if (!is_int($count)) {
            $this->fail("COUNT query failed");
        }
        return $count;
    }

    public function testIssuedCookieIsFound(): void
    {
        $repo = $this->at(0);
        $repo->issue(7, 'hash-a', 30 * self::DAY);
        $this->assertSame(7, $repo->findUserId('hash-a'));
    }

    public function testUnknownHashIsNobody(): void
    {
        $repo = $this->at(0);
        $repo->issue(7, 'hash-a', 30 * self::DAY);
        $this->assertSame(0, $repo->findUserId('hash-zzz'));
    }

    public function testCookieStopsWorkingWhenItExpires(): void
    {
        $this->at(0)->issue(7, 'hash-a', 30 * self::DAY);
        $this->assertSame(7, $this->at(30 * self::DAY - 1)->findUserId('hash-a'));
        $this->assertSame(0, $this->at(30 * self::DAY)->findUserId('hash-a'), 'at expiry');
        $this->assertSame(0, $this->at(31 * self::DAY)->findUserId('hash-a'), 'after');
    }

    public function testIssuingPurgesExpiredRows(): void
    {
        $this->at(0)->issue(7, 'old', self::DAY);
        $this->at(0)->issue(7, 'fresh', 30 * self::DAY);
        $this->at(2 * self::DAY)->issue(8, 'newest', 30 * self::DAY);
        $this->assertSame(2, $this->rowCount(), 'old is gone, fresh and newest remain');
    }

    public function testRevokeRemovesOneCookie(): void
    {
        $repo = $this->at(0);
        $repo->issue(7, 'hash-a', self::DAY);
        $repo->issue(7, 'hash-b', self::DAY);
        $repo->revoke('hash-a');
        $this->assertSame(0, $repo->findUserId('hash-a'));
        $this->assertSame(7, $repo->findUserId('hash-b'));
    }

    public function testRevokeAllKeepsTheCookieInHand(): void
    {
        $repo = $this->at(0);
        $repo->issue(7, 'phone', self::DAY);
        $repo->issue(7, 'laptop', self::DAY);
        $repo->issue(7, 'tablet', self::DAY);
        $repo->issue(9, 'someone-else', self::DAY);

        $this->assertSame(2, $repo->revokeAllForUser(7, 'laptop'));
        $this->assertSame(7, $repo->findUserId('laptop'), 'current browser stays in');
        $this->assertSame(0, $repo->findUserId('phone'));
        $this->assertSame(9, $repo->findUserId('someone-else'), 'other users untouched');
    }

    public function testRevokeAllWithoutAKeepSignsOutEverywhere(): void
    {
        $repo = $this->at(0);
        $repo->issue(7, 'phone', self::DAY);
        $repo->issue(7, 'laptop', self::DAY);
        $this->assertSame(2, $repo->revokeAllForUser(7));
        $this->assertSame(0, $this->rowCount());
    }
}
