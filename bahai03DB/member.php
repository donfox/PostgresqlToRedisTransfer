<?php  // $Id

class member extends user_root_class {

    static private $sections = array(
            'personal',
            'contact',
            'addresses',
            'bahai',
            //'legal',
            'employment',
            'emergency');

    static private $languages =
        array('english', 'spanish', 'farsi', 'chinese', 'french');

    static private $pg_list;

    public $person_id;

    //  Personal Information
    public $last_name;
    public $first_name;
    public $aka;

    public $is_male;  // 't','f' or null

    public $language;
    public $language_entry;      /* convenience field */
    public $language_2nd;
    public $language_2nd_entry;  /* convenience field */

    public $date_of_birth;
    public $date_of_death;


    // *** CONTACT INFO ***
    public $home_phone;
    public $cell_phone;
    public $fax_phone;

    public $primary_phone_choice;   // 1=home, 2=work, 3=cell
    public $primary_email_choice;   // 1=home, 2=work

    public $personal_email;
    public $personal_website_url;

    //  If null, then the personal is primary, otherwise this indicates
    //  which employer is primary.
    public $preferred_phone_employer;
    public $preferred_email_employer;

    //  Residential address:
    public $res_address;
    public $res_address_id;
    public $res_address_status;

    public $mailing_address;
    public $mailing_address_id;
    public $mailing_address_status;
    public $mailing_same;

    // *** BAHAI information ***

    public $bahai_cmty_id;

    // Bahai country and personal id are required.
    // The country id might not match that of the community or that of the
    // address in the case of guests or member in transition.
    public $bahai_id_country;
    public $bahai_id;

    public $date_became_bahai;
    public $is_deprived;

    // The following is A, Y, J, or C
    //   Age categories:
    //      Adult         >=21
    //      youth         15-20
    //      junior youth  10-14
    //      children      <10
    public $age_category;

    // *** LEGAL ***
    public $location_of_will;

    public $attorney_name;
    public $attorney_firm;
    public $attorney_phone;
    public $attorney_email;

    public $attorney_address;
    public $attorney_address_id;
    public $attorney_address_status;


    // *** EMPLOYMENT ***
    public $occupation;
    public $is_healthcare_provider;

    public $member_employment_places;   // rows in 'member_employment' table 

    // The following fields facilitate transfer of data from form
    // (since they automatically get copied in the constructor) :


    //----------------------------

    public $empl_1_status;
    public $empl_1_delete;

    //----------------------------

    public $empl_2_status;
    public $empl_2_delete;

    //----------------------------

    public $empl_3_status;
    public $empl_3_delete;

    //----------------------------

    // *** EMERGENCY information ***
    //   Rows in emergency_contact

    public $emergency_contacts;


//------------------------------------------------------------------------
function __construct(array $array_data) {

    $this->_copy_properties($array_data);

    $this->member_employment_places = array();

    if (array_key_exists('datatype', $array_data)) {   // from form input
        
        if (array_key_exists('gender', $array_data) && $array_data['gender']) {
            $this->is_male = ($array_data['gender'] == 'male');
        }

        if ($this->language == 'other') {
            $this->language = $this->language_entry;
        }

        if ($this->language_2nd == 'other') {
            $this->language_2nd = $this->language_2nd_entry;
        }

        $this->mailing_same = ($this->mailing_same == 'on');

        // MUST DELINK addresses ?
        if (!$this->mailing_same &&
                $this->res_address_id == $this->mailing_address_id) {

            if (!$this->mailing_address_status)
                $this->mailing_address_status = 'delete';
            else if ($this->mailing_address_status == 'update')
                $this->mailing_address_status = 'insert';
        }

        $this->handle_child_objects_from_form($array_data);
    }

    else {   // from DATABASE
        if (isset($this->is_male) and !is_null($this->is_male)) {
            $this->is_male = ($this->is_male == 't' or $this->is_male == 'on');
        }

        $this->mailing_same = (!$this->res_address_id ||
                ($this->res_address_id == $this->mailing_address_id));
    }
}


//------------------------------------------------------------------------
private function handle_child_objects_from_form(array $array_data) {

    $cmty = $_SESSION['app_session']->get_bahai_community();
    $country_code = $cmty->get_country_code();

    if ($this->attorney_address_status) {
       
        $this->attorney_address = address::dispatch_construct($country_code,
                $array_data, 'attorney_');

        if ($this->attorney_address->is_blank()) {
            $this->attorney_address = null;
            $this->attorney_address_status = 
                ($this->attorney_address_status == 'update') ? 'delete' : '';
        }
    }

    if ($this->res_address_id || $this->res_address_status) {
        $this->res_address = address::dispatch_construct($country_code,
                $array_data, 'res_');

        if ($this->res_address->is_blank()) {
            $this->res_address = null;
            $this->res_address_status = 
                ($this->res_address_status == 'update') ? 'delete' : '';
        }
    }

    if ($this->mailing_same) {
        $this->mailing_address = $this->res_address;
    }

    else if ($this->mailing_address_status) {
        $this->mailing_address = address::dispatch_construct($country_code,
                $array_data, 'mailing_');

        if ($this->mailing_address->is_blank()) {
            $this->mailing_address = null;
            $this->mailing_address_status =
                ($this->mailing_address_status == 'update') ? 'delete' : '';
        }
    }


    // Employment

    for ($i=1; $i<=3; ++$i) {
/*
        $addr_stat_fld = "empl_{$i}_address_status";
        if ($this->$addr_stat_fld) {
            $addr_fld = "empl_{$i}_address";
            $this->$addr_fld = new address($array_data, "empl_{$i}_");
        }
*/

        $stat_fld = "empl_{$i}_status";
        if (array_key_exists($stat_fld, $array_data)
                and $array_data[$stat_fld]) {
            $empl = new member_employment($array_data, $i);
            $phone_pref =
                (array_key_exists('preferred_phone_employer', $array_data)
                 and
                 $array_data["empl_{$i}_employer_name"] == 
                           $array_data['preferred_phone_employer']);

            $email_pref =
                (array_key_exists('preferred_email_employer', $array_data)
                 and
                 $array_data["empl_{$i}_employer_name"] == 
                           $array_data['preferred_email_employer']);
            $empl->set_preferred($phone_pref, $email_pref);

            array_push($this->member_employment_places, $empl);
        }

    }


    $this->emergency_contacts = array();
    // Emergency contacts
    $i=1;
    while (array_key_exists('emerg_' . $i . '_rel_person_id', $array_data)) {
        $rel_key = 'emerg_' . $i . '_relationship';
        $emcon = new emergency_contact(
            array(
                'person_id' => $this->person_id,
                'rel_person_id' =>
                    $array_data['emerg_' . $i . '_rel_person_id'],
                'relationship' => (array_key_exists($rel_key, $array_data) ?
                    $array_data[$rel_key] : '')
            ) );

        array_push($this->emergency_contacts, $emcon);
        ++$i;
    }

}


//------------------------------------------------------------------------
//  Returns an   object
//------------------------------------------------------------------------
function check_user_data() {

    $edit_errors_group = null;
    $edit_errors = array();

    // Actually this one should be caught by the javascript client check,
    // but I also put it here.
    if (empty($this->last_name) and empty($this->first_name)) {
        array_push($edit_errors,
            new edit_error('Both last name and first name are required.', ''));
    }

    if (is_null($this->is_male)) {
        array_push($edit_errors,
            new edit_error('Gender is required.', ''));
    }

    if (empty($this->home_phone) and empty($this->cell_phone)) {
        array_push($edit_errors,
            new edit_error('Either home or cell phone is required.', ''));
    }

    if (count($edit_errors) > 0) {
        $edit_errors_group = new edit_errors_group( array(
            'datatype' => __CLASS__,
            'row_descriptor' => '' . $this,
            'edit_errors' => $edit_errors
            ));
    }

    return $edit_errors_group;
}


//------------------------------------------------------------------------
static function mode_supported($verb) {
     if ($_SESSION['app_session']->is_superuser())
         return false;

    $session_user = $_SESSION['app_session']->get_app_user();
    $member_priv = $session_user->get_privilege(__CLASS__); 

    switch ($verb) {
        case 'create':
            return ($member_priv >= 2);

        case 'select':
        case 'update':
            return ($member_priv >= 1);

        default:
            return false;
    }
}


//------------------------------------------------------------------------
static function tabs_groups() {
    return array('member_tabs');
}


//------------------------------------------------------------------------
static function required_js_files() {
    return array('member.js', 'member_employment.js', 'date_entry.js',
                 'address.js', 'db_checker.js', 'ajax.js');
}


//------------------------------------------------------------------------
static function get_select_items($criteria=null) {
    $bloc = $_SESSION['app_session']->get_bahai_community();
    if (!$bloc)
        return null;

    $fmt_str = "SELECT person_label.person_id, label " .
               "FROM person_label, person " .
               "WHERE " .
               "person_label.person_id = person.person_id  AND " .
               "person.person_category = 1 AND " .
               "person.bahai_cmty_id = '%s' " .
               "ORDER BY label;";
    $query = sprintf($fmt_str, $bloc->get_key());

    $items = array();
    $res = app_session::pg_query($query);
    while ($row = pg_fetch_assoc($res)) {
        $items[ $row['person_id'] ] = $row['label'];
    }

   return $items;
}


function get_key() {
    return $this->person_id;
}


//------------------------------------------------------------------------
function __toString() {
    return $this->last_name . ', ' . $this->first_name;
}


//------------------------------------------------------------------------
static function type_long_name() {
    return BAHAI . " Member";
}


//------------------------------------------------------------------------
static function read_from_db($key) {
    $query = sprintf("SELECT * from member WHERE person_id = %d;", $key);
    $res = app_session::pg_query($query);
    $row = pg_fetch_assoc($res);
    if (!$row)   return NULL;
    $member = new member($row);

    if ($member->date_of_birth) {
        $member->age_category = $member->calc_age_category();
    }

    $cmty = $_SESSION['app_session']->get_bahai_community();
    $country_code = $cmty->get_country_code();

    // Load auxiliary data.

    if ($member->res_address_id) {
        $member->res_address = address::dispatch_read_from_db($country_code,
                $member->res_address_id);
    }


    if ($member->mailing_address_id) {
        $member->mailing_address = address::dispatch_read_from_db($country_code,
                $member->mailing_address_id);
    }

    $member->mailing_same =
            ($member->res_address_id == $member->mailing_address_id);

    if ($member->mailing_same)
        $member->mailing_address_status = '';   // SAME flag overrides changes


    if ($member->attorney_address_id) {
        $member->attorney_address = address::dispatch_read_from_db(
                $country_code, $member->attorney_address_id);
    }


    $query = sprintf("SELECT employer_name FROM member_employment " .
                     "WHERE person_id = %d;",
                     $key);
    $res = app_session::pg_query($query);
    while ($row = pg_fetch_assoc($res)) {
        $key = implode(KEY_SEPARATOR,
                       array($member->person_id, $row['employer_name']));
        $empl = member_employment::read_from_db($key);
        $empl->set_preferred(
                ($empl->employer_name == $member->preferred_phone_employer),
                ($empl->employer_name == $member->preferred_email_employer) );

        array_push($member->member_employment_places, $empl);
    }

    $member->emergency_contacts =
            emergency_contact::read_all_from_db($member->person_id);

    return $member;
}


//------------------------------------------------------------------------
function insert_to_db() {

    $bahai_cmty = $_SESSION['app_session']->get_bahai_community();

    $fmt_str = "SELECT insert_member('%s','%s','%s',%s,'%s','%s',%s,%s," .
               " %s,'%s','%s',%s,%s,%s,'%s',%s,%s);";

    $age_category = strtoupper($this->age_category);
    if (in_array($age_category, array('A','Y','J','C'))) {
        $age_category = "'$age_category'";
    }
    else {
        $age_category = 'NULL';
    }

    $query = sprintf($fmt_str,
        pg_escape_string($this->last_name),
        pg_escape_string($this->first_name),
        pg_escape_string($this->aka),
        (is_null($this->is_male) ? 'NULL' : ($this->is_male ? "'t'" : "'f'")),
        pg_escape_string($this->language),
        pg_escape_string($this->language_2nd),
        ($this->date_of_birth ?
            sprintf("CAST ('%s' as DATE)", $this->date_of_birth) : 'NULL'),
        'NULL',
        $bahai_cmty->get_key(),
        pg_escape_string($this->bahai_id_country),
        pg_escape_string($this->bahai_id),
        ($this->date_became_bahai ?
            sprintf("CAST ('%s' as DATE)", $this->date_became_bahai) : 'NULL'),
        (is_null($this->is_deprived) ? 'NULL' :
            ($this->is_deprived ? "'t'" : "'f'")),
        $age_category,
        pg_escape_string($this->occupation),
        (is_null($this->is_healthcare_provider) ? 'NULL' :
            ($this->is_healthcare_provider ? "'t'" : "'f'")),
        (is_null($this->edit_errors_group_id) ? 'NULL' :
         $this->edit_errors_group_id)
        );
    $res = app_session::pg_query($query);
    $id = pg_fetch_result($res, 0);

    $this->person_id = $id;


    $fmt_str = "SELECT update_member_contact(%d,'%s','%s','%s',%s,%s," .
               "'%s','%s','%s','%s');";
    $query = sprintf($fmt_str,
        $this->person_id,
        pg_escape_string($this->home_phone),
        pg_escape_string($this->cell_phone),
        pg_escape_string($this->fax_phone),
        ($this->primary_phone_choice ? $this->primary_phone_choice : 'NULL'),
        ($this->primary_email_choice ? $this->primary_email_choice : 'NULL'),
        pg_escape_string($this->preferred_phone_employer),
        pg_escape_string($this->preferred_email_employer),
        pg_escape_string($this->personal_email),
        pg_escape_string($this->personal_website_url)
        );
    $res = app_session::pg_query($query);
    // NEED MORE ??

    $this->update_child_objects_in_db();

/*
    $fmt_str = "SELECT update_member_legal(%d,'%s','%s','%s','%s','%s',%s);";

    $query = sprintf($fmt_str,
        $this->person_id,
        pg_escape_string($this->location_of_will),
        pg_escape_string($this->attorney_name),
        pg_escape_string($this->attorney_firm),
        pg_escape_string($this->attorney_phone),
        pg_escape_string($this->attorney_email),
        ($this->attorney_address_id ? $this->attorney_address_id : 'NULL')
        );
    $res = app_session::pg_query($query);
*/

    // NEED MORE ??

    return $id;
}


//------------------------------------------------------------------------
function update_in_db() {

    $bahai_cmty = $_SESSION['app_session']->get_bahai_community();

    $fmt_str = "SELECT update_member(%d, '%s','%s','%s',%s,'%s','%s'," .
               "CAST (%s as DATE), CAST (%s as DATE)," .
               "'%s',%s,%s);";
    $query = sprintf($fmt_str,
        $this->person_id,
        pg_escape_string($this->last_name),
        pg_escape_string($this->first_name),
        pg_escape_string($this->aka),
        (is_null($this->is_male) ? 'NULL' : ($this->is_male ? "'t'" : "'f'")),
        pg_escape_string($this->language),
        pg_escape_string($this->language_2nd),
        ($this->date_of_birth ? "'" . $this->date_of_birth . "'" : 'NULL'),
        'NULL',
        pg_escape_string($this->occupation),
        (is_null($this->is_healthcare_provider) ? 'NULL' :
            ($this->is_healthcare_provider ? "'t'" : "'f'")),
        (is_null($this->edit_errors_group_id) ? 'NULL' :
         $this->edit_errors_group_id)
        );
    $res = app_session::pg_query($query);


    $age_category = strtoupper($this->age_category);
    if (in_array($age_category, array('A','Y','J','C'))) {
        $age_category = "'$age_category'";
    }
    else {
        $age_category = 'NULL';
    }

    $fmt_str = "SELECT update_member_bahai(%d, '%s', '%s', %s, %s, %s);";
    $query = sprintf($fmt_str,
        $this->person_id,
        pg_escape_string($this->bahai_id_country),
        pg_escape_string($this->bahai_id),
        ($this->date_became_bahai ? "'" . $this->date_became_bahai . "'" :
            'NULL'),
        (is_null($this->is_deprived) ? 'NULL' :
            ($this->is_deprived ? "'t'" : "'f'")),
        $age_category);
    $res = app_session::pg_query($query);

    $fmt_str = "SELECT update_member_contact(%d,'%s','%s','%s',%s,%s," .
               "'%s','%s','%s','%s');";
    $query = sprintf($fmt_str,
        $this->person_id,
        pg_escape_string($this->home_phone),
        pg_escape_string($this->cell_phone),
        pg_escape_string($this->fax_phone),
        ($this->primary_phone_choice ? $this->primary_phone_choice : 'NULL'),
        ($this->primary_email_choice ? $this->primary_email_choice : 'NULL'),
        pg_escape_string($this->preferred_phone_employer),
        pg_escape_string($this->preferred_email_employer),
        pg_escape_string($this->personal_email),
        pg_escape_string($this->personal_website_url)
        );
    $res = app_session::pg_query($query);

    $this->update_child_objects_in_db();

/*
    $fmt_str = "SELECT update_member_legal(%d,'%s','%s','%s','%s','%s',%s);";

    $query = sprintf($fmt_str,
        $this->person_id,
        pg_escape_string($this->location_of_will),
        pg_escape_string($this->attorney_name),
        pg_escape_string($this->attorney_firm),
        pg_escape_string($this->attorney_phone),
        pg_escape_string($this->attorney_email),
        ($this->attorney_address_id ? $this->attorney_address_id : 'NULL')
        );
    $res = app_session::pg_query($query);
*/

    // NEED MORE ??

    return $this->person_id;
}


//------------------------------------------------------------------------
//
//------------------------------------------------------------------------
private function update_child_objects_in_db() {

    if ($this->res_address_status == 'insert') {
        $this->res_address_id = $this->res_address->insert_to_db();
        $query = sprintf("SELECT member_set_address('f',%d,%d);",
                    $this->person_id, $this->res_address_id);
        $res = app_session::pg_query($query);
    }
    else if ($this->res_address_status == 'update') {
        $this->res_address->update_in_db();
    }
    else if ($this->res_address_status == 'delete') {
        $this->res_address_id = null;
        $query = sprintf("SELECT member_clear_address('f', %d);",
                $this->person_id);
        $res = app_session::pg_query($query);
    }

    if ($this->mailing_same) {
        $this->mailing_address_id = $this->res_address_id;
    }
    else if ($this->mailing_address_status == 'insert') {
        $this->mailing_address_id = $this->mailing_address->insert_to_db();
    }
    else if ($this->mailing_address_status == 'update') {
        $this->mailing_address->update_in_db();
    }
    else if ($this->mailing_address_status == 'delete') {
        $this->mailing_address_id = null;
        $query = sprintf("SELECT member_clear_address('t', %d);",
                $this->person_id);
        $res = app_session::pg_query($query);
    }

    if ($this->mailing_address_id) {
        $query = sprintf("SELECT member_set_address('t',%d,%d);",
                $this->person_id, $this->mailing_address_id);
        $res = app_session::pg_query($query);
    }

    if ($this->attorney_address && $this->attorney_address_status) {
        if ($this->attorney_address_status == 'insert') {
            $this->attorney_address_id =
                    $this->attorney_address->insert_to_db();
        }
        else if ($this->attorney_address_status == 'update') {
            $this->attorney_address->update_in_db();
        }
    }


    $fmt_str = "SELECT update_member_legal(%d,'%s','%s','%s','%s','%s',%s);";
    $query = sprintf($fmt_str,
        $this->person_id,
        pg_escape_string($this->location_of_will),
        pg_escape_string($this->attorney_name),
        pg_escape_string($this->attorney_firm),
        pg_escape_string($this->attorney_phone),
        pg_escape_string($this->attorney_email),
        ($this->attorney_address_id ? $this->attorney_address_id : 'NULL')
        );
    $res = app_session::pg_query($query);


    foreach ( $this->member_employment_places as $empl) {
        $empl->set_person_id($this->person_id);
        $empl->process_in_db();
    }

    foreach ($this->emergency_contacts as $emcon) {
        $emcon->person_id = $this->person_id;
        if ($emcon->marked_for_delete()) {
            emergency_contact::delete_from_db($emcon->get_key());
        }
        else {
            $emcon->insert_update_in_db();
        }
    }

    // NEED MORE ??

}


//------------------------------------------------------------------------
static function delete_from_db($key) {
    $query = "SELECT delete_member('$key');";
    $res = app_session::pg_query($query);
}


//------------------------------------------------------------------------
static function delete_mailing_address($person_id) {
    $query = "SELECT member_set_address(CAST ('t' as boolean), " .
            "'$person_id', NULL);";
    $res = app_session::pg_query($query);
}


//------------------------------------------------------------------------
static private function get_name($fld) {
    return $fld['name'];
}


//---------------------------------------------------------------------------
public function calc_age_category() {
    if (!$this->date_of_birth)
        return null;

    $current_ts = strftime("%Y/%m/%d %H:%M", time());

    $query = sprintf(
            "SELECT calc_age_category('%s', CAST('%s' as timestamp), '%s');", 
            pg_escape_string($this->date_of_birth),
            $current_ts,
            $this->age_category);
    $res = app_session::pg_query($query);

    $age_cat = pg_fetch_result($res, 0);

    return $age_cat;
}


//---------------------------------------------------------------------------
//>>>  member::formatted_birthdate  <<<
//
//---------------------------------------------------------------------------
private function formatted_birthdate() {
    return $this->date_of_birth;
}


//---------------------------------------------------------------------------
//>>>  member::gen_display  <<<
//
//  This 
//---------------------------------------------------------------------------
static function gen_display(request $request, user_root_class $obj=null) {

    $html = '';

    $session_user = $_SESSION['app_session']->get_app_user();
    if (!$session_user) {  // superuser
        die("Shouldn't get here");
    }

    if ($request->mode == 'create' or $request->mode == 'update') {
        $html .= self::gen_entry_form($request, $obj);
        if ($request->mode == 'update') {
            $html .= self::goto_health_button($request, $obj);
        }
    }

    else {
        die("member: Shouldn't get to here (mode = '{$request->mode}').\n");
    }

    return $html;
}


//---------------------------------------------------------------------------
static function goto_health_button(request $request, user_root_class $obj=null) {
    return; // disable for now
?>

<p>
<FORM name='goto_health_form'>
  <input type='hidden' name='datatype' value='member_health' />
  <input type='hidden' name='key' value='<?= $obj->key ?>' />
  <input type='hidden' name='mode' value='update' />
  <input type='submit' value='Go to member health' />
</FORM>

<?php
}


//---------------------------------------------------------------------------
static function gen_selector() {

    $bahai_cmty = $_SESSION['app_session']->get_bahai_community();
    if (!$bahai_cmty)
        return null;

    $cap = dechex(person_popup::select_bit | person_popup::member_bit);
    $p_sel = new person_popup($cap, 'goto_member');
    $button_html = $p_sel->format_button("Select Member");

    $html = <<<MEMBER_SELECTOR_HTML

<script type='text/javascript'>
function goto_member(id,label) {
    go_to('member',id);
}
</script>

{$button_html}

MEMBER_SELECTOR_HTML;

    return $html;
}


//---------------------------------------------------------------------------
static function gen_entry_form(request $request, user_root_class $obj=null) {

    $member_tabs = new tabs_group('member_tabs', 'tabs');
    self::$pg_list = array('member_tabs');

    foreach (self::$sections as $section) {
        $html = call_user_func(array('self', 'format_' . $section),
                               $request, $obj);
        $member_tabs->add_pane(ucfirst($section), $html);
    }

    $session_user = $_SESSION['app_session']->get_app_user();
    $member_priv = $session_user->get_privilege(__CLASS__); 

    $js_init = $member_tabs->format_js_init();
    $tabs_html = $member_tabs->format_html();
    $buttons_html = self::gen_buttons_html(__CLASS__, $request->mode);
    $disable_html = ($member_priv < 2) ? "disable_form('member_entry');" : '';

    $fmt_str = <<<ENTRY_FORM_HTML

<FORM name='%s_entry' id='%s_entry' method='POST'
        onsubmit='javascript:return member_check(this);' >
<input type='hidden' name='datatype' value='%s'/>
<input type='hidden' name='mode' value='%s'/>
<input type='hidden' name='person_id' value='%s'/>
<input type='hidden' name='key' value='%s'/>
{$tabs_html}
<p>
{$buttons_html}
</FORM>

<SCRIPT type='text/javascript'>
{$js_init}
{$disable_html}

lang_check("language");
lang_check("language_2nd");

</SCRIPT>
ENTRY_FORM_HTML;

    $html = sprintf($fmt_str, 
        __CLASS__,
        __CLASS__,
        __CLASS__,
        $request->mode,
        ($obj ? $obj->person_id : ''),
        ($obj ? $obj->person_id : '')
        );

    return $html;
}


//---------------------------------------------------------------------------
static private function format_personal(request $request,
        user_root_class $obj=null) {

    $fmt_str = <<<PERSONAL_HTML
<table><tbody>
  <tr>        

  <td class='left_label'><br>Name:&nbsp;&nbsp;&nbsp;</td>

  <td>
    <label class='field_header' for='last_name'>Last</label><br>
    <input maxlength='30' size='20' name='last_name' id='last_name'
       value='%s'>
  </td>

  <td valign='bottom' style='font-size:xx-large'>&nbsp;,&nbsp;&nbsp;</td>

  <td>
    <label class='field_header' for='first_name'>First</label><br>
    <input maxlength='30' size='20' name='first_name' id='first_name'
       value='%s'/>
  </td>
  </tr>

  <tr>
  <td class='left_label'>Also known as:&nbsp;&nbsp;&nbsp;</td>

  <td>
    <input type='text' maxlength='40' size='30'
     name='aka' id='aka' value='%s'/>
  </td>

  </tr>


</tbody></table>

<table rules='none'><tbody>
  <tr>
  <td class='left_label'>
    <br><label for='gender'>Gender:</label>
  </td>

  <td>
  <table><tbody><tr><td>
    <td>
      <label class='field_header'>Male</label><br>
      <input value='male' name='gender' type='radio' %s />
    </td>

    <td>&nbsp;&nbsp;</td>

    <td>
      <label class='field_header'>Female</label><br>
      <input value='female' name='gender' type='radio' %s />
    </td>    
  </td></tr></tbody></table>
  </td>
  </tr>

  <tr>
  <td class='left_label'><br>Language:&nbsp;&nbsp;</td>

  <td>
    <label for='language'>primary</label><br>
      
    <select name="language" onChange='lang_check("language");'>
      %s
    </select>

    <input type='text' name='language_entry' size='12'
      value='%s' %s />
  </td>

  <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>

  <td>
    <label for='language'>secondary</label><br>
    <select name="language_2nd" onChange='lang_check("language_2nd");'>
      %s 
    </select>

    <input type='text' name='language_2nd_entry' size='12'
      value='%s' %s />
  </td>

  </tr>

  <tr>
  <td>&nbsp;</td>
  </tr>
      
  <tr>
  <td class='left_label'>Date of Birth:</td>
  <td colspan='2'>%s</td>
  </tr>

</table>
PERSONAL_HTML;


    $selected_value = null;
    if ($obj && $obj->language) {
        $selected_value = in_array($obj->language, self::$languages) ?
                $obj->language : 'other';
    }
    $language_opts_info = array();
    foreach (self::$languages as $lang) {
        $language_opts_info[$lang] = ucfirst($lang);
    }
    $language_opts_info['other'] = '(OTHER)';
    $language_options_html =
        html_utils::format_options($language_opts_info, $selected_value);

    $selected_value = null;
    if ($obj && $obj->language_2nd) {
        $selected_value = in_array($obj->language_2nd, self::$languages) ?
                $obj->language_2nd : 'other';
    }
    $language_2nd_opts_info = array('' => '(NONE)');
    foreach (self::$languages as $lang) {
        $language_2nd_opts_info[$lang] = ucfirst($lang);
    }
    $language_2nd_opts_info['other'] = '(OTHER)';
    $language_2nd_options_html = 
        html_utils::format_options($language_2nd_opts_info, $selected_value);


    // Date of Birth field
    $dob_entry = new date_entry('date_of_birth');
    if ($obj && $obj->date_of_birth) {
        $dob_entry->init_value($obj->date_of_birth);
    }
    $dob_entry->set_year_range_relative(-105,0);

    if ($obj) {
        $language_std = in_array($obj->language, self::$languages);
        $language_2nd_std = in_array($obj->language_2nd, self::$languages);

        $html = sprintf($fmt_str, 
                htmlspecialchars($obj->last_name, ENT_QUOTES),
                htmlspecialchars($obj->first_name, ENT_QUOTES),
                htmlspecialchars($obj->aka, ENT_QUOTES),
                ((!is_null($obj->is_male) && $obj->is_male) ? 'checked' : ''),
                ((!is_null($obj->is_male) && !$obj->is_male) ? 'checked' : ''),
                $language_options_html,
                $language_std ? '' : $obj->language,
                $language_std ? 'disabled' : '',
                $language_2nd_options_html,
                $language_2nd_std ? '' : $obj->language_2nd,
                $language_2nd_std ? 'disabled' : '',
                $dob_entry->format_date_field()
                );
    }

    else {
        $html = sprintf($fmt_str,
                '','','','','',
                $language_options_html, '', 'disabled',
                $language_2nd_options_html, '', 'disabled',
                $dob_entry->format_date_field()
                );
    }

    return $html;
}


//------------------------------------------------------------------
static private function format_contact(
        request $request,
        user_root_class $obj=null) {

    $website_html = website::format_url_field('personal_website_url', 
            ($obj ? $obj->personal_website_url : '') );

    $primary_phone_ch = $obj ? $obj->primary_phone_choice : '';
    $ph_options = array('1' => 'home', '2' => 'work', '3' => 'cell');
    $primary_phone_options =
        html_utils::format_options($ph_options, $primary_phone_ch);

    $primary_email_ch = $obj ? $obj->primary_email_choice : '';
    $em_options = array('1' => 'personal', '2' => 'work');
    $primary_email_options =
            html_utils::format_options($em_options, $primary_email_ch);


    $fmt_str = <<<CONTACT_HTML

<table rules='none'>
  <tr>
  <td class='left_label'><br>Phones:</td>
                
  <td> <!-- phones -->
    <table><tr>

      <td>
        <label for='home_phone' class='field_header'>home</label>
        <br>
        <input maxlength='12' size='12' name='home_phone' id='home_phone',
         value='%s'/>
      </td>

      <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>

      <td>
        <label for='cell_phone' class='field_header'>cell</label>
        <br>
        <input maxlength='12' size='12' name='cell_phone' id='cell_phone'
         value='%s'/>
      </td>

      <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
      <td>
        <label for='fax_phone' class='field_header'>fax</label>
        <br>
        <input maxlength='12' size='12' name='fax_phone' id='fax_phone'
         value='%s'/>
      </td>

      <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
            
      <td>
        <label for='primary_phone_choice' class='field_header'>primary</label>
        <br>
        <select name="primary_phone_choice" size="0">
          {$primary_phone_options}
        </select>
      </td>
            
    </tr>
    </table>
  </td>
            
  </tr>

  <tr>
    <td class='left_label'><br>Email:</td>
    <td>
      <table><tr>
        <td>
          <br>
          <input type="text" name="personal_email" size="20" maxlength="30"
           value='%s'/>
        </td>
        <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
        <td>
          <label for='primary_email_choice' class='field_header'>primary</label>
          <br>
          <select name="primary_email_choice" size="0">
            {$primary_email_options}
          </select>
        </td>
      </tr></table>
    </td>
  </tr>


  <tr>
    <td class='left_label'><br>Web site:</td>
    <td>
      <table><tr>
        <td>
          <br>
    {$website_html}
        </td>
      </tr></table>
    </td>
  </tr>

</table>

CONTACT_HTML;

    $html = sprintf($fmt_str, 
            ($obj ? $obj->home_phone : ''),
            ($obj ? $obj->cell_phone : ''),
            ($obj ? $obj->fax_phone : ''),
            ($obj ? pg_escape_string($obj->personal_email) : '')
            );

    return $html;
}


//------------------------------------------------------------------
static private function format_addresses(
        request $request,
        user_root_class $obj=null) {

    if ($obj && $obj->res_address) {
        $res_new_button = <<<RES_BUTTON_HTML
<br>
<input type='button' value='NEW' label='NEW'
 onclick='clear_res_address();'/>
RES_BUTTON_HTML;

    }
    else {
        $res_function = '';
        $res_new_button = '';
    }


    if ($obj && $obj->mailing_address) {

        $mailing_new_button = <<<MAILING_BUTTON_HTML
<br>
<input type='button' value='NEW' label='NEW'
 onclick='clear_mailing_address();'/>
MAILING_BUTTON_HTML;

    }
    else {
        $mailing_function = '';
        $mailing_new_button = '';
    }

    $cmty = $_SESSION['app_session']->get_bahai_community();
    $country_code = $cmty->get_country_code();

    $res_address_html = address::dispatch_format_fields($country_code,
            'res_', ($obj ? $obj->res_address : null) );
        
    $mailing_address_html = address::dispatch_format_fields($country_code,
            'mailing_', ($obj ? $obj->mailing_address : null) );

    $mailing_same_checked = ($obj && !$obj->mailing_same) ? '' : 'checked';


    $html = <<<ADDRESSES_HTML

<table rules='none'>

<!--  RESIDENCE  ADDRESS  -->
  <tr>
    <td valign='top'><br>Residence:&nbsp;&nbsp;<br><br>
      {$res_new_button}
    </td>
    <td>
      {$res_address_html}
    </td>
  </tr>

<!--   MAILING  ADDRESS  -->

  <tr>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td  class='left_label' valign='top'>Mailing:</td>
    <td>
      <input type='checkbox' name='mailing_same' {$mailing_same_checked}
          onclick='check_mailing();' />
      Mailing address same as residence address
    </td>
  </tr>

  <tr><td colspan='5'>
  <div id='mailing'>
  <table>

  <tr>
    <td valign='top' class='left_label'>
      <br>
      {$mailing_new_button}
    </td>

    <td>
      {$mailing_address_html}
</div>

<script type='text/javascript'>
check_mailing();
</script>

    </td>
   </tr>
   </table>

    </tbody>
  </table>

ADDRESSES_HTML;

    return $html;
}


//------------------------------------------------------------------
static private function format_bahai(
        request $request,
        user_root_class $obj=null) {

    $bahai_date_entry = new date_entry('date_became_bahai');
    if ($obj && $obj->date_became_bahai) {
        $bahai_date_entry->init_value($obj->date_became_bahai);
    }

    $bahai_date_entry->set_year_range_relative(-50,0);
    $bahai_date_html = $bahai_date_entry->format_date_field();

    $values['is_deprived_checked'] =
            ($obj && $obj->is_deprived) ? 'checked' : '';

    foreach (array('bahai_id_country', 'bahai_id') as $fld)  {
        $values[$fld] = $obj ? $obj->$fld : '';
    }

    $values['age_A_checked'] = '';
    $values['age_Y_checked'] = '';
    $values['age_J_checked'] = '';
    $values['age_C_checked'] = '';
    if ($obj && $obj->age_category)
        $values['age_' . $obj->age_category . '_checked'] = 'checked';

    $country_selected = $obj ? $obj->bahai_id_country : 'US';
    $country_options_html =
            html_utils::format_options(country::$options, $country_selected);

    $fmt_str = <<<BAHAI_HTML

<table>
<tr>
<td class='left_label'>Bahai ID country:</td>
<td>
<input type='text' name='bahai_id_country' id='bahai_id_country' readonly
 size='2' value='%s' style='border:0' />
&nbsp;&nbsp;(%s)
</td>
</tr>

<tr><td>&nbsp;</td></tr>

<tr>
<td class='left_label'>Bahai ID:</td>
<td>
  <input type="text" name="bahai_id" size="12" maxlength="20" value='%s'/>
</td>
</tr>

<tr><td>&nbsp;</td></tr>

<tr>
<td class='left_label'>Date became Bahai:</td>
<td>{$bahai_date_html}</td>
</tr>

<tr><td>&nbsp;</td></tr>

<tr>
<td class='left_label'><br>Age Category</td>
<td>
<table>
<tr>
<td>
  <label class='field_header'>Adult (&gt;=21)</label>&nbsp;&nbsp;&nbsp;<br>
  <input type="radio" name="age_category" value="A" %s/>
</td>
<td>
  <label class='field_header'>Youth (15-20)</label>&nbsp;&nbsp;&nbsp;<br>
  <input type="radio" name="age_category" value="Y" %s/>
</td>
<td>
  <label class='field_header'>Junior Youth (10-14)</label>&nbsp;&nbsp;&nbsp;<br>
  <input type="radio" name="age_category" value="J" %s/>
</td>
<td>
  <label class='field_header'>Child (&lt;10)</label>&nbsp;&nbsp;&nbsp;<br>
  <input type="radio" name="age_category" value="C" %s/>
</td>
</tr></table>
</td>
</tr>
</table>

BAHAI_HTML;

    $bahai_cmty = $_SESSION['app_session']->get_bahai_community();
    $country_code = $bahai_cmty->get_country_code();
    $country_name = country::$options[$country_code];

    $html = sprintf($fmt_str, 
         $country_code,
         htmlspecialchars($country_name, ENT_QUOTES),
         ($obj ? htmlspecialchars($obj->bahai_id, ENT_QUOTES) : ''),
         (($obj && $obj->age_category == 'A') ? 'checked' : ''),
         (($obj && $obj->age_category == 'Y') ? 'checked' : ''),
         (($obj && $obj->age_category == 'J') ? 'checked' : ''),
         (($obj && $obj->age_category == 'C') ? 'checked' : '')
         );

    return $html;
}


//------------------------------------------------------------------
static private function format_legal(
        request $request,
        user_root_class $obj=null) {

    $cmty = $_SESSION['app_session']->get_bahai_community();
    $country_code = $cmty->get_country_code();

    $attorney_address_html = address::format_fields($country_code, 
            'attorney_', ($obj ? $obj->attorney_address : null) );

    $fmt_str = <<<LEGAL_HTML

<table>

<tr>
<td class='left_label'> Location of Will:</td>
<td>
  <input type='text' name='location_of_will ' size='60' value='%s' />
</td>
</tr>

<tr><td>&nbsp;</td></tr>

<tr>
<td class='left_label'>Attorney Name:</td>
<td>
<input type='text' name='attorney_name' size='30' value='%s' />
</td>

<tr><td>&nbsp;</td></tr>

<tr>
<td class='left_label'>Attorney Firm:</td>
<td>
  <input type='text' name='attorney_firm' size='30' value='%s' />
</td>
</tr>

<tr><td>&nbsp;</td></tr>

<tr>
<td>Attorney Phone:</td>
<td>
  <input type='text' name='attorney_phone' size='12' value='%s' />
</td>
</tr>

<tr><td>&nbsp;</td></tr>

<tr>
<td>Attorney Email:</td>
<td>
  <input type='text' name='attorney_email' size='30' value='%s' />
</td>
</tr>

<tr><td>&nbsp;</td></tr>

<tr>
<td class='left_label' valign='top'><br>Attorney Address:</td>
<td>
{$attorney_address_html}
</td>
</tr>
</table>

LEGAL_HTML;

    $html = sprintf($fmt_str, 
        $obj ? pg_escape_string($obj->location_of_will) : '',
        $obj ? pg_escape_string($obj->attorney_name) : '',
        $obj ? pg_escape_string($obj->attorney_firm) : '',
        $obj ? pg_escape_string($obj->attorney_phone) : '',
        $obj ? pg_escape_string($obj->attorney_email) : ''
        );

    return $html;
}


//------------------------------------------------------------------
static private function format_employment(
        request $request,
        user_root_class $obj=null) {

    $box_html = member_employment::box_for_create_employer();

    $fmt_str = <<<EMPL_HEADER_HTML

<table style='padding-top:10px'>
<tr>
  <td class='left_label'>Occupation:</td>
  <td>
    <input type='text' name='occupation' size='30' value='%s'/>
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Healthcare provider ?
    <input type='checkbox' name='is_healthcare_provider' %s/>
  </td>
</tr>
<tr><td>&nbsp;</td></tr>
{$box_html}
</table>
<hr>

EMPL_HEADER_HTML;

    $html = sprintf($fmt_str, 
        ($obj ? $obj->occupation : ''),
        (($obj && $obj->is_healthcare_provider) ? 'CHECKED' : '')
        );

    $employers = ($obj && $obj->member_employment_places) ?
            $obj->member_employment_places : array();
    $html .= member_employment::format_section($employers);

    return $html;
}


//------------------------------------------------------------------
static private function format_emergency(
        request $request,
        user_root_class $obj=null) {

    $html = emergency_contact::format_section(
            ($obj ? $obj->person_id : null),
            ($obj ? $obj->emergency_contacts : array()) );

    return $html;
}

}

?>
