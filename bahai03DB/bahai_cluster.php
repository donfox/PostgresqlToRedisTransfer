<?php
// $Id: bahai_cluster.php,v 1.5 2006/04/20 15:38:44 bmartin Exp $

require_once('html_utils.php');

class bahai_cluster  extends user_root_class {

    protected $cluster_code;
    protected $cluster_name;

    //protected $atc_members;


    //----------------------------------------------------------------
    //
    //----------------------------------------------------------------
    static function mode_supported($verb) {
        return $_SESSION['app_session']->is_superuser();
    }


    //----------------------------------------------------------------
    static function get_select_items($criteria=null) {
        if (!$_SESSION['app_session']->is_superuser())
            return null;

        $query = 'SELECT cluster_code, cluster_name FROM bahai_cluster;';

        $res = app_session::pg_query($query);

        $items = array();
        while ($row = pg_fetch_array($res)) {
            $items[ $row['cluster_code'] ] = $row['cluster_name'];
        }

        return $items;
    }


    //----------------------------------------------------------------
    static function type_long_name() {
        return BAHAI . " Cluster";
    }


    //----------------------------------------------------------------
    function __toString() {
       return $this->cluster_name;
    }


    //----------------------------------------------------------------
    function get_key() {
        return $this->cluster_code;
    }


    //----------------------------------------------------------------
    // Can be constructed from either:
    //   Database row data  OR  Form data ($_POST).
    //----------------------------------------------------------------
    function __construct(array $array_data) {
        $this->_copy_properties($array_data);
    }


    //----------------------------------------------------------------
    function check_user_data() {

    }


    //----------------------------------------------------------------
    //  Instantiate and return a new object from the database 
    //  or NULL if it doesn't exist in the database.
    //----------------------------------------------------------------
    static function read_from_db($key) {
        $query = "SELECT * from bahai_cluster WHERE cluster_code = '$key';";
        $res = app_session::pg_query($query);
        $row = pg_fetch_array($res);
        
        $obj = $row ? new bahai_cluster($row) : null;

        return $obj;
    }


    //----------------------------------------------------------------
    //  Returns the key value.
    //----------------------------------------------------------------
    function insert_to_db() {

        $query = sprintf("SELECT insert_bahai_cluster('%s','%s');",
                pg_escape_string($this->cluster_code),
                pg_escape_string($this->cluster_name) );
        $res = app_session::pg_query($query);

        return $this->get_key();
    }


    //----------------------------------------------------------------
    //  Returns the key value.
    //----------------------------------------------------------------
    function update_in_db() {

        $query = sprintf("SELECT update_bahai_cluster('%s','%s');",
                pg_escape_string($this->cluster_code),
                pg_escape_string($this->cluster_name) );
        $res = app_session::pg_query($query);

        return $this->get_key();
    }


    //----------------------------------------------------------------
    //----------------------------------------------------------------
    static function delete_from_db($key) {
        $query = "SELECT delete_bahai_cluster('$key')";
        $res = app_session::pg_query($query);
    }


    //----------------------------------------------------------------
    static function read_select_options() {
        if (!$_SESSION['app_session']->is_superuser())
            return null;

        $query = 'SELECT cluster_code, cluster_name FROM bahai_cluster;';

        $res = app_session::pg_query($query);

        $items = array();
        while ($row = pg_fetch_array($res)) {
            $items[ $row['cluster_code'] ] = $row['cluster_name'];
        }

        return $items;
    }



    //----------------------------------------------------------------
    //----------------------------------------------------------------
    static function gen_selector() {
        if (!$_SESSION['app_session']->is_superuser())
            return;

        $select_options = self::read_select_options();

        $html = parent::gen_selector_html(__CLASS__, $select_options);

        return $html;
    }


    //----------------------------------------------------------------
    //   HTML form generator
    //
    // For a blank form, this method is invoked as a static method.
    // For a filled in form, it's invoked as an instance method.
    //----------------------------------------------------------------
    static function gen_display(request $request,
        user_root_class $obj=null) {

        $submit_label = ($request->mode == 'update') ?
                'Save changes' : 'Save new ' . self::type_long_name();

        $delete_button = '';
        if ($request->mode == 'update') {
            $delete_button .= "<button type='submit' " .
                'onclick="this.form.mode.value=\'delete\'; return true;">' .
                'Delete ' . self::type_long_name() . ' </button>';
        }

        $buttons_html = self::gen_buttons_html(__CLASS__, $request->mode);
    
        $fmt_str = <<<BAHAI_CLUSTER_HTML

<FORM name='%s_entry' id='%s_entry' method='POST'>

<input type='hidden' name='datatype' value='%s'/>
<input type='hidden' name='mode' value='{$request->mode}' />
<input type='hidden' name='key' value='%s' />

<TABLE>
<TR>
  <td class='left_label'>Cluster Code:</td>
  <td> <input type='text' name='cluster_code' value='%s' %s/> </td>
</TR>
<TR>
  <td class='left_label'>Cluster Name:</td>
  <td> <input type='text' name='cluster_name' value='%s'/> </td>
</TR>
</TABLE>

<p>
{$buttons_html}
</FORM>

BAHAI_CLUSTER_HTML;

        $html = sprintf($fmt_str,
            __CLASS__,
            __CLASS__,
            __CLASS__,
            $obj ? htmlspecialchars($obj->cluster_code, ENT_QUOTES) : '',
            $obj ? htmlspecialchars($obj->cluster_code, ENT_QUOTES) : '',
            $obj ? 'disabled' : '',
            $obj ? htmlspecialchars($obj->cluster_name, ENT_QUOTES) : '' );
    
        return $html;
    }

}
?>
