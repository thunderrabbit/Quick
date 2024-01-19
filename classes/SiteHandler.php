<?php

class SiteHandler {

    private $di_dbase;

    public function __construct(\Config $config, \Database\Database $dbase) {
        /** START - Database Driven Session Handler **/
        $session_handler = new \SessionHandler($config, $dbase);    // without this, we cannot log in, but we do not actually use the reference to it.
        /** END - Database Driven Session Handler **/
    }
}
