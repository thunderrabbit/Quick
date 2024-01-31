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
    private string $session_variable = 'login';   // must match the one in \UserAuthentication
    private string $cookie_name = 'quill';
    private \Database\Database $di_dbase;

    public function __construct(\Mlaphp\Request $mla_request, \Database\Database $dbase)
    {
        $this->di_mla_request = $mla_request;
        $this->di_dbase = $dbase;
        $wow_user_id = "nothing.  This is good; there is no cookie in db yet";
        if(!empty($this->di_mla_request->cookie[$this->cookie_name]))
        {
            $wow_user_id = $this->getUserIdForCookieInDatabase($this->di_mla_request->cookie[$this->cookie_name]);
            if(empty($wow_user_id))
            {
                $this->killCookie();
            }
        }
        print_rob($wow_user_id, false);
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
        $session_reference = $this->di_mla_request->__get('session');
        return (!empty($session_reference[$this->session_variable]) && is_numeric($session_reference[$this->session_variable]['user_id']));
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
    }

}
