<?php
namespace Database;

class Database implements DbInterface {

    private $charEncoding = "UTF8";
    private $report = false;
    private $errorReport = true;
    private $lastQuery = "";
    private $fields;
    private $lastQueryHash = null;
    private $dbObj;
    private $host;
    private $username;
    private $passwd;
    private $dbname;
    private $tz_offset = false;
    private $persistant;
    private $affected_rows;
    private $query_start_time;

    public function __construct($host, $username, $passwd, $dbname = '', $charEncoding = 'UTF8', $errorReport = false, $report = false) {
        $this->host = $host;
        $this->username = $username;
        $this->passwd = $passwd;
        $this->dbname = $dbname;
        $this->report = $report;
        $this->charEncoding = $charEncoding;
        if ($this->dbname && strtolower(substr($this->dbname, 0, 2)) == "p:") {
            $this->persistant = true;
        } else {
            $this->persistant = false;
        }
    }

// end __construct

    public function connect() {
//If we're already connected return true;
        if (!is_object($this->dbObj) || !$this->dbObj->ping()) {

            $this->dbObj = new \mysqli($this->host, $this->username, $this->passwd, $this->dbname);

            if (!is_object($this->dbObj) || $this->dbObj->connect_error) {
                sleep(1);
                $this->dbObj = new \mysqli($this->host, $this->username, $this->passwd, $this->dbname);
                if (!is_object($this->dbObj) || $this->dbObj->connect_error) {
                    throw new \Database\EDatabaseException("Could not connect to server after trying with 1s sleep (" . $this->dbObj->errno . ") " . $this->dbObj->error);
                } else {
                    $this->setEncoding($this->charEncoding);
                }
            } else {
                $this->setEncoding($this->charEncoding);
            }
        }
        $this->setTimezone();
        return true;
    }

    public function setTimezone(){
        $now = new \DateTime();
        $mins = $now->getOffset() / 60;

        $sgn = ($mins < 0 ? -1 : 1);
        $mins = abs($mins);
        $hrs = floor($mins / 60);
        $mins -= $hrs * 60;
        $offset = sprintf('%+d:%02d', $hrs*$sgn, $mins);

        if($this->tz_offset === false || $this->tz_offset != $offset){
            $this->tz_offset = $offset;
            $this->executeSql("SET time_zone='$offset';");
        }
    }

    public function setEncoding($charEncoding) {
        $this->charEncoding = $charEncoding;
        $this->dbObj->set_charset($this->charEncoding);
    }

    public function getEncoding() {
        return $this->charEncoding;
    }

    public function startTransaction() {
        $this->connect();
        $this->dbObj->query("SET autocommit=0");
        $this->dbObj->query("START TRANSACTION");
    }

    public function commitTransaction() {
        $this->dbObj->query("COMMIT;");
        $this->dbObj->query("SET autocommit=1;");
    }

    public function rollbackTransaction() {
        $this->dbObj->query("ROLLBACK;");
        $this->dbObj->query("SET autocommit=1;");
    }

    public function prepare($sql) {
        $this->connect(); //Connect if we aren't already

        if (!$stmt = $this->dbObj->prepare($sql))
            throw new \Database\EDatabaseException($this->dbObj->error);
        return $stmt;
    }

    private function loadFieldInfo($resultSet) {
        $metadata = $resultSet->result_metadata();
        $fields = $metadata->fetch_fields();
        $metadata->close();
        return $fields;
    }

    /*
     * Executes an UPDATE, INSERT or DELETE statement on the database.
     * Returns the insert_id or affected_rows, where relevant, or null.
     */

    private function executeSimpleSQL($sql) {
        $this->debugStart($sql);

        $this->connect(); //Connect if we aren't already

        $this->dbObj->query($sql);

        $result = ($this->dbObj->insert_id > 0) ? $this->dbObj->insert_id : NULL;
        $this->affected_rows = $this->dbObj->affected_rows;

        $this->debugResult();
        return $result;
    }

    /*
     * Executes an UPDATE, INSERT or DELETE statement on the database.
     * Returns the insert_id or affected_rows, where relevant, or null.
     */

    private function executePreparedSQL($sql, $paramtypes, $parameters) {
        $bindParam = [];
        $this->debugStart($sql);

        //Connect attempt is made in the prepare function.
        $stmt = $this->prepare($sql);

        $bindParam[] = $paramtypes;

        $param_arr = array_merge($bindParam, $parameters);
        if (!call_user_func_array(array($stmt, 'bind_param'), $this->refValues($param_arr))) {
            throw new \Database\EDatabaseException("Bind parameters failed");
        }

        $stmt->execute();
        if ($this->dbObj->errno != 0) {
            if ($this->dbObj->errno == ERROR_DUPLICATE_KEY) {
                throw new \Database\EDuplicateKey($this->dbObj->error);
            } else {
                throw new \Database\EDatabaseException($this->dbObj->error);
            }
        }
        $stmt->store_result();

        $result = ($stmt->insert_id > 0) ? $stmt->insert_id : NULL;
        $this->affected_rows = $stmt->affected_rows;

        $stmt->free_result();
        $stmt->close();
        unset($stmt);
        $this->debugResult();
        return $result;
    }

    public function executeSQL($sql, $paramtypes = null, $var1 = null) {
        $this->affected_rows = NULL;
        if (isset($paramtypes)) {
            $parameters = array();
            $paramcount = func_num_args();
            for ($i = 2; $i < $paramcount; $i++) {
                $tmpVar = func_get_arg($i);
                if (is_array($tmpVar)) {
                    foreach ($tmpVar as $var) {
                        $parameters[] = $var;
                    }
                } else {
                    $parameters[] = $tmpVar;
                }
            }

            return $this->executePreparedSQL($sql, $paramtypes, $parameters);
        } else {
            return $this->executeSimpleSQL($sql);
        }
    }

    public function affectedRows() {
        return $this->affected_rows;
    }

    public function fetchMultiResults($sql) {
        $this->debugStart($sql);

//Connect if we aren't already
        $this->connect();

        $results = array();
        if ($this->dbObj->multi_query($sql)) {
            do {
                $result = $this->dbObj->store_result();
                if ($result) {
                    $results[] = new ResultSetObjectResult($result);
                }
            } while ($this->dbObj->next_result());
        }

        $this->debugResult();
        return $results;
    }

    private function fetchSimpleResults($sql) {
        $this->debugStart($sql);

        $this->connect(); //Connect if we aren't already

        $result = $this->dbObj->query($sql);

        $this->debugResult();

        return new ResultSetObjectResult($result);
    }

    private function fetchPreparedResults($sql, $paramtypes, $params) {
        $bindParam = [];
        $this->debugStart($sql);

        //Connect attempt is made in the prepare function.
        $stmt = $this->prepare($sql);

        if (!empty($paramtypes) && !empty($params)) {
            $bindParam[] = $paramtypes;
            $param_arr = array_merge($bindParam, $params);
            if (!call_user_func_array(array($stmt, 'bind_param'), $this->refValues($param_arr))) {
                throw new \Database\EDatabaseException("Bind parameters failed");
            }
        }

        $stmt->execute();
        if ($this->dbObj->errno != 0) {
            throw new \Database\EDatabaseException($this->dbObj->error);
        }
        $stmt->store_result();

        $this->debugResult();

        return new ResultSetObjectStmt($stmt);
    }

    private function refValues($arr) {
        if (strnatcmp(phpversion(), '5.3') >= 0) { //Reference is required for PHP 5.3+
            $refs = array();
            foreach ($arr as $key => $value)
                $refs[$key] = &$arr[$key];
            return $refs;
        }
        return $arr;
    }

    public function fetchResults($sql, $paramtypes = null, $var1 = null) {
        $this->affected_rows = NULL; // Reset this here because we shouldn't have this set for a result.
        if (isset($paramtypes) && $paramtypes) {
            $params = array();
            $paramcount = func_num_args();
            for ($i = 2; $i < $paramcount; $i++) {
                $tmpVar = func_get_arg($i);
                if (is_array($tmpVar)) {
                    foreach ($tmpVar as $var) {
                        $params[] = $var;
                    }
                } else {
                    $params[] = $tmpVar;
                }
            }
            return $this->fetchPreparedResults($sql, $paramtypes, $params);
        } else {
            return $this->fetchSimpleResults($sql);
        }
    }

    public function updateFromRecord($tablename, $paramtypes, $record, $where) {
        return $this->updateRecord($tablename, false, $paramtypes, $record, $where);
    }

    public function updateIgnoreFromRecord($tablename, $paramtypes, $record, $where) {
        return $this->updateRecord($tablename, true, $paramtypes, $record, $where);
    }

    public function updateRecord($tablename, $ignore_duplicate_keys, $paramtypes, $record, $where) {
        $keys = NULL;

        if (strlen($paramtypes) != (is_countable($record) ? count($record) : 0)) {
            throw new \Database\EDatabaseException(__FUNCTION__ . ": Num elements in paramtype string != num of bind variables in record array. " . strlen($paramtypes) . " != " . (is_countable($record) ? count($record) : 0));
        }

        foreach ($record as $key => $val) {
            $keys .= "`" . $key . "`" . "=?,";
        }
        $keys = rtrim($keys, ", ");
        if ($ignore_duplicate_keys) {
            return $this->executeSQL("UPDATE IGNORE `" . trim($tablename, " `") . "` SET {$keys} WHERE {$where}", $paramtypes, $record);
        } else {
            return $this->executeSQL("UPDATE `" . trim($tablename, " `") . "` SET {$keys} WHERE {$where}", $paramtypes, $record);
        }
    }

    public function insertIgnoreFromRecord($tablename, $paramtypes, $record) {
        return $this->insertRecord($tablename, true, $paramtypes, $record);
    }

    public function insertFromRecord($tablename, $paramtypes, $record) {
        return $this->insertRecord($tablename, false, $paramtypes, $record);
    }

    public function insertRecord($tablename, $ignore_duplicate_keys, $paramtypes, $record) {
        $vars = NULL;
        $values = NULL;

        if (strlen($paramtypes) != (is_countable($record) ? count($record) : 0)) {
            throw new \Database\EDatabaseException(__FUNCTION__ . ": Num elements in paramtype string != num of bind variables in record array. " . strlen($paramtypes) . " != " . (is_countable($record) ? count($record) : 0));
        }

        foreach ($record as $key => $val) {
            $vars .= "`" . $key . "`, ";
            $values .=" ? , ";
        }
        $vars = rtrim($vars, ", ");
        $values = rtrim($values, ", ");

        if ($ignore_duplicate_keys) {
            return $this->executeSQL("INSERT IGNORE INTO `" . trim($tablename, " `") . "` ({$vars}) VALUES ({$values}) ", $paramtypes, $record);
        } else {
            return $this->executeSQL("INSERT INTO `" . trim($tablename, " `") . "` ({$vars}) VALUES ({$values}) ", $paramtypes, $record);
        }
    }

//This is a quick function for editing record type tables.  Unless the information that you're updating = the insert, you shouldn't use this.
    public function insertOnDuplicateUpdate($tablename, $paramtypes, $record, $update_paramtypes = null, $update_record = null) {
        $vars = NULL;
        $values = NULL;
        $keys = NULL;

        if (strlen($paramtypes) != (is_countable($record) ? count($record) : 0)) {
            throw new \Database\EDatabaseException(__FUNCTION__ . ": Num elements in paramtype string != num of bind variables in record array. " . strlen($paramtypes) . " != " . (is_countable($record) ? count($record) : 0));
        }

        if (isset($update_paramtypes) || isset($update_record)) {
            if (strlen($update_paramtypes) != (is_countable($update_record) ? count($update_record) : 0)) {
                throw new \Database\EDatabaseException(__FUNCTION__ . ": Num elements in paramtype string != num of bind variables in record array. " . strlen($update_paramtypes) . " != " . (is_countable($update_record) ? count($update_record) : 0));
            }
        }

        foreach ($record as $key => $val) {
            $vars .= "`" . $key . "`, ";
            $values .=" ? , ";
            $keys .= "`" . $key . "`" . "=?,";
        }
        $vars = rtrim($vars, ", ");
        $values = rtrim($values, ", ");
        $keys = rtrim($keys, ", ");

        if (isset($update_paramtypes) || isset($update_record)) {
            $keys = "";
            foreach ($update_record as $key => $val) {
                $keys .= "`" . $key . "`" . "=?,";
            }
            $vars = rtrim($vars, ", ");
            $keys = rtrim($keys, ", ");

            return $this->executeSQL("INSERT INTO `" . trim($tablename, " `") . "` ({$vars}) VALUES ({$values}) ON DUPLICATE KEY UPDATE {$keys}", $paramtypes . $update_paramtypes, $record, $update_record);
        } else {
            return $this->executeSQL("INSERT INTO `" . trim($tablename, " `") . "` ({$vars}) VALUES ({$values}) ON DUPLICATE KEY UPDATE {$keys}", $paramtypes . $paramtypes, $record, $record);
        }
    }

    public function close($override = false) {
        if (!$this->persistant || $override) {
            if (is_object($this->dbObj)) {
                $this->dbObj->close();
            }
        }
    }

    private function debugStart($sql) {
        if ($this->report) {
            $output = "-----<br/>";
            if ($sql) {
                $output .= "<p>QUERY = " . str_replace(array("\n", "\r"), " ", $sql) . "</p>";
            }
            echo $output;
            $this->lastQuery = $sql;
            $this->query_start_time = microtime(true);
        }
    }

    private function debugResult() {
        if ($this->report || ($this->errorReport && mysqli_errno($this->dbObj))) {
            $output = "";

            if (!empty($this->dbObj) && mysqli_errno($this->dbObj)) {
                $message = "MySQL Error Occured";
                $result = mysqli_errno($this->dbObj) . ": " . mysqli_error($this->dbObj);
//                trigger_error($message . " " . $result, E_USER_ERROR);
                throw new \Database\EDatabaseException($this->dbObj->error);
            } else {
                $message = "MySQL Query Executed Succesfully.";
                $result = $this->affected_rows . " Rows Affected";
                $output = "view logs for details";
                $runtime = round(microtime(true) - $this->query_start_time, 5);

                $output = "<b>" . $message . "</b>" .
                        "<span>" . $result . "</span>";
                if ($this->report) {
                    if($runtime > 0.005){
                        $output .= "<br />warning slow<p style='color:red'>" . $runtime . "</p>\n";
                    }else{
                        $output .= "<br /><p>" . $runtime . "</p>\n";
                    }
                }
                $output .= "<br/>-----<br/>";

                echo $output;
            }
        }
    }
}
