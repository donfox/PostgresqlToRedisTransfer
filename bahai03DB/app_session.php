<?php
// $Id: app_session.php,v 1.2 2006/04/20 05:47:18 bmartin Exp $


class db_error extends Exception {
    public $query;
    public $error_message;
    public $db_error_id;

    function __construct($query, $db_handle=null) {
        $this->query = $query;
        $this->error_message = pg_last_error();

        if ($db_handle) {
            $err_query = sprintf("SELECT log_db_error('%s', '%s');",
                    pg_escape_string($query),
                    pg_escape_string($this->error_message)
                    );

            $res = pg_query($db_handle, $err_query);
            $this->db_error_id = pg_fetch_result($res, 0);
        }
    }

};


//-----------------------------------------------------------------------
// Encapsulates the connection (and reconnection) to the database across
// multiple server requests, but held together by a php session.
//-----------------------------------------------------------------------
class app_session {

    private $previous_login_ts;

    private $is_super;
    private $app_user;    // would be null for superuser

    private $bahai_community;

    static private $db_handle;

    //------------------------------------------------------------------
    // Constructor to establish a NEW session
    //------------------------------------------------------------------
    function __construct($login, $password, $trace) {
        global $_SERVER;

        $encoded_pwd = MD5($password);

        $tmp_db_handle = self::db_connect();
        $query = sprintf("SELECT establish_session('%s','%s','%s');",
                pg_escape_string($login),
                $encoded_pwd,
                session_id() );

        $res = pg_query($tmp_db_handle, $query);

        if (!$res) {
            throw new db_error($query);
        }

        self::log_sql("\nSESSION " . session_id());

        self::$db_handle = $tmp_db_handle;  // no exception thrown: save
        
        $this->previous_login_ts = pg_fetch_result($res, 0);

        try {
            $res = pg_query('SELECT session_is_superuser();');
        }
        catch (Exception $e) {
            die ("error calling 'session_is_superuser'");
        }

        $is_super = (pg_fetch_result($res, 0) == 't');
        if (!$is_super) {
            $this->app_user = app_user::read_from_db($login);
            $this->bahai_community = $this->app_user->get_bahai_community();
        }

        if ($trace) {
            $_SESSION['trace'] = true;
        }

        $_SESSION['app_session'] = $this;
    }


    //------------------------------------------------------------------
    //
    //------------------------------------------------------------------
    static private function db_connect() {
        $pg_connect_file = 'pg_connect';
        $connect_str = file_get_contents($pg_connect_file);
        $tmp_db_handle = pg_connect($connect_str) 
                or die("Can't connect to database");
        return $tmp_db_handle ;
    }


    //------------------------------------------------------------------
    //  If the file exists, dump the text of all SQL queries to this file.
    //------------------------------------------------------------------
    static private function log_sql($query) {
        
        if (file_exists(SQL_LOG)) {
            $fd = fopen(SQL_LOG, 'a');
            fwrite($fd, $query . "\n");
            fclose($fd);
        } 
    }


    //------------------------------------------------------------------
    // A variation on the standard function.
    // Throws an exception for a database error.
    //------------------------------------------------------------------
    static public function pg_query($query) {

        if (!(strpos($_SERVER['REQUEST_URI'], 'login') or
                (isset($_SERVER['HTTP_REFERER']) and
                strpos($_SERVER['HTTP_REFERER'], 'login')))) {
            require_once('tracer.php');
            tracer::trace_sql($query);
        }

        self::log_sql($query);

        $res = pg_query(self::$db_handle, $query);
        if (!$res) {
            throw new db_error($query, self::$db_handle);
        }

        return $res;
    }


    //------------------------------------------------------------------
    public function is_superuser() {
        return ($this->app_user == null);
    }


    //------------------------------------------------------------------
    public function get_app_user() {
        return $this->app_user;
    }


    //------------------------------------------------------------------
    public function get_bahai_community() {
        return $this->bahai_community;
    }


    //------------------------------------------------------------------
    public function set_bahai_community(bahai_community $bahai_community) {
        if ($this->app_user) {
            throw("Can't change bahai community for application user");
        }
        $this->bahai_community = $bahai_community;
    }


    //------------------------------------------------------------------
    public function clear_bahai_community() {
        if ($this->app_user) {
            throw("Can't change bahai community for application user");
        }
        $this->bahai_community = NULL;
    }


    //------------------------------------------------------------------
    public function previous_login_ts() {
        return $this->previous_login_ts;
    }


    //------------------------------------------------------------------
    // Throws an exception with failure.
    //------------------------------------------------------------------
    public function validate_session() {
        $tmp_db_handle = self::db_connect();

        $query = "SELECT refresh_session('" . session_id() . "');";
        $res = pg_query($tmp_db_handle, $query);

        self::$db_handle = $tmp_db_handle; // no exception thrown: save
    }


    //------------------------------------------------------------------
    public function logoff() {
        $query = "SELECT end_session('" . session_id() . "');";
        $res = self::pg_query($query);
    }
};

?>
