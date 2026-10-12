<?php

/**
 * This file tries to simplify knowing if user is logged in.
 *
 * First check against cookie table to see if cookie is valid.
 * CREATE TABLE `plasticaddy`.`cookies` (`cookie_id` INT NOT NULL AUTO_INCREMENT , `cookie` TINYTEXT NOT NULL , `user_id` INT NOT NULL , PRIMARY KEY (`cookie_id`), UNIQUE `unique_cookie` (`cookie`(32))) ENGINE = InnoDB;
 *
 */

namespace Auth;

class IsLoggedIn
{
    private const COOKIE_DOMAIN = 'quick.robnugen.com';

    private bool $is_logged_in = false;

    private string $session_variable = 'login';   // must match the one in \UserAuthentication
    private string $cookie_name = 'quill';
    private \Database\Database $di_dbase;

    public function __construct(\Database\Database $dbase)
    {
        $this->di_dbase = $dbase;
    }

    /**
     * Transitional: the old single entry point, now the two halves in a row.
     */
    public function checkLogin(\Mlaphp\Request $mla_request): bool
    {
        $this->resumeFromCookie($mla_request);
        if ($this->is_logged_in) {
            return true;
        }
        $email = $mla_request->post['email'] ?? '';
        $password = $mla_request->post['pass'] ?? '';
        if (is_string($email) && $email !== '' && is_string($password) && $password !== '') {
            return $this->attemptPasswordLogin($email, $password);
        }
        return false;
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

        $this->is_logged_in = true;
    }

    /**
     * Password login. Called by /login/ on POST and nowhere else, so a stray
     * email/pass pair in some other form is just data, and a wrong password
     * can no longer log a user out of an unrelated page.
     */
    public function attemptPasswordLogin(string $email, string $password): bool
    {
        $user_id = $this->checkPHPHashedPassword($email, $password);
        if ($user_id <= 0) {
            return false;
        }

        $this->establishSession($user_id);
        return true;
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
        $this->is_logged_in = true;
    }

    private function setAutoLoginCookie(int $user_id): void
    {
        $cookie = \Utilities::randomString(32);

        $record['user_id'] = $user_id;
        $record['cookie'] = $cookie;

        $this->di_dbase->insertFromRecord("`cookies`", "is", $record);

        $cookie_options = CookieOptions::build(self::COOKIE_DOMAIN, time() + (30 * 24 * 60 * 60));
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
        $cookie_result = $this->di_dbase->fetchResults("SELECT `user_id` FROM `cookies`
            WHERE `cookie` = ? LIMIT 1", "s", $cookie);
        if ($cookie_result->numRows() > 0) {
            $result_as_array = $cookie_result->toArray();
            return $result_as_array[0]['user_id'];
        } else {
            return 0;
        }
    }
    public function isLoggedIn(): bool
    {
        return $this->is_logged_in;
    }

    public function logout(): void
    {
        $this->is_logged_in = false;
        $this->killCookie();
        session_destroy();
        session_start();
        session_regenerate_id();
    }

    private function killCookie(): void
    {
        $cookie_options = CookieOptions::build(self::COOKIE_DOMAIN, time() - 3600);
        setcookie($this->cookie_name, '', $cookie_options);
        $this->is_logged_in = false;
    }
}
