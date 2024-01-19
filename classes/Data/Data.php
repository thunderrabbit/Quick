<?php
namespace Data;

abstract class Data{

    protected $errors;
    protected $mla_request;      // Encapsulates superglobals e.g. $SESSION, $REQUEST, etc (misspelled in this comment to keep searches clean)
    protected $di_dbase;

    public function __construct(\Mlaphp\Request $mla_request, \Database\Database $dbase) {
        $this->mla_request = $mla_request;
        $this->di_dbase = $dbase;
    }

    abstract public function loadFromDatabase(int $id);
}
