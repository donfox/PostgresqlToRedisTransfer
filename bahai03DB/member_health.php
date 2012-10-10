<?php  // $Id

class member_health extends auto_construct implements type_in_db {

    static private $sections = array();
    static private $member_health_tg;

    public $person_id;
    public $health_provider_id;   // indicates primary provider

    public $health_providers;

    static public $max_healthcare_providers = 4;

    static public $max_medical_conditions = 3;
    public $medical_conditions;

    // *** EMERGENCY information ***
    //   Rows in emergency_contact


//------------------------------------------------------------------------
function __construct(array $array_data) {

    parent::_init_props($array_data);

    if (!$this->person_id and $this->key) {
        $this->person_id = $this->key;
    }

    if (isset($this->is_male) and !is_null($this->is_male)) {
        $this->is_male = ($this->is_male == 't' or $this->is_male == 'on');
    }

    else if (isset($this->extra_fields['gender'])) {
       $this->is_male = ($this->extra_fields['gender'] == 'male');
    }

    if (!is_bool($this->is_guest)) {
        $this->is_guest = ($this->is_guest == 't' or $this->is_guest == 'on');
    }
}


//------------------------------------------------------------------------
static function pulldown_can_support_verb($verb) {
     if ($_SESSION['app_session']->is_superuser())
         return false;

     return ($verb == 'create' || $verb == 'update');
}


//------------------------------------------------------------------------
static function get_select_items($criteria=null) {
    return null;
}


//------------------------------------------------------------------------
static function gen_html_head_content(request $request) {
    if ($request->mode == 'create' || $request->mode == 'update') {
        print( pane_group::format_html_header(
            array('empl_', 'member_pg', 'hp_', 'mc_')) );
    }
}


function get_key() {
    return $this->person_id;
}


function __toString() {
    return $this->last_name . ', ' . $this->first_name;
}


//------------------------------------------------------------------------
static function type_long_name() {
    return BAHAI . " member";
}


//------------------------------------------------------------------------
static function read_from_db($key) {
    $query = sprintf("SELECT * from member WHERE person_id = %d;", $key);
    $res = app_session::pg_query($query);
    $row = pg_fetch_array($res);
    if (!$row)   return NULL;

    $member = new member($row);

    // Load auxiliary data.
    if ($member->res_address_id) {
        $member->res_address =
            address::read_from_db($member->res_address_id);
    }


    if ($member->mailing_address_id) {
        $member->mailing_address =
            address::read_from_db($member->mailing_address_id);
    }

    return $member;
}


function insert_to_db() {
    $types = array();
    $values = array();

    $bahai_cmty = $_SESSION['app_session']->get_bahai_community();

    array_push($types, self::sql_type('bahai_cmty_country_code'));
    array_push($values, $bahai_cmty->get_country_code());

    array_push($types, self::sql_type('bahai_cmty_code'));
    array_push($values, $bahai_cmty->get_bahai_cmty_code());

    foreach(array('bahai_id_country', 'bahai_id', 'is_guest',
                  'last_name', 'first_name', 
                  'occupation', 'is_male', 'date_of_birth',
                  'home_phone', 'mobile_phone', 'work_phone', 'fax_phone', 
                  'email', 'emr_name', 'emr_phone')  as $col) {
        array_push($types, self::sql_type($col));
        array_push($values, $this->$col);
    }

    $query = "SELECT insert_member" .
        type_in_db::format_sql_values_list($types, $values) . ";";

    $res = app_session::pg_query($query);
    $id = pg_fetch_result($res, 0);

    return $id;
}


function update_in_db() {
    $types = array();
    $values = array();
    foreach(array('person_id', 
                  'bahai_id_country', 'bahai_id', 'is_guest',
                  'last_name', 'first_name', 
                  'occupation', 'is_male', 'date_of_birth',
                  'home_phone', 'mobile_phone', 'work_phone', 'fax_phone', 
                  'email', 'emr_name', 'emr_phone')  as $col) {
        array_push($types, self::sql_type($col));
        array_push($values, $this->$col);
    }
    $query = "SELECT update_member" .
        type_in_db::format_sql_values_list($types, $values) . ";";

    $res = app_session::pg_query($query);

    return $this->person_id;
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


//------------------------------------------------------------------------
static function process_post_data() {

    // Close to standard handling.
    if (in_array($_POST['mode'], array('insert','update','delete'))) {
        $new_req = parent::process_post_data();
        if ($_POST['mode'] != 'delete' and !$new_req->error) {
            $new_req->mode = 'display';
        }
        return $new_req;
    }

    try {
        switch ($_POST['mode']) {
            case 'delete_mailing_address':
                $key = $_POST['person_id'];
                self::delete_mailing_address($key);
                break;
    
            case 'propagate':
                self::process_propagate();
                $key = $_POST['person_id'];
                break;
        }
 
        $new_req = new request( array(
                'success' => "Successful Transaction",
                'datatype' => $_POST['datatype'],
                'mode' => 'display',
                'key' => $key ) );
    }

    catch (db_exception $e) {
        $new_req = new request( array(
                'error' => $e->getMessage(),
                'datatype' => $_POST['datatype'],
                'mode' => $_POST['mode'],
                'key' => $key ) );

        $new_req = new request($_POST);
        $new_req->error = $e->getMessage();
    }

    return $new_req;
}


//---------------------------------------------------------------------------
//
//---------------------------------------------------------------------------
function field_attributes($fld_name) {

    if ($this and !is_null($this->$fld_name)) {
        print(" value='" . $this->$fld_name . "' ");
    }
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
static function gen_display(request $request, $obj) {

    if ($request->mode == 'create' or $request->mode == 'update') {
        self::gen_entry_form($request, $obj);
    }

    else if ($request->mode == 'propagate') {
        $member = self::read_from_db($request->key);
        $member->gen_propagate_form($request);
    }
    else {
        die("member_health: Shouldn't get to here.\n");
    }
}



//---------------------------------------------------------------------------
static private function gen_entry_form(request $request, $obj) {

    self::$js_code = '';
    self::$pg_list = array('member_pg');

    foreach (self::$sections as $section) {
        $html = call_user_func(array('self', 'format_' . $section),
                               $request, $obj);
        self::$member_pg->add_pane(ucfirst($section), $html);
    }
    self::$js_code .= self::$member_pg->format_js_init();

?>
    <FORM name='member_entry' method='POST'>
    <?php print( self::$member_pg->format_html() ); ?>

    <p>
    <input type='submit' value='Save Member'/>

<?php if ($request->mode == 'update') : ?>
    &nbsp;&nbsp;
    <button name='mode' value='delete'>Delete Member</button>
<?php endif; ?>

    </FORM>

    <script type='text/javascript'>
    <?= self::$js_code ?>
    </script>

<?php
}


//------------------------------------------------------------------
static private function format_health(request $request, $obj) {

    array_push(self::$pg_list,  'hp_');
    $hp_pg = new pane_group('hp_', 'expand');
    $hp_pg->set_can_delete(true);
    $hp_pg->set_type_label('Healthcare<br>Providers');

    $num_hp = $obj ? count($obj->healthcare_providers) : 0;
    for ($i=0; $i<self::$max_healthcare_providers; ++$i) {
        $onchange = $hp_pg->format_selector_onchange_code($i+1);
        if ($i<$num_hp) {
            $obj = $this->healthcare_providers[$i];
            $sel_label = $obj->get_descriptor();
        }
        else {
            $obj = null;
            $sel_label = '';
        }

        $prefix = 'hp_' . ($i+1) . '_';
        $e_html = healthcare_provider::format_fields($prefix, $obj, $onchange);

        $hp_pg->add_pane($sel_label, $e_html);
    }
    $hp_html = $hp_pg->format_html();
    self::$js_code .= $hp_pg->format_js_init();

    array_push(self::$pg_list,  'mc_');
    $mc_pg = new pane_group('mc_', 'expand');
    $mc_pg->set_can_delete(true);
    $mc_pg->set_type_label('Medical<br>Conditions');

    $num_mc = $obj ? count($obj->medical_conditions) : 0;
    for ($i=0; $i<self::$max_medical_conditions; ++$i) {
        $onchange = $mc_pg->format_selector_onchange_code($i+1);
        if ($i<$num_mc) {
            $obj = $this->medical_conditions[$i];
            $sel_label = $obj->get_descriptor();
        }
        else {
            $obj = null;
            $sel_label = '';
        }

        $prefix = 'mc_' . ($i+1) . '_';
        $e_html = medical_condition::format_fields($prefix, $obj, $onchange);

        $mc_pg->add_pane($sel_label, $e_html);
    }
    $mc_html = $mc_pg->format_html();
    self::$js_code .= $mc_pg->format_js_init();
    
    $health_html = <<<HEALTH_HTML

<table>
<tr><td>
{$hp_html}
</td></tr>
<tr><td>
{$mc_html}
</td></tr>
</table>

HEALTH_HTML;
    
    return $health_html;
}

}

?>
