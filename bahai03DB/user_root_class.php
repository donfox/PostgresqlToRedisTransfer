<?php  // $Id$
/*
 *   CLASSES that are top level displays, that show up in pulldown menus.
 */

abstract class user_root_class extends auto_construct implements type_in_db {

    public $edit_errors_group_id;

    abstract function check_user_data();

    static function type_long_name() {
        die(__METHOD__ . " must be overridden.");
    }

    abstract function __toString();

    // Returns HTML for display (e.g., form).
    static function gen_display(request $request,
            user_root_class $obj=null) {
        die(__METHOD__ . " must be overridden.");
    }

    static function mode_supported($verb) {
        die(__METHOD__ . " must be overridden.");
    }

    static function get_select_items($criteria=null) {
        die(__METHOD__ . " must be overridden.");
    }
    // $criteria usage may differ by class


    static private $class_list =
        array ('bahai_cluster', 'bahai_community',
               'app_user', 'member', 'person', 'event');
     // 'person', 'atc_members'


    //------------------------------------------------------------------
    static function get_class_list() {
        return self::$class_list;
    }


    //------------------------------------------------------------------
    //  If there are multiple privilege levels for this type, 
    //  returns an array of labels for those levels suitable for a
    //  selection list (otherwise null if there are no levels).
    //------------------------------------------------------------------
    static function privilege_levels_labels() {
        return null;
    }


    //------------------------------------------------------------------
    //  Returns an SQL statement to query the database for items to be
    //  put in a select menu 
    //  (such as is displayed after SELECT <datatype> pulldown is chosen).
    //  Query returns a series of rows with pseudo-columns 'key' and 'label'.
    //
    //  Would be 'abstract', but PHP interpreter doesn't allow for static.
    //------------------------------------------------------------------
    static function format_query_for_select(
            $where_clause,
            $order_by_clause=null) {

        die("user_root_class::format_query_for_select must be overridden.\n");
    }


    //------------------------------------------------------------------
    static function required_js_files() {
        return array();
    }


    //------------------------------------------------------------------
    static function tabs_groups() {
        return array();
    }


    //------------------------------------------------------------------
    static function is_root_class($class_name) {
        return in_array($class_name, self::$class_list);
    }


    //------------------------------------------------------------------
    //  Returns a 'request' object.
    //------------------------------------------------------------------
    static function process_post_data() {
        $class_name = $_POST['datatype'];

        try {
            switch ($_POST['mode']) {
                case 'create':
                case 'update':
                    $obj = new $class_name($_POST);
                    return $obj->process_create_or_update($_POST['mode']);
    
                case 'delete':
                    return self::process_delete($class_name, $_POST['key']);
                
                default:
                    $fnx = array($class_name, 'process_' . $_POST['mode']);
                    return call_user_func($fnx);
            }
        }

        catch (db_error $e) {

?>
<SCRIPT type='text/javascript'>
window.location='db_error.php?db_error_id=<?= $e->db_error_id; ?>'
</SCRIPT>
<?php

            exit();
        }

    }


    //------------------------------------------------------------------
    //  Returns a 'request' object.
    //------------------------------------------------------------------
    private function process_create_or_update($mode) {
        $edit_errors_group = $this->check_user_data();
        if ($edit_errors_group) {
            $this->edit_errors_group_id = $edit_errors_group->insert_to_db();
        }

        $key = ($mode == 'create') ? $this->insert_to_db() :
                                     $this->update_in_db();

        $fmt_str = ($mode == 'create') ? 'New %s saved' :
                'Changes to %s saved';

        $message = sprintf($fmt_str,
                call_user_func(array(get_class($this), 'type_long_name')) );

        $new_req = new request( array(
                'datatype' => get_class($this),
                'mode' => 'update',
                'key' => $key,
                'message' => $message
                ));

        return $new_req;
    }


    //------------------------------------------------------------------
    //  Returns a 'request' object.
    //------------------------------------------------------------------
    private static function process_delete($class_name, $key) {
        call_user_func(array($class_name, 'delete_from_db'), $key);

        $message = sprintf("%s deleted", 
                call_user_func(array($class_name, 'type_long_name')) );

        $new_req = new request(array('message' => $message));

        return $new_req;
    }


    //------------------------------------------------------------------
    //
    //------------------------------------------------------------------
    function get_edit_errors_group() {
        return $this->edit_errors_group_id ?
                edit_errors_group::read_from_db($this->edit_errors_group_id) :
                null;
    }


    //------------------------------------------------------------------
    static function gen_selector_html($data_type, $select_options) {
        $long_name = call_user_func(array($data_type, 'type_long_name'));

        $options_html = html_utils::format_options($select_options);

        $html = <<<SELECTOR_HTML

<FORM name='{$data_type}_select'>
  <input name='datatype' value='{$data_type}' type='hidden'/>
  <input name='mode' value='update' type='hidden'/>
  <SELECT name='key' onchange="go_to('{$data_type}', this.value)" >
  <OPTION value=''>{$long_name}</OPTION>
  {$options_html}
  </SELECT>
</FORM>
<SCRIPT type='text/javascript'>

</SCRIPT>

SELECTOR_HTML;

        return $html;

    }


    //--------------------------------------------------------------
    static function gen_buttons_html($data_type, $mode, $read_only=null) {

        $session_user = $_SESSION['app_session']->get_app_user();
        if (is_null($read_only)) {
            $read_only = ($session_user and
                    ($session_user->get_privilege($data_type) < 2));
        }

        if ($read_only)
            return "(Read-only access)";


        $name = call_user_func(array($data_type, 'type_long_name'));
        $html = sprintf("<BUTTON type='submit'>Save %s%s</BUTTON>",
                $name, ($mode == 'update' ? ' Changes' : '') );
        if ($mode == 'update') {
            $js = "this.form.mode.value = 'delete'; return true;";
            $html .= '&nbsp;&nbsp;';
            $html .= "<BUTTON type='submit' onclick=\"javascript:$js\">" .
                     "Delete $name</BUTTON>\n";
        }
        return $html;
    }

}
