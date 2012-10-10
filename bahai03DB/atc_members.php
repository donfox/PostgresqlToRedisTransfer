<?php
// $Id: atc_members.php,v 1.5 2006/04/20 15:38:44 bmartin Exp $

require_once('html_utils.php');

class atc_members {

    protected $cluster_code;
    protected $cluster_name;

    protected $members;


    //----------------------------------------------------------------
    static function tabs_group_name() {
        return null;
    }


    //----------------------------------------------------------------
    static function type_long_name() {
        return 'ATC Members';
    }


    //----------------------------------------------------------------
    //
    //----------------------------------------------------------------
    static public function pulldown_can_support_verb($verb) {
        if ($_SESSION['app_session']->is_superuser())
            return false;

        if ($verb != 'update')
            return false;

        $session_user = $_SESSION['app_session']->get_app_user();
        $priv = $session_user->get_privilege('atc_members');

        return ($priv >= 1);
    }


    //----------------------------------------------------------------
    // Can be constructed from either:
    //   Database row data  OR  Form data ($_POST).
    //----------------------------------------------------------------
    function __construct(array $array_data) {
        $this->cluster_code = $array_data['cluster_code'];
        $this->cluster_name = $array_data['cluster_name'];

        $this->members = array();

        if (array_key_exists('datatype', $array_data)) {
            $i=1;
            $fld = "atc_${i}_person_id";
            
            while (array_key_exists($fld, $array_data)) {
                $del_fld = "atc_${i}_delete";
                if (!(array_key_exists($del_fld, $array_data) &&
                        $array_data[$del_fld])) {
                    array_push($this->members, $array_data[$fld]);
                }
                ++$i;
                $fld = "atc_${i}_person_id";
            }
        }
    }


    //----------------------------------------------------------------
    //  Instantiate and return a new object from the database 
    //  or NULL if it doesn't exist in the database.
    //----------------------------------------------------------------
    static public function read_from_db($key) {
        $query = sprintf(
            "SELECT * FROM bahai_cluster WHERE cluster_code = '%s';",
            pg_escape_string($key) );
        $res = app_session::pg_query($query);
        $row = pg_fetch_assoc($res);
        if (!$row)
            return null;

        $obj = new atc_members($row);

        $obj->members = array();

        $query = sprintf("SELECT person_id from atc_member WHERE " .
                         "cluster_code = '%s';",
            pg_escape_string($key) );

        $res = app_session::pg_query($query);
        while ($row = pg_fetch_assoc($res)) {
            array_push($obj->members, $row['person_id']);
        }

        return $obj;
    }


    //----------------------------------------------------------------
    //
    //----------------------------------------------------------------
    static public function process_post_data() {
        $obj = new atc_members($_POST);

        try {
            $key = $obj->update_in_db();

            $new_req = new request( array(
                            'success' => "Successful Transaction",
                            'datatype' => 'atc_members',
                            'mode' => 'update',
                            'key' => $key ) );
        }

        catch (db_exception $e) {
             $new_req = new request($_POST);
             $new_req->error = $e->getMessage();
             if ($obj) {
                 $new_req->data_for_retry = $obj;
             }
        }

        return $new_req;
    }



    //----------------------------------------------------------------
    public function insert_to_db() {
        die("Shouldn't get here (insert_to_db).");
    }

    //----------------------------------------------------------------
    static public function delete_from_db($key) {
        die("Shouldn't get here (delete_from_db).");
    }


    //----------------------------------------------------------------
    //  Returns the key value.
    //----------------------------------------------------------------
    public function update_in_db() {
 
        if (count($this->members) > 0) {
            $query = sprintf(
                "SELECT cluster_update_atc_members(" .
                "'%s', CAST('{%s}' as integer[]));",
                pg_escape_string($this->cluster_code),
                implode(',', $this->members) );

            $res = app_session::pg_query($query);
        }

        return $this->cluster_code;
    }


    //----------------------------------------------------------------
    //
    //----------------------------------------------------------------
    static function gen_display(request $request, $obj) {
        if ($request->mode == 'list') {
            self::list_locations();
        }
        else {
            self::gen_entry_form($request, $obj);
        }
    }


    //----------------------------------------------------------------
    //   HTML form generator
    //
    // For a blank form, this method is invoked as a static method.
    // For a filled in form, it's invoked as an instance method.
    //----------------------------------------------------------------
    static function gen_entry_form(request $request, $obj) {

        $atc_member_html = '';

        $cap = person_popup::select_bit |
               person_popup::multi_bit |
               person_popup::member_bit;
        $per_sel = new person_popup($cap, 'add_atc_member');
        $per_sel->set_cluster($obj->cluster_code);
        $per_sel_html =
                $per_sel->format_button("OPEN ATC MEMBERS SELECTOR");


        $row_fmt_str = <<<ROW_HTML
<TR>
  <TD>%s</TD>
  <TD>
    <INPUT type='hidden' name='atc_%d_person_id' value=%d />
    <INPUT type='checkbox' name='atc_%d_delete' />
</TR>
ROW_HTML;

        $rows_html = '';
        $index = 1;

        if (count($obj->members) > 0) {
            $index=1;
            foreach($obj->members as $mem_id) {
                $mem = member::read_from_db($mem_id);
                $label = $mem->get_descriptor();
                $rows_html .= sprintf($row_fmt_str, $label,
                    $index, $mem->person_id, $index);
                ++$index;
            }
        }


        $fmt_str = <<<BAHAI_CLUSTER_HTML

<SCRIPT type='text/javascript'>
atc_member_index = {$index};
</SCRIPT>

<FORM name='atc_members' method='POST'>

<input type='hidden' name='datatype' value='atc_members'/>
<input type='hidden' name='mode' value='{$request->mode}' />
<input type='hidden' name='cluster_code' value='%s' />
<input type='hidden' name='cluster_name' value='%s' />

<TABLE>
<TR>
  <td class='left_label'>Cluster Code:</td> <td> %s </td>
</TR>
<TR>
  <td class='left_label'>Cluster Name:</td> <td> %s </td>
</TR>
</TABLE>

<br>
<br>

<TABLE id='atc_members_table'  border='1' cellpadding='7'>
<TR>
  <th> ATC Member </th>
  <th> Delete ? </th>
</TR>
{$rows_html}
</TABLE>

<p>
{$per_sel_html}

<p>
<INPUT type='submit' value='Save Changes'/>
</FORM>

BAHAI_CLUSTER_HTML;

        $html = sprintf($fmt_str,
            $obj ? htmlspecialchars($obj->cluster_code, ENT_QUOTES) : '',
            $obj ? htmlspecialchars($obj->cluster_name, ENT_QUOTES) : '',
            $obj ? htmlspecialchars($obj->cluster_code, ENT_QUOTES) : '',
            $obj ? htmlspecialchars($obj->cluster_name, ENT_QUOTES) : '');
    
        print $html;
    }

}
?>
