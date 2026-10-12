<?php

/**
 * This file tries to simplify knowing if user is logged in.
 *
 * Remember-me cookies live in the `cookies` table through
 * \Database\CookieRepository; db_schemas/705_auth_hardening/ has the schema.
 *
 */

namespace Auth;

class IsLoggedIn
{
    private const COOKIE_DOMAIN = 'quick.robnugen.com';
    private const COOKIE_LIFETIME = 30 * 24 * 60 * 60;

    private int $who_is_logged_in = 0;

    private string $session_variable = 'login';   // must match the one in \UserAuthentication
    private string $cookie_name = 'quill';
    private \Database\Database $di_dbase;

    public function __construct(
        \Database\Database $dbase,
        private LoginThrottle $di_throttle,
        private \Database\CookieRepository $di_cookies,
    ) {
        $this->di_dbase = $dbase;
    }

    /**
     * Who is this request from? Runs on EVERY request (prepend.php). Its only
     * write is expiring a cookie the database no longer knows.
     * It never looks at credentials: that is attemptPasswordLogin()'s job,
     * and only the login page calls that.
     */
    public function resumeFromCookie(\Mlaphp\Request $mla_request): void
    {
        $cookie = $mla_request->cookie[$this->cookie_name] ?? '';
        if (!is_string($cookie) || $cookie === '') {
            return;
        }

        $found_user_id = $this->getUserIdForCookieInDatabase($cookie);
        if ($found_user_id <= 0) {
            $this->killCookie();
            return;
        }

        $this->who_is_logged_in = $found_user_id;
    }

    /**
     * Password login. Called by /login/ on POST and nowhere else, so a stray
     * email/pass pair in some other form is just data, and a wrong password
     * can no longer log a user out of an unrelated page.
     *
     * BadCredentials has no side effects; the caller shows one generic
     * message for it so a guesser cannot learn which logins exist.
     */
    public function attemptPasswordLogin(string $email, string $password): LoginResult
    {
        $ip_address = is_string($_SERVER['REMOTE_ADDR'] ?? null) ? $_SERVER['REMOTE_ADDR'] : '';
        if ($this->di_throttle->isThrottled($email, $ip_address)) {
            // Do not even look at the password: a throttled guess must cost
            // the attacker nothing in information and us nothing in bcrypt.
            return LoginResult::Throttled;
        }

        $user_id = $this->checkPHPHashedPassword($email, $password);
        if ($user_id <= 0) {
            $this->di_throttle->recordFailure($email, $ip_address);
            return LoginResult::BadCredentials;
        }

        $this->di_throttle->clearFailures($email);
        $this->establishSession($user_id);
        return LoginResult::Success;
    }

    /**
     * The one way to become logged in. Every authentication path must end by
     * calling this and nothing else, so no path can skip the fixation guard.
     */
    private function establishSession(int $user_id): void
    {
        // Fresh privilege level → fresh session id (fixation guard): a
        // pre-set session id must not survive the authentication boundary.
        session_regenerate_id(true);
        // regenerate_id keeps the session data, so a CSRF token minted (or
        // fixated) before login would survive it. Drop it; the next page
        // render mints a fresh one.
        unset($_SESSION[\Security\CSRFProtectaroo::FIELD]);
        $this->setAutoLoginCookie($user_id);
        $this->who_is_logged_in = $user_id;
    }

    private function setAutoLoginCookie(int $user_id): void
    {
        $cookie = \Utilities::randomString(32);

        // Store only the SHA-256 of the token: a leaked DB dump/backup must
        // not contain ready-to-use session tokens. The browser holds the
        // plaintext; lookups hash the presented value. The row carries its
        // own expires_at, so the lifetime is enforced here and not only by
        // the browser.
        $this->di_cookies->issue($user_id, hash('sha256', $cookie), self::COOKIE_LIFETIME);

        $cookie_options = CookieOptions::build(self::COOKIE_DOMAIN, time() + self::COOKIE_LIFETIME);
        setcookie($this->cookie_name, $cookie, $cookie_options);
    }



    private function getIDandPHPHashedPasswordForEmail($email)
    {
        // get password hash
        $user_id_and_hash_result = $this->di_dbase->fetchResults("SELECT `user_id`, `php_pw_hash` FROM `users`
            WHERE LOWER(`email`) = LOWER(?) LIMIT 1", "s", $email);
        if ($user_id_and_hash_result->numRows() > 0) {
            return $user_id_and_hash_result->toArray()[0];
        } else {
            return array();
        }
    }
    /**
     * Looks up hashed password for email, and checks it against the password provided
     * @param $email
     * @param $password
     * @return bool
     */
    private function checkPHPHashedPassword($email, $password): int
    {
        // get password hash
        $user_id_and_hash_array = $this->getIDandPHPHashedPasswordForEmail($email);
        if (!empty($user_id_and_hash_array)) {
            $hashed_password = $user_id_and_hash_array['php_pw_hash'];
            // check it
            if (password_verify($password, $hashed_password)) {
                // password is correct, so this user_id has logged in properly
                $user_id = $user_id_and_hash_array['user_id'];
                // return user_id
                return $user_id;
            }
        } else {
            return 0;
        }

        return 0;
    }


    private function getUserIdForCookieInDatabase(string $cookie): int
    {
        return $this->di_cookies->findUserId(hash('sha256', $cookie));
    }
    public function isLoggedIn(): bool
    {
        return $this->who_is_logged_in > 0;
    }

    public function logout(): void
    {
        // Nobody is logged in, so there is nothing to revoke and no session of
        // ours to tear down.
        if ($this->who_is_logged_in <= 0) {
            return;
        }

        // Revoke every session this user has, not only the browser that clicked
        // logout. Someone logging out because they think they were compromised
        // gets what they asked for, and a token captured from any of their
        // devices stops working now instead of at its expiry.
        $this->di_cookies->revokeAllForUser($this->who_is_logged_in);

        $this->who_is_logged_in = 0;
        $this->killCookie();
        session_destroy();
        session_start();
        session_regenerate_id();
    }

    private function killCookie(): void
    {
        $cookie_options = CookieOptions::build(self::COOKIE_DOMAIN, time() - 3600);
        setcookie($this->cookie_name, '', $cookie_options);
        $this->who_is_logged_in = 0;
    }
}
