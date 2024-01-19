<?php
class SessionHandler{
    protected $cache;
    protected $cache_key;
    protected $dbase;

    public function __construct(\Config $config, \Database\Database $di_dbase) {

        $this->dbase = $di_dbase;
        session_name("quick");
        session_cache_limiter("none");
        if(stripos($_SERVER['HTTP_HOST'], (string) $config->domain_name) !== false){
            session_set_cookie_params(0, "/", $config->domain_name);
        }
        // Register this object as the session handler
        session_set_save_handler(array(&$this, "open"),
                array(&$this, "close"),
                array(&$this, "read"),
                array(&$this, "write"),
                array(&$this, "destroy"),
                array(&$this, "gc"));
    }
    # Gets key / value pair into cache
    public function getCache($key) {
        return ($this->cache) ? $this->cache->get($key) : false;
    }

    public function open($save_path, $session_name) {
        // Don't need to do anything. Just return TRUE.
        return true;
    }

    public function close() {
        return $this->gc();
    }

    public function read($session_id) {
        // Set empty result incase we get nothing back
        $session_id = substr($session_id, 0, 32);
        $data = '';

        if($this->cache && $session_id) {
            $this->cache_key = "SESSID_".$session_id;
            if(($cache = $this->getCache($this->cache_key)) !== false) {
                return $cache;
            }
        }

        $sql = "SELECT `session_data` FROM `sessions` WHERE `session_id` = ? AND `expires` >= NOW() LIMIT 1";
        $session_results = $this->dbase->fetchResults($sql, "s", $session_id);

        if($session_results->numRows()) {
            $session_results->next();
            $data = $session_results->data['session_data'];
        }
        unset($session_results);

        return $data;
    }

    public function write($session_id, $data, int $timeout_in_seconds) {
        $session_id = substr($session_id, 0, 32);

        $sql = "REPLACE `sessions` (`session_id`, `session_data`, `expires`) VALUES (?, ?, ?)";
        $this->dbase->executeSQL($sql, "sss", $session_id, $data, date("Y-m-d H:i:s", time()+$timeout_in_seconds));
        if($this->cache && $this->cache_key) {
            if(($this->cache->replace($this->cache_key, $data, $timeout_in_seconds)) == false) {
                $this->cache->set($this->cache_key, $data, $timeout_in_seconds);
            }
        }

        return true;
    }

    public function destroy($session_id) {
        $sql = "DELETE FROM `sessions` WHERE `session_id` = ?";
        $this->dbase->executeSQL($sql, "s", $session_id);
        if($this->cache && $this->cache_key) {
            $this->cache->delete($this->cache_key);
        }

        return true;
    }

    public function gc() {
        // Garbage Collection

        // Delete all records who have passed the life time
        $sql = "DELETE FROM `sessions` WHERE `expires` < NOW()";
        $this->dbase->executeSQL($sql);

        return true;
    }

    // ensure session data is written out before classes are destroyed
    function __destruct () {
        @session_write_close();
    }
}
