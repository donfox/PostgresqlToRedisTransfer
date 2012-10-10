<?php

class person extends user_root_class {

    public $person_id;
    public $bahai_cmty_id;
    public $person_category;

    static public $categories = array(
        1 => 'member',   // note this should be handled from member input
        2 => 'guest',
        3 => 'seeker',
        4 => 'external'
        );

    public $last_name;
    public $first_name;

    public $primary_phone;
    public $primary_email;
    public $bahai_id_country;
    public $bahai_id;

    public $contact_address;
    public $contact_address_id;
    public $contact_address_status;


    //------------------------------------------------------------------------
    function __construct(array $array_data, $prefix=null) {

        $this->_copy_properties($array_data, $prefix);

        $bahai_cmty = $_SESSION['app_session']->get_bahai_community();
        $this->bahai_cmty_id = $bahai_cmty->get_key();

        if (array_key_exists('datatype', $array_data)) {   // from form input
            $this->handle_child_objects_from_form($array_data);
        }
    }


    //------------------------------------------------------------------
    function get_key() {
        return $this->person_id;
    }


    //------------------------------------------------------------------
    static function required_js_files() {
        return array('person.js', 'address.js', 'ajax.js', 'db_checker.js');
    }


    //---------------------------------------------------------------------
    static function privilege_levels_labels() {
        return array('none', 'read-only', 'modify');
    }


    //------------------------------------------------------------------------
    static function read_from_db($key) {
        $country_code =
          $_SESSION['app_session']->get_bahai_community()->get_country_code();

        $query = sprintf("SELECT * from person WHERE person_id = %d;", $key);
        $res = app_session::pg_query($query);
        $row = pg_fetch_assoc($res);

        $person = new person($row);
        if ($person->contact_address_id) {
            $person->contact_address =
                    address::dispatch_read_from_db($country_code,
                        $person->contact_address_id);
        }

        return $person;
    }


    //------------------------------------------------------------------------
    private function handle_child_objects_from_form(array $array_data) {

        if ($this->contact_address_status) {

            $cmty = $_SESSION['app_session']->get_bahai_community();
            $country_code = $cmty->get_country_code();

            $this->contact_address = address::dispatch_construct($country_code,
                    $array_data, 'contact_');

            if ($this->contact_address->is_blank()) {
                $this->contact_address = null;
                $this->contact_address_status =
                    ($this->contact_address_status == 'update') ? 
                        'delete' : '';
            }
        }
    }

    
    //------------------------------------------------------------------------
    function insert_to_db() {
    
        $bahai_cmty = $_SESSION['app_session']->get_bahai_community();

        $this->update_child_objects_in_db();

        $fmt_str = "SELECT insert_person(%d,CAST(%d as smallint),'%s','%s'," .
                "'%s','%s',%s,'%s','%s',%s);";
    
        $query = sprintf($fmt_str,
                $this->bahai_cmty_id,
                $this->person_category,
                pg_escape_string($this->last_name),
                pg_escape_string($this->first_name),
                pg_escape_string($this->primary_phone),
                pg_escape_string($this->primary_email),
                ($this->contact_address_id ? $this->contact_address_id
                        : 'NULL'),
                pg_escape_string($this->bahai_id_country),
                pg_escape_string($this->bahai_id),
                ($this->edit_errors_group_id ?  $this->edit_errors_group_id
                        : 'NULL')
                );
                    
         $res = app_session::pg_query($query);
         $this->person_id = pg_fetch_result($res, 0);

         return $this->person_id;
    }
    

    //------------------------------------------------------------------------
    function update_in_db() {

        $this->update_child_objects_in_db();

        $fmt_str = "SELECT update_person(%d,CAST(%d as smallint),'%s','%s'," .
                "'%s','%s',%s,'%s','%s',%s);";
    
        $query = sprintf($fmt_str,
                $this->person_id,
                $this->person_category,
                pg_escape_string($this->last_name),
                pg_escape_string($this->first_name),
                pg_escape_string($this->primary_phone),
                pg_escape_string($this->primary_email),
                ($this->contact_address_id ? $this->contact_address_id
                        : 'NULL'),
                pg_escape_string($this->bahai_id_country),
                pg_escape_string($this->bahai_id),
                ($this->edit_errors_group_id ?  $this->edit_errors_group_id
                        : 'NULL')
                );
                    
         $res = app_session::pg_query($query);
         //pg_fetch_result($res, 0);

         return $this->person_id;
    }


    //------------------------------------------------------------------------
    //
    //------------------------------------------------------------------------
    private function update_child_objects_in_db() {
    
        if ($this->contact_address_status == 'insert') {
            $this->contact_address_id = $this->contact_address->insert_to_db();
        }
        else if ($this->contact_address_status == 'update') {
            $this->contact_address->update_in_db();
        }
        else if ($this->contact_address_status == 'delete') {
            $this->contact_address_id = null;
        }
    }


    //------------------------------------------------------------------------
    static function delete_from_db($key) {
        $query = sprintf("SELECT delete_person(%d);", $key);
        $res = app_session::pg_query($query);
    }

    
    //------------------------------------------------------------------------
    function check_user_data() {
        $edit_errors_group = null;
        $edit_errors = array();
    
        // Actually this one should be caught by the javascript client check,
        // but I also put it here.
        if (empty($this->last_name) and empty($this->first_name)) {
            array_push($edit_errors,
                new edit_error('Both last name and first name are required.',
                               ''));
        }
    
        if (count($edit_errors) > 0) {
            $edit_errors_group = new edit_errors_group( array(
                'datatype' => 'member',
                'row_descriptor' => '' . $this,
                'edit_errors' => $edit_errors
                ) );
        }
    
        return $edit_errors_group;
    }


    //------------------------------------------------------------------------
    static function mode_supported($verb) {
         if ($_SESSION['app_session']->is_superuser())
             return false;
    
        $session_user = $_SESSION['app_session']->get_app_user();
        $priv_level = $session_user->get_privilege(__CLASS__);
    
        switch ($verb) {
            case 'create':
                return ($priv_level >= 2);
    
            case 'select':
            case 'update':
                return ($priv_level >= 1);
    
            default:
                return false;
        }
    }


    //------------------------------------------------------------------------
    static function tabs_group_name() {
        return '';
    }


    //------------------------------------------------------------------------
    static function read_label($person_id) {
        $query = sprintf("SELECT label FROM person_label " .
                 "WHERE person_id = %d;",
                 $person_id );
        $res = app_session::pg_query($query);
        $row = pg_fetch_row($res);
        return $row[0];
    }


    //------------------------------------------------------------------------
    static function read_person_category($person_id) {
        $query = sprintf("SELECT person_category FROM person " .
                 "WHERE person_id = %d;",
                 $person_id );
        $res = app_session::pg_query($query);
        $row = pg_fetch_row($res);
        return $row[0];
    }


    //------------------------------------------------------------------------
    function __toString() {
        return $this->last_name . ', ' . $this->first_name;
    }


    //------------------------------------------------------------------------
    static function type_long_name() {
        return 'Person';
    }


    //-----------------------------------------------------------------------
    //>>>  person::gen_display  <<<
    //
    //  This
    //-----------------------------------------------------------------------
    static function gen_display(request $request, user_root_class $obj=null) {
    
        if ($request->mode == 'create' or $request->mode == 'update') {
            return self::gen_entry_form($request, $obj);
        }
    
        else {
            die("person: Shouldn't get here (mode = '{$request->mode}').\n");
        }
    }
    

    //-----------------------------------------------------------------------
    //
    //-----------------------------------------------------------------------
    static function gen_entry_form(request $request,
                                   user_root_class $obj=null,
                                   $prefix=null,
                                   $popup_params=null) {
        $country_code =
          $_SESSION['app_session']->get_bahai_community()->get_country_code();

        $session_user = $_SESSION['app_session']->get_app_user();
        $priv = $session_user->get_privilege(__CLASS__);

        $address_html = address::dispatch_format_fields($country_code,
                $prefix . "{$prefix}contact_",
                $obj ? $obj->contact_address : null);

        $category_list = ($popup_params and $popup_params->category_list) ?
                $popup_params->category_list : array(2,3,4);

        $person_category_options = array();
        foreach (self::$categories as $num => $label) {
            if (in_array($num, $category_list)) {
                $person_category_options[$num] = $label;
            }
        }

        $person_category_options_html = 
            html_utils::format_options($person_category_options,
                ($obj ? $obj->person_category : null) );

        $buttons_html = $popup_params ?
            sprintf("<button type='button' onclick='%s'>" .
                    "Create %s</button>\n",
                    'javascript:return process_form_data(this.form);',
                    self::type_long_name() )
            :  self::gen_buttons_html(__CLASS__, $request->mode);


        $convert_button = '';
/*
        if ($request->mode == 'update') {
            $convert_button = <<<CONVERT_HTML
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<BUTTON type='submit' onclick='javascript:convert_to_member(this.form);'>
Convert to Member
</BUTTON>
CONVERT_HTML;
        }
*/
        $bahai_id_label = BAHAI . " ID";

        $fmt_str = <<<CREATE_HTML

<FORM name='{$prefix}person_entry' id='{$prefix}person_entry'
method='POST' onsubmit='javascript:return person_check(this);'>

<table><tbody>
  <tr>
    <td>
      <input type='hidden' name='datatype' value='person'/>
      <input type='hidden' name='mode' value='%s'/>
      <input type='hidden' name='key' value='%s'/>
      <input type='hidden' name='{$prefix}person_id' value='%s'/>
      <label class='field_header' for='{$prefix}last_name'>Last Name</label>
      <br>
      <input maxlength='30' size='20' value='%s'
       name='{$prefix}last_name' id='{$prefix}last_name' />
    </td>

    <td valign='bottom' style='font-size:xx-large'>&nbsp;,&nbsp;&nbsp;</td>

    <td>
      <label class='field_header'
       for='{$prefix}first_name'>First Name</label><br>
      <input maxlength='30' size='20' value='%s'
       name='{$prefix}first_name' id='{$prefix}first_name' />
    </td>
  
    <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>

    <td>
      <label class='field_header'
       for='{$prefix}person_category'>Category</label><br>
      <SELECT name='{$prefix}person_category' id='{$prefix}person_category'
       onchange='javascript:{$prefix}check_bahai_display();' >
        {$person_category_options_html}
      </SELECT>
    </td>

  </tr>

  <tr>
    <td>
      <label class='field_header'
       for='{$prefix}primary_phone'>Phone</label><br>
      <input maxlength='30' size='12' value='%s'
        name='{$prefix}primary_phone' id='{$prefix}primary_phone'/>
    </td>
    <td colspan='2'>
      <label class='field_header'
       for='{$prefix}primary_email'>Email</label><br>
      <input maxlength='30' size='20' value='%s'
        name='{$prefix}primary_email' id='{$prefix}primary_email' />
    </td>
  </tr>

  <tr>
    <td colspan='5'> $address_html </td>
  </tr>
  
  <tr>
    <td colspan='5'><DIV id='{$prefix}person_bahai' style='display:%s'>
      <br>
      <label class='field_header'
       for='{$prefix}bahai_id'>{$bahai_id_label}</label><br>
      <input id='{$prefix}bahai_id' name='{$prefix}bahai_id'
       type='text' value='%s'/>
    </DIV></td>
  </tr>

  <tr>
    <td colspan='5'>
        {$buttons_html}
        {$convert_button}
    </td>
  </tr>

</tbody></table>
</FORM>

<SCRIPT type='text/javascript'>

function {$prefix}check_bahai_display() {
    var sel = document.getElementById('{$prefix}person_category');
    var person_bahai_div = document.getElementById('{$prefix}person_bahai');
    person_bahai_div.style.display = (sel.value == 1) ? 'inline' : 'none';
}

{$prefix}check_bahai_display();

</SCRIPT>

CREATE_HTML;

        $html = sprintf($fmt_str, 
            $request->mode,
            ($obj ? $obj->person_id : ''),
            ($obj ? $obj->person_id : ''),
            ($obj ? htmlspecialchars($obj->last_name, ENT_QUOTES) : ''),
            ($obj ? htmlspecialchars($obj->first_name, ENT_QUOTES) : ''),
            ($obj ? htmlspecialchars($obj->primary_phone, ENT_QUOTES) : ''),
            ($obj ? htmlspecialchars($obj->primary_email, ENT_QUOTES) : ''),
            'inline',
            ($obj ? htmlspecialchars($obj->bahai_id, ENT_QUOTES) : '')
            );

        return $html;
    }


    //---------------------------------------------------------------------
    //
    //---------------------------------------------------------------------
    static function get_select_items($criteria=null) {
        $bloc = $_SESSION['app_session']->get_bahai_community();
        if (!$bloc)
            return null;

        $cat_clause = '';
        $loc_clause = sprintf("person.bahai_cmty_id = '%s' ", $bloc->get_key());
        if ($criteria) {
            if ($criteria->category_list) {
                $cat_clause = sprintf("person.person_category IN (%s) AND ",
                    implode(',', $criteria->category_list) );
            }
        }
    
        $fmt_str = "SELECT person_label.person_id, label " .
                   "FROM person_label, person " .
                   "WHERE " .
                   "person_label.person_id = person.person_id  AND " .
                   $cat_clause .
                   $loc_clause .
                   "ORDER BY label;";
        $query = sprintf($fmt_str, $bloc->get_key());
    
        $items = array();
        $res = app_session::pg_query($query);
        while ($row = pg_fetch_assoc($res)) {
            $items[ $row['person_id'] ] = $row['label'];
        }
    
        return $items;
    }

}
