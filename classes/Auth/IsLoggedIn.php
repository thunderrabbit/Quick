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
    private $di_mla_request;

    private bool $is_logged_in = false;

    private string $session_variable = 'login';   // must match the one in \UserAuthentication
    private string $cookie_name = 'quill';
    private \Database\Database $di_dbase;

    public function __construct(\Database\Database $dbase)
    {
        $this->di_dbase = $dbase;
    }

    public function checkLogin(\Mlaphp\Request $mla_request): bool
    {
        $this->di_mla_request = $mla_request;
        $wow_user_id = "nothing.  This is good; there is no cookie in db yet";
        if(!empty($this->di_mla_request->cookie[$this->cookie_name]))
        {
            $wow_user_id = $this->getUserIdForCookieInDatabase($this->di_mla_request->cookie[$this->cookie_name]);
            if(empty($wow_user_id))
            {
                $this->killCookie();
                return false;
            } else {
                $this->is_logged_in = true;
                return true;
            }
        } elseif(!empty($this->di_mla_request->post['email']) && !empty($this->di_mla_request->post['pass'])) {
            $wow_user_id = $this->checkPHPHashedPassword($this->di_mla_request->post['email'], $this->di_mla_request->post['pass']);
            if(empty($wow_user_id))
            {
                // don't killCookie here because an attacker could kill my login by sending a post with bad password
                return false;
            } else {
                $this->setAutoLoginCookie($wow_user_id);
                $this->is_logged_in = true;
                return true;
            }


        } else {
            print_rob("no cookie, no post", false);
        }
        print_rob($wow_user_id, false);
        return false;
    }

    private function setAutoLoginCookie(int $user_id):void
    {
        $cookie = \Utilities::randomString(32);

        $record['user_id'] = $user_id;
        $record['cookie'] = $cookie;

        $this->di_dbase->insertFromRecord("`cookies`", "is", $record);

        $cookie_options = array(
            'expires' => time() + (30 * 24 * 60 * 60),
            'path' => '/',
            'domain' => "quill.plasticaddy.com",
            'samesite' => 'Strict' // None || Lax  || Strict
        );
        setcookie($this->cookie_name, $cookie, $cookie_options);
        print_rob($cookie_options, false);
        print_rob("WE JUST wrote $this->cookie_name to: " . $cookie, false);
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


    private function getUserIdForCookieInDatabase($cookie)
    {
        $cookie_result = $this->di_dbase->fetchResults("SELECT `user_id` FROM `cookies`
            WHERE `cookie` = ? LIMIT 1", "s", $cookie);
        if($cookie_result->numRows() > 0)
        {
            $result_as_array = $cookie_result->toArray();
            $user_id_and_hash_array = array_shift($result_as_array);
            return $user_id_and_hash_array;
        }
        else
        {
            return false;
        }
    }
    public function isLoggedIn(): bool
    {
        return $this->is_logged_in;
    }

    private function killCookie(): void
    {
        $cookie_options = array (
          'expires' => time()-3600,
          'path' => '/',
          'domain' => "quill.plasticaddy.com",
          'samesite' => 'Strict' // None || Lax  || Strict
        );
        setcookie($this->cookie_name, '', $cookie_options);
        $this->is_logged_in = false;
    }

}
