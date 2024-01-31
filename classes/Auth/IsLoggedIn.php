<?php
/**
 * This file tries to simplify knowing if user is logged in.
 *
 * First check against cookie table to see if cookie is valid.
 * CREATE TABLE `plasticaddy`.`cookies` (`cookie_id` INT NOT NULL AUTO_INCREMENT , `cookie` TINYTEXT NOT NULL , `user_id` INT NOT NULL , PRIMARY KEY (`cookie_id`), UNIQUE `unique_cookie` (`cookie`(25))) ENGINE = InnoDB;
 *
 */
namespace Auth;


class IsLoggedIn
{
    private $di_mla_request;
    private $session_variable = 'login';   // must match the one in \UserAuthentication

    public function __construct(\Mlaphp\Request $mla_request)
    {
        $this->di_mla_request = $mla_request;
    }

    public function isLoggedIn(): bool
    {
        $session_reference = $this->di_mla_request->__get('session');
        return (!empty($session_reference[$this->session_variable]) && is_numeric($session_reference[$this->session_variable]['user_id']));
    }
}
