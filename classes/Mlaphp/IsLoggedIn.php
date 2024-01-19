<?php
/**
 * This file is NOT part of "Modernizing Legacy Applications in PHP".
 *
 */
namespace Mlaphp;


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
