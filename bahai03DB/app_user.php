<?php  // $Id$

class app_user extends user_root_class {  // Application User

protected $login;
protected $bahai_cmty_id;
protected $bahai_community;

protected $full_name;
protected $email;
protected $password;
protected $password_dup;
protected $creator;
protected $create_ts;
protected $update_ts;

protected $privileges = array();

/*
static protected $nonclass_domains = array(
    'read_scope' => array('community', 'cluster', 'global')
    );

static protected $nonclass_domain_labels = array(
    'read_scope' => 'Read Scope'
    );
*/


//------------------------------------------------------------------
static function required_js_files() {
    return array('app_user.js', 'db_checker.js', 'ajax.js');
}


//------------------------------------------------------------------
static function privilege_levels_labels() {
    return array('none', 'read-only', 'modify');
}


//------------------------------------------------------------------
private function read_privileges() {
    $this->privileges = array();
    
    $query = sprintf("SELECT * from app_user_privilege WHERE login = '%s';",
                     pg_escape_string($this->login));
    $res = app_session::pg_query($query);
    while ($row = pg_fetch_assoc($res)) {
        $this->privileges[$row['domain']] = $row['privilege_level'];
    }
}


//------------------------------------------------------------------
function get_privilege($domain) {
    if ($domain == 'member')   // for now, share person privilege with member
        $domain = 'person';

    return array_key_exists($domain, $this->privileges) ? 
            $this->privileges[$domain] : 0;
}


//------------------------------------------------------------------
static function type_long_name() {
    return "Application User";
}


//------------------------------------------------------------------
function get_key() {
    return $this->login;
}


//------------------------------------------------------------------
function __toString() {
    return $this->login;
}


//------------------------------------------------------------------
function get_login() {
    return $this->login;
}


//------------------------------------------------------------------
// Can be constructed from form data or database row
//------------------------------------------------------------------
function __construct(array $array_data) {
    $this->_copy_properties($array_data);

    $key = $this->bahai_cmty_id;
    $this->bahai_community = bahai_community::read_from_db($key);

    if (!$this->bahai_community) {
        throw new Exception("Unexisting bahai community");
    }

    if (array_key_exists('datatype', $array_data)) {
        $this->handle_child_objects_from_form($array_data);
    }
}


//------------------------------------------------------------------
private function handle_child_objects_from_form(array $array_data) {

    $classes = user_root_class::get_class_list();
    foreach ($classes as $domain) {
        if (array_key_exists($domain . '_privilege', $array_data)) {
            $this->privileges[$domain] = $array_data[$domain . '_privilege'];
        }
    }
    
/*
    foreach (array_keys(self::$nonclass_domains) as $domain) {
        if (array_key_exists($domain . '_privilege', $array_data)) {
            $this->privileges[$domain] = $array_data[$domain . '_privilege'];
        }
    }
*/
}


//------------------------------------------------------------------
//
//------------------------------------------------------------------
static function mode_supported($verb) {
    if ($_SESSION['app_session']->is_superuser()) {
        return ($_SESSION['app_session']->get_bahai_community() != null);
    }

    else {
        $user = $_SESSION['app_session']->get_app_user();

        switch ($user->get_privilege(__CLASS__)) {
            case 0:  return false;
            case 1:  return ($verb == 'select');
            case 2:  return true;
        }
    }
}


//------------------------------------------------------------------
function check_user_data() {

}


//------------------------------------------------------------------
static function get_select_items($criteria=null) {
    $bloc = $_SESSION['app_session']->get_bahai_community();
    if (!$bloc)
        return null;

    $query = sprintf("SELECT login FROM app_user WHERE bahai_cmty_id = %s;",
            $bloc->get_key());

    $items = array();
    $res = app_session::pg_query($query);
    while ($row = pg_fetch_array($res)) {
        $items[$row[0]] = $row[0];
    }

   return $items;
}


//------------------------------------------------------------------
static function process_change_password() {
    return self::change_password($_POST['login'], $_POST['password']);
}


//------------------------------------------------------------------
// >>>  app_user::get_creator  <<<
//
//------------------------------------------------------------------
function get_creator() {
    if (!$this->creator) {
        $query = "SELECT creator from app_user_creator " .
                "WHERE login = '$this->login';";
        $res = app_session::pg_query($query);
        $this->creator = pg_fetch_result($res, 'creator');
    }
   
    return $this->creator;
}


//------------------------------------------------------------------
//>>>  app_user::read_from_db  <<<
//
//  Construct an app_user object, reading the data from the database,
//  given a key value.
//------------------------------------------------------------------
static function read_from_db($login) {
    $query = "SELECT * from app_user WHERE login = '$login';";
    $res = app_session::pg_query($query);
    $row = pg_fetch_array($res);

    if (!$row)
        return null;

    $app_user = new app_user($row); 
    
    $app_user->read_privileges();

    return $app_user;
}


//------------------------------------------------------------------
//>>>  app_user::insert_from_db  <<<
//
//  Insert the data from the given app_user object into the database.
//------------------------------------------------------------------
function insert_to_db() {
    $this->handle_privileges();

    $query = sprintf("SELECT insert_app_user('%s',%d,'%s','%s','%s',%s);",
            pg_escape_string($this->login),
            $this->bahai_cmty_id,
            pg_escape_string($this->full_name),
            pg_escape_string($this->email),
            MD5($this->password),
            (is_null($this->edit_errors_group_id) ? 'NULL' :
             $this->edit_errors_group_id)
            );

    $res = app_session::pg_query($query);

    return $this->login;
}


//------------------------------------------------------------------
//>>>  app_user::update_in_db  <<<
//
//  Update the record in the database, corresponding to the given
//  app_user object.
//------------------------------------------------------------------
function update_in_db() {
    $this->handle_privileges();

    $query = sprintf("SELECT update_app_user('%s','%s','%s',%s);",
            pg_escape_string($this->login),
            pg_escape_string($this->full_name),
            pg_escape_string($this->email),
            (is_null($this->edit_errors_group_id) ? 'NULL' :
             $this->edit_errors_group_id)
            );

    $res = app_session::pg_query($query);

    return $this->get_key();
}


//------------------------------------------------------------------
private function handle_privileges() {
    foreach($this->privileges as $domain => $level) {
        $query = sprintf("SELECT app_user_set_privilege('%s','%s', " .
                         "CAST (%d as smallint));",
            $this->login, $domain, $level);
        app_session::pg_query($query);
    }
}


//------------------------------------------------------------------
//>>>  app_user::delete_from_db  <<<
//
//  Delete the record from the database, corresponding to the given
//  app_user object.
//------------------------------------------------------------------
static function delete_from_db($key) {
    $query = "SELECT delete_app_user('$key');";

    $res = app_session::pg_query($query);
}


//--------------------------------------------------------------------------
//>>>  app_user::get_bahai_community  <<<
//
//  Return the Bahai community to which this user belongs.
//--------------------------------------------------------------------------
function get_bahai_community() {
    return $this->bahai_community;
}


//--------------------------------------------------------------------------
//
//--------------------------------------------------------------------------
static function gen_selector() {

    $sess = $_SESSION['app_session'];
    $session_user = $sess->is_superuser() ? NULL : $sess->get_app_user();
    if (!$session_user) 
        return;

    $bahai_cmty = $sess->get_bahai_community();

    $tables = 'app_user';
    $where = "bahai_cmty_id = " . $bahai_cmty->get_key();

/*
    *********** NEED SOMETHING HERE ************
    if ($session_user and !$session_user->can_see_all_users) {
        if (!$session_user->can_create_users) {
            return;
        }
        $tables .= ', app_user_mods';
        $where .= " AND app_user.login = app_user_mods.login AND " .
                  " app_user_mods.create_user = '" .
                  $session_user->login . "'";
    }
*/

    $query = "SELECT app_user.login FROM $tables WHERE $where;";
    $res = app_session::pg_query($query);

    $select_items =  array();
    $select_items[''] = '(' . self::type_long_name() . ')';
    while ($row = pg_fetch_array($res)) {
        $select_items[$row['login']] = $row['login'];
    }

    $options_html = html_utils::format_options($select_items);

    $url = 'datatype=app_user&mode=update&key=this.value';

    $html = <<<SELECTOR_HTML

<FORM name='app_user_select'>
  <input name='datatype' value='app_user' type='hidden'/>
  <input name='mode' value='update' type='hidden'/>
  <SELECT name='key' onchange="go_to('app_user', this.value)" >
  {$options_html}
  </SELECT>
</FORM>

SELECTOR_HTML;

        return $html;
}


//------------------------------------------------------------------
//  If in modify mode, the privilege levels shown for each domain
//  must not exceed the level of the logged in user.
//  This prevents a user from creating another user more powerful than
//  himself.
//  An exception is when a user already has greater capabilities 
//  (and so they must obviously be shown as they are).
//------------------------------------------------------------------
static private function format_privilege_selector(
        $domain, $label, $privileges_list, $selected_level, $read_only) {

    $session_user = $_SESSION['app_session']->get_app_user();
    $max_level = is_null($session_user) ? count($privileges_list)-1 :
            max($session_user->get_privilege($domain), $selected_level);
    $options = array();
    for ($i=0; $i<=$max_level; ++$i) {
        $options[$i] = $privileges_list[$i];
    }
    $options_html = html_utils::format_options($options, $selected_level);

    $html = <<<PRIVILEGE_SELECTOR_HTML

<TR>
  <TD style='left_label'>{$label}:</TD>
  <TD>
    <SELECT name='{$domain}_privilege'>
    {$options_html}
    </SELECT>
  </TD>
</TR>

PRIVILEGE_SELECTOR_HTML;

    return $html;
}


//------------------------------------------------------------------
//>>>  app_user::gen_display  <<<
//
//  Generate an HTML form for inputing app_user information.
//------------------------------------------------------------------
static function gen_display(request $request, user_root_class $obj=null) {

    return self::gen_entry_form($request, $obj);
}


//------------------------------------------------------------------
//>>>  app_user::gen_display  <<<
//
//  Generate an HTML form for inputing app_user information.
//------------------------------------------------------------------
static function gen_entry_form(request $request, user_root_class $obj=null) {

    $bahai_cmty = $_SESSION['app_session']->get_bahai_community();
    $session_user = $_SESSION['app_session']->get_app_user();
    $read_only = ($obj and $session_user and
            $session_user->login != $obj->get_creator());

    $name_html = '';
    $creator_html = '';
    if ($obj) {
        if ($obj->full_name) {
            $name_html .= sprintf(" value='%s'",
                htmlspecialchars($obj->full_name, ENT_QUOTES));
        }
        $creator = $obj->get_creator();
        if (!$creator) {
            $creator = '<font +3>&infin;</font>';
        }
        
        $creator_html = <<<CREATOR_HTML
  <tr>
    <td colspan='2'>(Created by {$creator})</td>
  </tr>
CREATOR_HTML;
    }

    $login_style = ($request->mode == 'create') ? '' :
            sprintf(" style='border:0' readonly value='%s'\n", $obj->login);  

    $fmt_str = <<<FORM_1_HTML

<FORM name='app_user_entry' id='app_user_entry' method='POST'
   onsubmit='javascript:return validate_create(this);'>

  <input name='datatype' value='app_user' type='hidden'/>
  <input name='mode' value='%s' type='hidden'/>
  <input name='bahai_cmty_id' value='%s' type='hidden' />
  <input name='key' value='%s' type='hidden'/>

  <TABLE>
  <tr>
    <td class='left_label'>
        <label for='login'>Login ID:</label>
    </td>
    <td>
        <input name='login' size='20' type='text' {$login_style} />
    </td>
    <td rowspan='8'>
      &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
      &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    </td>
  </tr>
  <tr>
    <td class='left_label'>
      <label for='full_name'>Full Name:</label>
    </td>
    <td>
      <input name='full_name' type='text' value='%s'/>
    </td>
  </tr>
  <tr>
    <td class='left_label'>
      <label for='email'>Email:</label>
    </td>
    <td>
      <input name='email' type='text' value='%s'/>
    </td>
  </tr>
  {$creator_html}
  <tr>
    <td><br>PRIVILEGES:</td>
  </tr>
FORM_1_HTML;

    $html = sprintf($fmt_str, 
            $request->mode,
            $bahai_cmty->get_key(),
            ($obj ? $obj->get_key() : ''),
            ($obj ? htmlspecialchars($obj->full_name, ENT_QUOTES) : ''),
            ($obj ? htmlspecialchars($obj->email, ENT_QUOTES) : '')
            );

    $classes = user_root_class::get_class_list();

    foreach ($classes as $class_name) {
        $priv_list =
            call_user_func(array($class_name, 'privilege_levels_labels'));
        if ($priv_list) {
            $selected_level =
                ($obj && array_key_exists($class_name, $obj->privileges))
                    ? $obj->privileges[$class_name] : null;

            $html .= self::format_privilege_selector(
                    $class_name, 
                    call_user_func(array($class_name, 'type_long_name')),
                    $priv_list,
                    $selected_level,
                    $read_only);
        }
    }

/*
    foreach (self::$nonclass_domains as $domain => $levels) {
        $selected_level =
            ($obj && array_key_exists($domain, $obj->privileges))
                ? $obj->privileges[$domain] : null;
        $html .= self::format_privilege_selector(
                $domain, 
                self::$nonclass_domain_labels[$domain],
                $levels,
                $selected_level,
                $read_only);
    }
*/


    if ($request->mode == 'create') {
        $html .= <<<FORM_2_HTML
  <tr><td>&nbsp;</td></tr>
  <tr>
    <td class='left_label'><label for=password>Type password:</label></td>
    <td> <input name='password' type='password'/> </td>
  </tr>
  <tr>
    <td class='left_label'>
      <label for=password_dup>Retype password:</label>
    </td>
    <td><input name='password_dup' type='password'/></td>
  </tr>
FORM_2_HTML;
    }

    $fmt_str = <<<FORM_3_HTML
  <tr><td>&nbsp;</td></tr>
  <tr>
    <td colspan='3'>
        %s
    </td>
  </tr>
</FORM>
FORM_3_HTML;
    $html .= sprintf($fmt_str, 
            self::gen_buttons_html('app_user', $request->mode, $read_only));

    if ($request->mode == 'update' && !$read_only) {
        $fmt_str = <<<FORM_4_HTML
  <tr>
    <td valign='top' colspan='3'>
    <br><br>
    %s
    </td>
  </tr>
FORM_4_HTML;
        $html .= sprintf($fmt_str, $obj->gen_password_change_form());
    }

    $html .= "</TABLE>\n";

    if ($read_only) {
        $html .= <<<FORM_6_HTML
<SCRIPT type='text/javascript'>
disable_form('app_user_entry');
</SCRIPT>
FORM_6_HTML;
    }

    return $html;

}   // END of app_user::gen_entry_form


//------------------------------------------------------------------
//
//------------------------------------------------------------------
static function change_password($login, $password) {

    $query = "SELECT change_password('$login', '" . MD5($password) . "');";
    $res = app_session::pg_query($query);

    return new request( array(
            'login' => $login,
            'datatype' => 'app_user',
            'mode' => 'update',
            'key' => $login,
            'message' => 'Password successfully changed.') );
}


//------------------------------------------------------------------
//>>>  app_user::gen_password_change_form  <<<
//
//  When in UPDATE mode, passwords are not in the entry form.
//  This function generates a separate form for changing password.
//------------------------------------------------------------------
function gen_password_change_form() {

    $bahai_cmty = $_SESSION['app_session']->get_bahai_community();

    $fmt_str = <<<FORM_HTML

<FORM name='app_user_password' method='POST'
  onsubmit='javascript:return validate_password_change(this);' >

<input name=datatype type=hidden value='app_user'/>
<input name=mode type=hidden value='change_password'/>

<input name='bahai_cmty_country_code' type='hidden' value='%s'/>
<input name='bahai_cmty_code' type='hidden' value='%s'/>
<input name='login' type='hidden' value='%s'/>

<TABLE border=1>
<tr><th colspan=2>Change password</th></tr>
<tr>
  <td> <label for='password'>Type new password</label> </td>
  <td> <input name='password' type='password'/> </td>
</tr>
<tr>
  <td><label for='password_dup'>Retype new password</label></td>
  <td><input name='password_dup' type='password'/></td>
</tr>
<tr>
  <td colspan=2 align=center>
  <button type='submit'>Set password</button>
  &nbsp;&nbsp;
  <button type='reset'>Reset Form</button>
  </td>
</tr>
</TABLE>

</td>
</tr>

</TABLE>
</FORM>
FORM_HTML;

    $form_html = sprintf($fmt_str, 
            $bahai_cmty->get_country_code(),
            htmlspecialchars($bahai_cmty->get_bahai_cmty_code(), ENT_QUOTES),
            htmlspecialchars($this->login, ENT_QUOTES)
            );

    return $form_html;
   
}  // END of app_user::gen_password_change_form

}  // END CLASS app_user

?>
