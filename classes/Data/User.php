<?php
namespace Data;

class User extends \Data\Data {
  protected $userId;
  protected $name;
  protected $email;
  protected $password;
  public function __construct(\Mlaphp\Request $mla_request, \Database\Database $dbase) {
      $this->mla_request = $mla_request;
      $this->di_dbase = $dbase;
  }

  public function loadFromDatabase($id) {

    $query = sprintf("SELECT *
            FROM `users`
            WHERE `user_id` = %d",$id);
    $result_set = $this->di_dbase->fetchResults($query);
    $result_set->toArray();
    if($result_set->valid()) {
        $this->loadFromArray($result_set->current());
        return true;
    } else {
        return false;
    }
  }

  private function loadFromArray($record) {
    $this->userId = $record['user_id'];
    $this->name = $record['name'];
    $this->email = $record['email'];
    $this->password = $record['password'];
  }

  public function saveToDatabase() {
        try {
            $this->di_dbase->startTransaction();

            $parameters = array();
            $parameter_types = "";
            $parameter_types .= "i";    $parameters['user_id'] = $this->userId;
            $parameter_types .= "s";    $parameters['name'] = $this->name;
            $parameter_types .= "s";    $parameters['email'] = $this->email;

            $this->di_dbase->updateFromRecord("`users`", $parameter_types, $parameters, "`user_id` = " . $this->userId);
            $this->di_dbase->commitTransaction();
            return true;
        } catch (\Exception $e) {
            $this->errors[] = "error saving to database.";
            $this->di_dbase->rollbackTransaction();
        }
        return false;
  }
}
