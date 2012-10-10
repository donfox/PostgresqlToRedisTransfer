<?php

class member_employment extends auto_construct  implements type_in_db {

    public $person_id;
    public $status;

    public $employer_name;
    public $employer_phone;
    public $employer_addr;
    public $employer_addr_id;

    public $address_status;

    public $member_work_phone;
    public $member_work_email;

    public $is_preferred_phone;  // boolean
    public $is_preferred_email;  // boolean

    static $max_num_employers = 3;


    //----------------------------------------------------------------------
    function __construct(array $array_data, $index=null) {
        $prefix = is_null($index) ? '' : "empl_{$index}_";
        $this->_copy_properties($array_data, $prefix);

        $cmty = $_SESSION['app_session']->get_bahai_community();
        $country_code = $cmty->get_country_code();

        if (array_key_exists('datatype', $array_data)) {
            $this->employer_addr = address::dispatch_construct($country_code,
                    $array_data, $prefix);
            if ($this->employer_addr->is_blank()) {
                $this->employer_addr = null;
            }
        }

        else if ($this->employer_addr_id) {
            $this->employer_addr = address::dispatch_read_from_db(
                    $country_code, $this->employer_addr_id);
        }
    }


    //----------------------------------------------------------------------
    function set_person_id($person_id) {
        $this->person_id = $person_id;
    }


    //----------------------------------------------------------------------
    function is_blank() {
        foreach (array('employer_name', 'employer_phone', 'employer_addr',
                       'member_work_phone', 'member_work_email') as $fld) {
            if ($this->{$fld})
                return false;
        }

        return true;
    }


    //----------------------------------------------------------------------
    function set_preferred($is_preferred_phone, $is_preferred_email) {
        $this->is_preferred_phone = $is_preferred_phone;
        $this->is_preferred_email = $is_preferred_email;
    }


    //----------------------------------------------------------------------
    function process_in_db() {

        if ($this->employer_addr) {
            switch ($this->employer_addr->address_status) {
                case 'insert':
                    $this->employer_addr_id =
                        $this->employer_addr->insert_to_db();
                    $query =
                        sprintf("SELECT update_employer_address(%d,'%s',%d);",
                            $this->person_id,
                            pg_escape_string($this->employer_name),
                            $this->employer_addr_id );
                    $res = app_session::pg_query($query);
                    break;

                case 'update':
                    $this->employer_addr->update_in_db();
                    break;
            
                default:
            }
        }

        switch ($this->status) {
            case 'insert':
                $this->insert_to_db();
                break;

            case 'update':
                $this->update_in_db();
                break;

            case 'delete':
                $key = $this->person_id . KEY_SEPARATOR . $this->employer_name;
                self::delete_from_db($key);
                break;

            case '':
            case 'unchanged':
                break;

            default:
                die("Shouldn't get here, status = {$this->status}"); 
        }
    }


    //----------------------------------------------------------------------
    function insert_to_db() {

        $query = sprintf(
                "SELECT insert_member_employment(%d,'%s',%s,'%s','%s','%s');",
                $this->person_id,
                pg_escape_string($this->employer_name),
                ($this->employer_addr_id ? $this->employer_addr_id : 'NULL'),
                pg_escape_string($this->employer_phone),
                pg_escape_string($this->member_work_phone),
                pg_escape_string($this->member_work_email)
                );

        app_session::pg_query($query);
    }


    //----------------------------------------------------------------------
    function update_in_db() {

/*
        if ($this->address_status == 'insert') {
            $this->employer_addr_id = $this->employer_addr->insert_to_db();
            $query = sprintf("SELECT update_employer_address(%d,'%s',%d);",
                    $this->person_id,
                    pg_escape_string($this->employer_name),
                    $this->employer_addr_id );
            $res = app_session::pg_query($query);
        }

        else if ($this->address_status == 'update') {
            $this->employer_addr->update_in_db();
        }

        else if ($this->address_status == 'delete') {
            $this->update_employer_addr->delete_from_db();
            $query = sprintf("SELECT update_employer_address(%d,'%s',NULL);",
                    $this->person_id,
                    pg_escape_string($this->employer_name));
            $res = app_session::pg_query($query);
        }
*/
        
        $query = sprintf(
                "SELECT update_member_employment(%d,'%s','%s','%s','%s');",
                $this->person_id,
                pg_escape_string($this->employer_name),
                pg_escape_string($this->employer_phone),
                pg_escape_string($this->member_work_phone),
                pg_escape_string($this->member_work_email)
                );

        app_session::pg_query($query);
    }


    //----------------------------------------------------------------------
    static function delete_from_db($key) {

        list($person_id, $empl_name) = explode(KEY_SEPARATOR, $key);

        $query = sprintf("SELECT delete_member_employment(%d, '%s');",
                         $person_id,
                         pg_escape_string($empl_name));
        app_session::pg_query($query);
    }


    //---------------------------------------------------------------
    static function box_for_create_employer() {

        $new_empl_style = 'border:solid 2px #060; padding:4px; ' .
                          'background-color:#ddeedd; ';

        $html = <<<BOX_HTML

<tr>
<td colspan='2' border='2' style='{$new_empl_style}' id='new_employer_row'>
  <font color='green' size='+1'>To Create a new employer:</font>
  <br>
  First, enter employer name&nbsp;
  <input type='text' size='20'
     name='new_employer_name' id='new_employer_name'
     onKeyUp='javascript:key_new_employer(this);'
     onKeyPress='javascript:return listen_for_enter(event);'
     />
  &nbsp; &nbsp;
  <BUTTON type='button' id='new_employer_button'
     onClick='display_new_employer();' disabled='true'>
     Then click HERE to Open Form
  </BUTTON>
</td>
</tr>

BOX_HTML;

        return $html;
    }


    //----------------------------------------------------------------------
    function __toString() {
        return $this->employer_name;
    }


    //----------------------------------------------------------------------
    //static function read_from_db($person_id, $employer_name)
    static function read_from_db($key) {

        $cmty = $_SESSION['app_session']->get_bahai_community();
        $country_code = $cmty->get_country_code();

        list($person_id, $employer_name) = explode(KEY_SEPARATOR, $key);

        $query = sprintf("SELECT * from member_employment WHERE " .
                         "person_id = %d AND employer_name = '%s';",
                         $person_id,
                         pg_escape_string($employer_name) );
                      
        $res = app_session::pg_query($query);
        $row = pg_fetch_assoc($res);

        $obj = new member_employment($row);

        if ($obj->employer_addr_id) {
            $obj->employer_addr = address::dispatch_read_from_db($country_code,
                    $obj->employer_addr_id);
        }

        return $obj;
    }


    //----------------------------------------------------------------------
    function set_employer_address(address $employer_address) {
        $this->employer_address = $employer_address;
    }


    //----------------------------------------------------------------------
    static function format_section($employers) {

        $max = self::$max_num_employers;
        $curr_num_empl = count($employers);

        $html = <<<JS_HTML
<SCRIPT type='text/javascript' src='member_employment.js'>
</SCRIPT>

<SCRIPT type='text/javascript'>
    max_num_empl = {$max};
    num_empl = {$curr_num_empl};
</SCRIPT>
JS_HTML;

        for ($i=0; $i<self::$max_num_employers; ++$i) {
            $html .= self::format_fields($i+1,
                      ($i<count($employers) ? $employers[$i] : null) );
        }

        return $html;
    }


    //----------------------------------------------------------------------
    static function format_fields($index, member_employment $obj=null) {

        $cmty = $_SESSION['app_session']->get_bahai_community();
        $country_code = $cmty->get_country_code();

        $orig_status = $obj ? 'unchanged' : '';
        $change_status = $obj ? 'update' : 'insert';

        $div_display = $obj ? 'inline' : 'none';

        $address_chg = "empl_field_changed($index, '$change_status');";
        $address_obj = $obj ? $obj->employer_addr : null;
        $address_html = address::dispatch_format_fields($country_code,
                "empl_{$index}_", $address_obj, $address_chg);

        $js_append_employer = '';
        if ($obj) {
            $emp = addslashes($obj->employer_name);
            $js_append_employer = <<<JS_APPEND_HTML
<SCRIPT type='text/javascript'>
    empl_names.push('{$emp}');
</SCRIPT>
JS_APPEND_HTML;
        }

        $fmt_str = <<<MEMBER_EMPLOYMENT_HTML

{$js_append_employer}
<div id='empl_{$index}' style='display:{$div_display}'>

<table>  <!-- EMPLOYER -->
  <tr><td><table><tr>  <!-- top line -->
    <td>
      <label for='empl_{$index}_delete'>
        &nbsp;
        <scan style='color:red; font-size:large; font-style:italic'>X</scan>
      </label>
      <br>
      <a title='Mark this employer for deletion.'>
      <input type='checkbox' 
        name='empl_{$index}_delete' id='empl_{$index}_delete'
        onchange='empl_field_changed({$index}, "delete");'
       />
       </a>
       &nbsp;&nbsp;
    </td>
    <td>
      <input type='hidden' value="{$orig_status}"
       name='empl_{$index}_status' id='empl_{$index}_status'/>
      <input type='hidden' value='delete'
       name='empl_{$index}_status_saved' id='empl_{$index}_status_saved'/>

      <label for='empl_{$index}_employer_name' class='field_header'>
      Employer Name</label>
      <br>
      <input type='text' size='30'
        name='empl_{$index}_employer_name' id='empl_{$index}_employer_name'
        style='border:0; background:#ccccff' readonly value='%s'/>
    </td>
    <td>&nbsp;&nbsp;</td>
    <td>
      <label for='empl_{$index}_employer_phone' class='field_header'>
       Phone</label>
      <br>
      <input type='text' name='empl_{$index}_employer_phone' size='12'
       onchange='empl_field_changed({$index}, "{$change_status}");'
       value='%s'/>

     </td>
  </tr></table></td></tr>  <!-- END of top line -->

  <tr><td colspan='2'>  <!-- address -->
  {$address_html}
  </td></tr>

  <tr> <!-- phone and email -->
    <td colspan='2'><table>
      <tr>
        <td>
          <br><font size='+1'>Member contact at 
          <input type='text' readonly value='%s' style='border:0' 
            name='empl_{$index}_name2' id='empl_{$index}_name2'/>
          :&nbsp;&nbsp;&nbsp;</font>
        </td>
        <td>
          <label for='empl_{$index}_member_work_phone'
              class='field_header'>&nbsp;&nbsp;Phone</label>
          <br>
          <nobr>
          <a title=
           'Choose this as member&quot;s preferred work phone number'>
          <input type='radio' %s value='%s'
           name='preferred_phone_employer' id='empl_{$index}_preferred_phone'/>
           </a>
          <input type='text' name='empl_{$index}_member_work_phone'
           size='12' value='%s'
            onchange='empl_field_changed({$index}, "{$change_status}");'
           />
          </nobr>
        </td>
        <td>&nbsp;&nbsp;</td>
        <td>
          <label for='empl_{$index}_member_work_email_{$index}'
              class='field_header'>&nbsp;&nbsp;Email</label>
          <br>
          <nobr>
          <a title=
           'Choose this as member&quot;s preferred work email address'>
          <input type='radio' %s value='%s'
           name='preferred_email_employer' id='empl_{$index}_preferred_email'/>
          </a>
          <input type='text' name='empl_{$index}_member_work_email'
          type='text' name='empl_{$index}_member_work_email'
           size='30' size='12' value='%s' 
            onchange='empl_field_changed({$index}, "{$change_status}");'
           />
          </nobr>
        </td>
      </tr>
    </table></td>
  </tr>  <!-- END phone and email -->
</table> <!-- END EMPLOYER -->

<hr style='color:green'>

</div>

MEMBER_EMPLOYMENT_HTML;


        $html = $obj ? 
            sprintf($fmt_str, 
                htmlspecialchars($obj->employer_name, ENT_QUOTES),
                htmlspecialchars($obj->employer_phone, ENT_QUOTES),
                htmlspecialchars($obj->employer_name, ENT_QUOTES),
                ($obj->is_preferred_phone ? 'CHECKED' : ''),
                htmlspecialchars($obj->employer_name, ENT_QUOTES),
                htmlspecialchars($obj->member_work_phone, ENT_QUOTES),
                ($obj->is_preferred_email ? 'CHECKED' : ''),
                htmlspecialchars($obj->employer_name, ENT_QUOTES),
                htmlspecialchars($obj->member_work_email, ENT_QUOTES)
                )
             : sprintf($fmt_str, '','','','','','','','','');

        return $html;
    }

}

?>
