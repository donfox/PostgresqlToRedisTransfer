<?php // $Id$

require_once('html_utils.php');

class bahai_community  extends user_root_class {

    protected $bahai_cmty_id;

    protected $country_code;
    protected $bahai_cmty_code;

    protected $bahai_cmty_name;

    //  Community specification within country are in
    protected $location;

    protected $time_zone;

    protected $is_lsa;       // boolean

    protected $comm_website_url;
    protected $comm_website;

    protected $bc_address_id;
    protected $bc_address;
    protected $bc_address_status;

    protected $bc_phone;
    protected $bc_fax;

    protected $bc_website_url;
    protected $bc_website;

    protected $bahai_cluster;
    protected $bahai_eu;


    //----------------------------------------------------------------
    static function clusters_exist() {
        $query = "SELECT count(*) from bahai_cluster;";
        $res = app_session::pg_query($query);
        $count = pg_fetch_result($res, 0);
        return ($count > 0);
    }


    //----------------------------------------------------------------
    //
    //----------------------------------------------------------------
    static function mode_supported($verb) {
        if (!$_SESSION['app_session']->is_superuser())
            return false;

        if (!self::clusters_exist())
            return false;
        
        return true;
    }


    //----------------------------------------------------------------
    static function get_select_items($criteria=null) {
        if (!$_SESSION['app_session']->is_superuser())
            return null;

        $query = 'SELECT bahai_cmty_id, country_code, ' .
                 'bahai_cmty_code, bahai_cmty_name FROM bahai_community;';

        $res = app_session::pg_query($query);

        $items = array();
        while ($row = pg_fetch_array($res)) {
            $items[ $row['bahai_cmty_id'] ] =
                 sprintf("%s (%s:%s)",
                         $row['bahai_cmty_name'],
                         $row['country_code'],
                         $row['bahai_cmty_code']);
        }

        return $items;
    }


    //----------------------------------------------------------------
    function check_user_data() {
        return null;
    }


    //----------------------------------------------------------------
    static function required_js_files() {
        return array('bahai_community.js', 'address.js', 'db_checker.js',
                     'XHConn2.js');
    }


    //----------------------------------------------------------------
    static function type_long_name() {
        return BAHAI . " Community";
    }


    //----------------------------------------------------------------
    static function tabs_groups() {
        return array('bahai_community_tabs');
    }


    //----------------------------------------------------------------
    function __toString() {
       return $this->bahai_cmty_name;
    }


    //----------------------------------------------------------------
    function get_key() {
        return $this->bahai_cmty_id;
    }


    //----------------------------------------------------------------
    function get_country_code() {
        return $this->country_code;
    }


    //----------------------------------------------------------------
    function get_bahai_cmty_code() {
        return $this->bahai_cmty_code;
    }


    //----------------------------------------------------------------
    function get_bahai_cmty_name() {
        return $this->bahai_cmty_name;
    }


    //----------------------------------------------------------------
    function get_cluster_code() {
        return $this->bahai_cluster;
    }


    //----------------------------------------------------------------
    function get_time_zone() {
        return $this->time_zone;
    }


    //----------------------------------------------------------------
    // Can be constructed from either:
    //   Database row data  OR  Form data ($_POST).
    //----------------------------------------------------------------
    function __construct(array $array_data) {
        $this->_copy_properties($array_data);

        $this->is_lsa = ($this->is_lsa == 'on' or $this->is_lsa == 't');

        if (array_key_exists('datatype', $array_data)) {  // from form input
            $this->handle_child_objects_from_form($array_data);
        }

    }


    //------------------------------------------------------------------------
    private function handle_child_objects_from_form(array $array_data) {

        if ($this->bc_address_status) {

            $this->bc_address =
                    address::dispatch_construct($this->country_code,
                        $array_data, 'bc_');

            if ($this->bc_address->is_blank()) {
                $this->bc_address = null;
                $this->bc_address_status =
                    ($this->bc_address_status == 'update') ?
                        'delete' : '';
            }
        }

        $this->location = location::dispatch_construct($this->country_code,
                $array_data);
    }


    //----------------------------------------------------------------
    //  Instantiate and return a new object from the database 
    //  or NULL if it doesn't exist in the database.
    //----------------------------------------------------------------
    static function read_from_db($key) {
        $query = "SELECT * from bahai_community WHERE bahai_cmty_id = $key;";
        $res = app_session::pg_query($query);
        $row = pg_fetch_array($res);
        if ($row) {
            $obj = new bahai_community($row);

/*
            if ($obj->comm_website_url) {
                $obj->comm_website =
                    website::read_from_db($obj->comm_website_url);
            }

            if ($obj->bc_website_url) {
                $obj->bc_website =
                    website::read_from_db($obj->bc_website_url);
            }
*/

            if ($obj->bc_address_id) {
                $obj->bc_address = address::dispatch_read_from_db(
                        $obj->country_code, $obj->bc_address_id);
            }

            $obj->location =
                    location::dispatch_read_from_db($obj->country_code, $key);
 
            return $obj;
        }

        else {
            return NULL;
        }
    }


    //----------------------------------------------------------------
    //  Returns the key value.
    //----------------------------------------------------------------
    function insert_to_db() {

        $this->update_child_objects_in_db();

        $fmt_str = "SELECT insert_bahai_community('%s','%s','%s','%s'," .
            "CAST ('%s' as boolean), '%s',%s,'%s','%s','%s','%s','%s',%s);";

        $query = sprintf($fmt_str, 
            pg_escape_string($this->country_code),
            pg_escape_string($this->bahai_cmty_code),
            pg_escape_string($this->bahai_cmty_name),
            pg_escape_string($this->time_zone),
            ($this->is_lsa ? 't' : 'f'),
            pg_escape_string($this->comm_website_url),
            ($this->bc_address_id ?
                    $this->bc_address_id : 'NULL'),
            pg_escape_string($this->bc_phone),
            pg_escape_string($this->bc_fax),
            pg_escape_string($this->bc_website_url),
            pg_escape_string($this->bahai_cluster),
            pg_escape_string($this->bahai_eu),
            (is_null($this->edit_errors_group_id) ? 'NULL' :
             $this->edit_errors_group_id)
            );

        $res = app_session::pg_query($query);
        $this->bahai_cmty_id = pg_fetch_result($res, 0);

        $this->location->bahai_cmty_id = $this->bahai_cmty_id;
        $this->location->insert_to_db();

        $_SESSION['app_session']->set_bahai_community($this);
        return $this->bahai_cmty_id;
    }


    //----------------------------------------------------------------
    //  Returns the key value.
    //----------------------------------------------------------------
    function update_in_db() {

        $this->update_child_objects_in_db();
        $this->location->update_in_db();

        $fmt_str = "SELECT update_bahai_community(%d,'%s','%s','%s','%s'," .
            "CAST ('%s' as boolean), '%s',%s,'%s','%s','%s','%s','%s',%s);";

        $query = sprintf($fmt_str,
             $this->bahai_cmty_id,
             pg_escape_string($this->country_code),
             pg_escape_string($this->bahai_cmty_code),
             pg_escape_string($this->bahai_cmty_name),
             pg_escape_string($this->time_zone),
             ($this->is_lsa ? 't' : 'f'),
             pg_escape_string($this->comm_website_url),
             ($this->bc_address_id ?
              $this->bc_address_id : 'NULL'),
             pg_escape_string($this->bc_phone),
             pg_escape_string($this->bc_fax),
             pg_escape_string($this->bc_website_url),
             pg_escape_string($this->bahai_cluster),
             pg_escape_string($this->bahai_eu),
             (is_null($this->edit_errors_group_id) ? 'NULL' :
              $this->edit_errors_group_id)
             );

        $res = app_session::pg_query($query);
        $_SESSION['app_session']->set_bahai_community($this);

        return $this->get_key();
    }


    //------------------------------------------------------------------------
    //
    //------------------------------------------------------------------------
    private function update_child_objects_in_db() {

        if ($this->bc_address_status == 'insert') {
            $this->bc_address_id = $this->bc_address->insert_to_db();
        }
        else if ($this->bc_address_status == 'update') {
            $this->bc_address->update_in_db();
        }
        else if ($this->bc_address_status == 'delete') {
            $this->bc_address_id = null;
        }
    }


    //----------------------------------------------------------------
    //----------------------------------------------------------------
    static function delete_from_db($key) {
        $query = "SELECT delete_bahai_community($key)";
        $res = app_session::pg_query($query);

        $_SESSION['app_session']->clear_bahai_community();
    }


    //----------------------------------------------------------------
    static function read_select_options() {

        $query = 'SELECT bahai_cmty_id, country_code, bahai_cmty_code, ' .
                 'bahai_cmty_name FROM bahai_community;';
        $res = app_session::pg_query($query);

        $select_items = array();
        while ($row = pg_fetch_array($res)) {
            $select_items[$row['bahai_cmty_id']] =
                    sprintf("%s (%s:%s)", $row['bahai_cmty_name'],
                            $row['country_code'], $row['bahai_cmty_code']);
        }

        return $select_items;
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
    //----------------------------------------------------------------
    private function value_init($fld_name) {
        if ($fld_name == 'is_lsa') {
            if ($this->is_lsa) {
                print(" checked ");
            }
        }
        else {
            print(" value='" . $this->$fld_name . "' ");
        }
    }


    //----------------------------------------------------------------
    //----------------------------------------------------------------
    static function gen_data_validation_code($function_name) {
?>
<!--  Javascript to do some simple form validation:  -->
<script>
function <?=$function_name?>() {
    if (document.forms['bahai_cmty'].bahai_cmty_code.value &&
         document.forms['bahai_cmty'].bahai_cmty_name.value) {
        return true;
    }
    else {
        alert("Error: Both bahai_cmty_code and bahai_cmty_name are required!");
        return false;
    }
}
</script>

<?php
    }


    //----------------------------------------------------------------
    static function gen_display(request $request, user_root_class $obj=null) {
        if ($request->mode == 'create' and 
                !(isset($request->extra_fields) &&
                 array_key_exists('country_code', $request->extra_fields)))
            return self::gen_country_code_form($request);

        else
            return self::gen_entry_form($request, $obj);
    }


    //----------------------------------------------------------------
    //
    //----------------------------------------------------------------
    static function gen_country_code_form(request $request) {

        $country_options_html =
            html_utils::format_options(country::$options);

        $fmt_str = <<<COUNTRY_CODE_FORM_HTML

<FORM method='GET' name='country_code_form'>
    <input type='hidden' name='datatype' value='%s'/>
    <input type='hidden' name='mode' value='%s'/>

Enter country code:
<SELECT name='country_code' id='country_code'>
%s
</SELECT>
<P>
<BUTTON type='submit'>Proceed to enter Community</BUTTON>

COUNTRY_CODE_FORM_HTML;

        $html = sprintf($fmt_str, __CLASS__, $request->mode,
                        $country_options_html);

        return $html;
    }


    //----------------------------------------------------------------
    //   HTML form generator
    //
    // For a blank form, this method is invoked as a static method.
    // For a filled in form, it's invoked as an instance method.
    //----------------------------------------------------------------
    static function gen_entry_form(request $request,
            user_root_class $obj=null) {

        $bl_pg = new tabs_group('bahai_community_tabs');  // 'bloc_');

        $bl_pg->add_pane("General",
                self::format_general($request, $obj) );
        $bl_pg->add_pane(BAHAI . " Center",
                self::format_bc($request, $obj) );
/*
        $bl_pg->add_pane("Local Spiritual Assembly",
                self::format_lsa($request, $obj) );
*/

        $key_html = '';
        if ($obj) {
            $fmt_str = <<<KEY_HTML
<input type='hidden' name='key' value='%s'/>
<input type='hidden' name='bahai_cmty_id' value='%s'/>
KEY_HTML;

            $key_html = sprintf($fmt_str, 
                    htmlspecialchars($obj->bahai_cmty_id, ENT_QUOTES),
                    htmlspecialchars($obj->bahai_cmty_id, ENT_QUOTES) );
        }

        $buttons_html = self::gen_buttons_html(__CLASS__, $request->mode);

        $delete_button = '';
        if ($request->mode == 'update') {
            $long_name = self::type_long_name();
            $delete_button = <<<DELETE_BUTTON_HTML
<BUTTON type='submit' onclick="this.form.mode.value = 'delete'; return true;">
Delete {$long_name}
</BUTTON>
DELETE_BUTTON_HTML;
        }


        $fmt_str = <<<FORM_HTML

<FORM name='%s_entry' method='POST'
    onsubmit='javascript:return bahai_community_check(this);'>

    <input type='hidden' name='datatype' value='%s'/>
    <input type='hidden' name='mode' value='%s'/>
    %s
    %s
 
    {$buttons_html}
</FORM>

<script type='text/javascript'>
%s

</script>

FORM_HTML;

        $html = sprintf($fmt_str, 
            __CLASS__,
            __CLASS__,
            $request->mode,
            $key_html,
            $bl_pg->format_html(),
            $bl_pg->format_js_init()
            );

        return $html;
    }


    //--------------------------------------------------------------------
    static function format_general(request $request,
            user_root_class $obj=null) {

/*
        $state_code = $obj ? $obj->state_code : null;
        $state_options_html =
            html_utils::format_options(state::$options, $state_code);
*/

        $country_code = $obj ? $obj->country_code :
                $request->extra_fields['country_code'];

        $timezone_list = country::get_timezone_list($country_code);
        if (is_null($timezone_list)) {
            $timezone_html = '(not set)';
        }

        else if (count($timezone_list) == 1) {
            $timezone_html = <<<TZ_HTML
<INPUT type='text' name='time_zone' id='time_zone' readonly style='border:0'
 value='{$timezone_list[0]}' />
TZ_HTML;
        }

        else {
            $options = array();
            foreach ($timezone_list as $tz) {
                $options[$tz] = $tz;
            }
            $tz_options_html = html_utils::format_options($options,
                    $obj ? $obj->time_zone : null);
            $timezone_html = "<SELECT name='time_zone' id='time_zone'>\n" .
                    $tz_options_html . "</SELECT>\n";
        }

        $location_html = location::dispatch_format_fields($country_code,
            $obj ? $obj->location : null);

#print("<p>hello 0\n");

        $cluster_options = bahai_cluster::get_select_items();
        if (!($obj and $obj->bahai_cluster)) {
            $cluster_options =
                array_merge(array(''=>'(SELECT Cluster)'), $cluster_options);
        }
#print("<p>hello 1\n");
        $cluster_options_html = html_utils::format_options($cluster_options,
                    $obj ? $obj->bahai_cluster : null);

/*
        $website_html = website::format_fields('comm_',
            ($obj ? $obj->comm_website : null) );
*/
        $website_html = website::format_url_field('comm_website_url',
            $obj ? $obj->comm_website_url : '');

        $general_fmt_str = <<<GENERAL_HTML
<TABLE>
<tr>
  <td class='left_label'>Name:</td>
  <td>
    <input name='bahai_cmty_name' type='text' value='%s'/>
    &nbsp;&nbsp; &nbsp;&nbsp;
    Community code:
    <input type='text' name='bahai_cmty_code' value='%s'/>
  </td>
</tr>

<tr><td>&nbsp;</td></tr>
<tr>
  <td class='left_label'>Country:</td>
  <td>
    <input type='text' name='country_code' value='%s'
     size='2' readonly style='border:0'/>
    &nbsp; ( %s )
    &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
    Timezone:&nbsp; &nbsp; &nbsp; %s
  </td>
</tr>

<tr>
  <td class='left_label'><br>Location:</td>
  <td>
  {$location_html}
  </td>
</tr>

<tr><td>&nbsp;</td></tr>

<tr>
  <td class='left_label'>Cluster:</td>
  <td>
    <SELECT name='bahai_cluster'>
    {$cluster_options_html}
    </SELECT>
  </td>
</tr>

<tr>
  <td class='left_label'>Electoral Unit:</td>
  <td><input name='bahai_eu' type='text' value='%s'/></td>
</tr>

<tr>
  <td class='left_label' valign='top'><br>Website:</td>
  <td colspan='2'>%s</td>
</tr>

</TABLE>

GENERAL_HTML;

        return sprintf($general_fmt_str,
            $obj ? htmlspecialchars($obj->bahai_cmty_name, ENT_QUOTES) : '',
            $obj ? htmlspecialchars($obj->bahai_cmty_code, ENT_QUOTES) : '',
            htmlspecialchars($country_code, ENT_QUOTES),
            htmlspecialchars(country::$options[$country_code]),
            $timezone_html,
            $obj ? htmlspecialchars($obj->bahai_eu, ENT_QUOTES) : '',
            $website_html);
    }


    //--------------------------------------------------------------------
    static function format_bc(request $request, user_root_class $obj=null) {

        $country_code = $obj ? $obj->country_code :
               $request->extra_fields['country_code'];

        $address_html = address::dispatch_format_fields($country_code, 'bc_',
            ($obj ? $obj->bc_address : null) );

/*
        $website_html = website::format_fields('bc_',
            ($obj ? $obj->comm_website : null) );
*/
        $website_html = website::format_url_field('bc_website_url', 
            ($obj ? $obj->bc_website_url : null) );

        $fmt_str = <<<BC_HTML
  <table>

  <tr>
    <td class='left_label'>Phone:</td>
    <td>
      <label class='format_header'>Phone</label>
      <br>
      <input type='input' name='bc_phone' value='%s'/>
    </td>
    <td>
      <label class='format_header'>Fax</label>
      <br>
      <input type='input' name='bc_fax' value='%s'/>
    </td>
  </tr>

  <tr>
    <td class='left_label'>Address:</td>
    <td colspan='2'>%s</td>
  </tr>

  <tr><td>&nbsp;</td></tr>

  <tr>
    <td class='left_label' valign='top'><br>Website:</td>
    <td colspan='2'>%s</td>
  </tr>

  </table>

BC_HTML;

        $bc_html = sprintf($fmt_str, 
            $obj ? htmlspecialchars($obj->bc_phone, ENT_QUOTES) : '',
            $obj ? htmlspecialchars($obj->bc_fax, ENT_QUOTES) : '',
            $address_html,
            $website_html);
        
        return $bc_html;
    }


/*
    //--------------------------------------------------------------------
    static function format_lsa(request $request, user_root_class $obj=null) {

        $lsa_html = <<<LSA_HTML

    <table>
      <tr>
        <td>
        Local Spiritual Assembly
        </td>
      </tr>
    </table>
LSA_HTML;

        return $lsa_html;
    }
*/

}
?>
