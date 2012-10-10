<?php  // $Id

class event extends user_root_class {

    public $event_id;

    public $event_type_code;
    public $event_session;

    public $event_address;
    public $event_address_id;
    public $event_address_status;

    public $event_start_ts;
    public $event_end_ts;
    public $bahai_cmty_id;
    public $host_bahai_cmty_id;
    public $description;
    public $notes;

    //public $event_notices;
    public $event_persons;

    public $event_counts;
    public $counts_combined;   // stash away old data for comparison


//------------------------------------------------------------------
static function privilege_levels_labels() {
    return array('none', 'read-only', 'modify');
}


//------------------------------------------------------------------------
function __construct(array $array_data) {

    $this->_copy_properties($array_data);

    $this->counts = new event_counts($array_data);

    if (array_key_exists('datatype', $array_data)) {   // from FORM input
        $this->construct_child_objects_from_form($array_data);
    }
}


//------------------------------------------------------------------------
private function construct_child_objects_from_form(array $array_data) {

    $cmty = $_SESSION['app_session']->get_bahai_community();
    $country_code = $cmty->get_country_code();

    if ($this->event_address_status) {
        $this->event_address = address::dispatch_construct($country_code, 
                $array_data, 'event_');

        if ($this->event_address->is_blank()) {
            $this->event_address = null;

            $this->event_address_status = 
                ($this->event_address_status == 'update') ? 'delete' : '';
        }
    }

    $this->event_persons = array();
    $evper_index = 1;
    $stem = sprintf('evper_%d_',$evper_index);
    while (array_key_exists($stem . 'person_id', $array_data)) {
        $evper_data = array();
        foreach (array('person_id','role','follow_up','follow_up_ts',
                       'follow_up_action') as $fld) {
            if (array_key_exists($stem . $fld, $array_data))
                $evper_data[$fld] = $array_data[$stem . $fld];
        }

        array_push($this->event_persons, new event_person($evper_data) );

        $stem = sprintf('evper_%d_', ++$evper_index);
    }

    $this->event_counts = new event_counts($array_data);
}


//------------------------------------------------------------------------
static function tabs_groups() {
    return array('event_tabs');
}


//------------------------------------------------------------------------
static function mode_supported($verb) {
     if ($_SESSION['app_session']->is_superuser())
         return false;

    $session_user = $_SESSION['app_session']->get_app_user();
    $member_priv = $session_user->get_privilege('event');

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
function check_user_data() {

}


//------------------------------------------------------------------------
static function get_select_items($criteria=null) {
    $cmty = $_SESSION['app_session']->get_bahai_community();
    $country_code = $cmty->get_country_code();

    $items = array();
    $query = sprintf("SELECT event_id,event_type_code," .
                     "event_address_id,event_start_ts".
             " FROM event " .
             " WHERE bahai_cmty_id = %d " .
             "ORDER BY event_type_code,event_start_ts;",
             $_SESSION['app_session']->get_bahai_community()->get_key() );

    $res = app_session::pg_query($query);
    while ($row = pg_fetch_assoc($res)) {
        if ($row['event_address_id']) {
            $addr = address::dispatch_read_from_db($country_code,
                    $row['event_address_id']);
            $addr_abbr = substr($addr, 0, 25);
        }
        else {
            $addr_abbr = '';
        }

        $items[$row['event_id']] = self::format_descriptor(
                $row['event_type_code'], 
                $row['event_start_ts'],
                $addr_abbr);
    }
    
    return $items;
}


//------------------------------------------------------------------
static function required_js_files() {
    return array('event.js', 'address.js', 'date_entry.js');
}



//------------------------------------------------------------------------
function get_key() {
    return $this->event_id;
}


//------------------------------------------------------------------------
static function format_descriptor(
         $event_type_code, $event_start_ts, $addr_abbr)  {

    $ts = '';
    if ($event_start_ts) {
        list($year,$month,$day,$hour,$minute) =
            sscanf($event_start_ts, "%d-%d-%d %d:%d");

        $am_pm = ($hour < 12) ? 'AM' : 'PM';
        $hour = ($hour >= 12) ? $hour-12 : ($hour == 0 ? 12 : $hour);

        $ts = sprintf("%04d-%02d-%02d %02d:%02d %s",
            $year,$month,$day,$hour,$minute, $am_pm);
    }

    return sprintf("%s: %s%s%s",
            $event_type_code, $ts, $addr_abbr ? ' @ ' : '', $addr_abbr);
}



//------------------------------------------------------------------------
function __toString() {

    $addr_abbr = $this->event_address ?
            substr($this->event_address, 0, 15) : '';

    return self::format_descriptor(
            $this->event_type_code,
            $this->event_start_ts,
            $addr_abbr);
}


//------------------------------------------------------------------------
static function type_long_name() {
    return BAHAI . " Event";
}


//------------------------------------------------------------------------
static function read_from_db($key) {
    $query = sprintf("SELECT * from event WHERE event_id = %d;", $key);
    $res = app_session::pg_query($query);
    $row = pg_fetch_assoc($res);
    if (!$row)   return NULL;

    $event = new event($row);

    // Load auxiliary data.
    $cmty = $_SESSION['app_session']->get_bahai_community();
    $country_code = $cmty->get_country_code();

    $event->event_persons = event_person::read_all_from_db($key);
    $event->event_address = address::dispatch_read_from_db($country_code, 
                                $event->event_address_id);

    $event->event_counts = event_counts::read_from_db($key);

    return $event;
}


//------------------------------------------------------------------------
function insert_to_db() {

    //$bahai_cmty = $_SESSION['app_session']->get_bahai_community();
    //$this->bahai_cmty_id = $bahai_cmty->get_key();

    $this->handle_event_address();

    $fmt_str = "SELECT insert_event('%s',%s,%s,CAST (%s as TIMESTAMP)," .
               "CAST (%s as TIMESTAMP),%s,%s,'%s','%s');";

    $query = sprintf($fmt_str, 
            pg_escape_string($this->event_type_code),
            ($this->event_session ? $this->event_session : 'NULL'),
            ($this->event_address_id ? $this->event_address_id : 'NULL'),
            ($this->event_start_ts ? "'{$this->event_start_ts}'" : 'NULL'),
            ($this->event_end_ts ? "'{$this->event_end_ts}'" : 'NULL'),
            ($this->bahai_cmty_id ? $this->bahai_cmty_id : 'NULL'),
            ($this->host_bahai_cmty_id ? $this->host_bahai_cmty_id : 'NULL'),
            pg_escape_string($this->description),
            pg_escape_string($this->notes) );

    $res = app_session::pg_query($query);
    $this->event_id = pg_fetch_result($res, 0);

    $this->handle_attendees();
    $this->handle_event_counts();

    return $this->event_id;
}


//------------------------------------------------------------------------
function handle_attendees() {

    foreach ($this->event_persons as $evper) {
        $evper->event_id = $this->event_id;
        if ($evper->marked_for_delete()) {
            event_person::delete_from_db($evper->get_key());
        }
        else {
            $evper->insert_update_in_db();
        }
    }
}


//------------------------------------------------------------------------
function handle_event_counts() {
    $updated_counts_combined = $this->event_counts->format_counts_combined();
    if ($updated_counts_combined != $this->counts_combined) {
        $this->event_counts->event_set_counts_in_db();
    }
}


//------------------------------------------------------------------------
function handle_event_address() {

    if ($this->event_address_status) {

        if ($this->event_address_status == 'insert') {
            $this->event_address_id = $this->event_address->insert_to_db();
        }
        else if ($this->event_address_status == 'update') {
            $this->event_address->update_in_db();
        }
    }
}


//------------------------------------------------------------------------
function update_in_db() {

    //$bahai_cmty = $_SESSION['app_session']->get_bahai_community();

    $this->handle_event_address();
    $this->handle_attendees();
    $this->handle_event_counts();

    $fmt_str = "SELECT update_event(%d,'%s',%s,%s,CAST (%s as TIMESTAMP)," .
               "CAST (%s as TIMESTAMP),%s,%s,'%s','%s');";

    $query = sprintf($fmt_str, 
            $this->event_id,
            pg_escape_string($this->event_type_code),
            ($this->event_session ? $this->event_session : 'NULL'),
            ($this->event_address_id ? $this->event_address_id : 'NULL'),
            ($this->event_start_ts ? "'{$this->event_start_ts}'" : 'NULL'),
            ($this->event_end_ts ? "'{$this->event_end_ts}'" : 'NULL'),
            ($this->bahai_cmty_id ? $this->bahai_cmty_id : 'NULL'),
            ($this->host_bahai_cmty_id ? $this->host_bahai_cmty_id : 'NULL'),
            pg_escape_string($this->description),
            pg_escape_string($this->notes) );

    $res = app_session::pg_query($query);

    return $this->event_id;
}


//------------------------------------------------------------------------
static function delete_from_db($key) {
    $query = "SELECT delete_event($key);";
    $res = app_session::pg_query($query);
}


//---------------------------------------------------------------------------
static function gen_selector() {

    return null;
}


//---------------------------------------------------------------------------
static function gen_display(request $request,
        user_root_class $obj=null) {

    $event_tabs = new tabs_group('event_tabs');
    $event_tabs->add_pane('General', self::format_general($request, $obj));
    $event_tabs->add_pane('Description',
                          self::format_description($request, $obj));
    //$event_tabs->add_pane('Notices', self::format_notices($request, $obj));
    $event_tabs->add_pane('People', self::format_people($request, $obj));
    $event_tabs->add_pane('Counts', self::format_counts($request, $obj));

    $update_html = '';
    if ($request->mode == 'update') {
        $fmt_str = <<<UPDATE_HTML
<input type='hidden' name='event_id' value='%s'/>
<input type='hidden' name='key' value='%s'/>
UPDATE_HTML;
        $update_html = sprintf($fmt_str, $obj->event_id, $obj->event_id);
    }

    $buttons_html = self::gen_buttons_html(__CLASS__, $request->mode);

    $ts = new date_entry('evper_@_follow_up_ts',true);
    $ts->set_year_range_relative(-1,6);
    $ts_html = $ts->format_date_field();
    $assign_html =
            html_utils::assign_to_js_var('follow_up_ts_template', $ts_html);


    $fmt_str = <<<FORM_HTML

    <SCRIPT type='text/javascript'>
{$assign_html}
    </SCRIPT>

    <FORM name='%s_entry' id='%s_entry' method='POST'
        onsubmit='javascript:return event_check(this);'>
    <input type='hidden' name='datatype' value='%s'/>
    <input type='hidden' name='mode' value='%s' />
    $update_html
    %s
    <p>
    {$buttons_html}
    </FORM>

    <script type='text/javascript'>
    %s
    </script>

FORM_HTML;

    $html = sprintf($fmt_str, 
            __CLASS__,
            __CLASS__,
            __CLASS__,
            $request->mode,
            $event_tabs->format_html(),
            $event_tabs->format_js_init()
            );

    return $html;
}


/***********  SECTIONS **********
Below are several functions for formatting the forms (with content if applies)
for the sections of the event data.

Note that the HTML is formatted with syntax tags where to insert the values.


*/


//---------------------------------------------------------------------------
static private function format_general(request $request,
        user_root_class $obj=null) {

    $cmty = $_SESSION['app_session']->get_bahai_community();
    $country_code = $cmty->get_country_code();

    $type_selector_html = file_get_contents('event_type_selector.html');
    if ($obj && $obj->event_type_code) {
        $type_selector_html .= <<<SELECTOR_HTML
<SCRIPT type='text/javascript'>
    document.getElementById('event_type_code').value =
        '{$obj->event_type_code}';
</SCRIPT>
SELECTOR_HTML;
    }

    $event_start_ts_widget = new date_entry('event_start_ts', '-', true);
    $event_start_ts_widget->set_year_range_relative(-1,3);
    if ($obj && $obj->event_start_ts) {
        $event_start_ts_widget->init_value($obj->event_start_ts);
    }
    else {
        $event_start_ts_widget->init_date_to_current();
    }
    $event_start_ts_html = $event_start_ts_widget->format_date_field();

    $event_end_ts_widget = new date_entry('event_end_ts', '-', true);
    $event_end_ts_widget->set_year_range_relative(-1,3);
    if ($obj && $obj->event_end_ts) {
        $event_end_ts_widget->init_value($obj->event_end_ts);
    }
    else {
        $event_end_ts_widget->init_date_to_current();
    }
    $event_end_ts_html = $event_end_ts_widget->format_date_field();

    $raw_loc_options = bahai_community::read_select_options();

    $loc_label = bahai_community::type_long_name();
    $loc_options_html = html_utils::format_options($raw_loc_options,
            $obj ? $obj->bahai_cmty_id : null);
    $loc_selector = '';

    $host_loc_label = BAHAI . ' Host Community';
    $host_loc_options_html = html_utils::format_options($raw_loc_options,
            $obj ? $obj->host_bahai_cmty_id : null);
    $host_loc_selector = '';

    $event_address_html = address::dispatch_format_fields($country_code, 
            'event_', ($obj ? $obj->event_address : null) );

    $fmt_str = <<<EVENT_GENERAL_HTML
<TABLE>
<tr>
  <td>Event Type<br>{$type_selector_html}</td>
  <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
  <td>Session<br>
    <input type='text' name='event_session' size='4' value='%s'/>
    <input type='hidden' name='bahai_cmty_id' value='%d'/>
  </td>
  <td>
    
  </td>
</tr>
<tr>
  <td colspan='3'>Start<br>$event_start_ts_html</td>
</tr>
<tr>
  <td>End<br>$event_end_ts_html</td>
</tr>
<tr>
  <td colspan='5'>
    {$event_address_html}
  </td>
</tr>

</TABLE>

EVENT_GENERAL_HTML;

    $html = sprintf($fmt_str, ($obj ? $obj->event_session : ''),
            $_SESSION['app_session']->get_bahai_community()->get_key() );

    return $html;

/*  DELETED:

<tr>
  <td>{$loc_label}<br>
    <SELECT name='bahai_cmty_id'>
    <OPTION value=''>(Select Community)</OPTION>
    {$loc_options_html}
    </SELECT>
  </td>
  <td colspan='2'>{$host_loc_label}<br>
    <SELECT name='host_bahai_cmty_id'>
    <OPTION value=''>(Select Community)</OPTION>
    {$host_loc_options_html}
    </SELECT>
  </td>
</tr>

*/

}


//------------------------------------------------------------------
static private function format_description(request $request,
        user_root_class $obj=null) {

    $html = sprintf("<TEXTAREA name='description' rows='20' cols='80'>" .
                    "%s</TEXTAREA>\n",
            $obj ? htmlspecialchars($obj->description, ENT_QUOTES) : '');

    return $html;
}


/*
//------------------------------------------------------------------
static private function format_notices(request $request,
        user_root_class $obj=null) {

    $html = <<<EVENT_NOTICE_HTML

<TEXTAREA name='event_notice' rows='24' cols='60'>
</TEXTAREA>

EVENT_NOTICE_HTML;

    return $html;
}
*/


//------------------------------------------------------------------
static private function format_people(request $request,
        user_root_class $obj=null) {

    $html = event_person::format_section(
            ($obj ? $obj->event_id : null),
            ($obj ? $obj->event_persons : array()) );

    return $html;
}


//------------------------------------------------------------------
static private function format_counts(request $request,
        user_root_class $obj=null) {

    $html = event_counts::format_fields($obj ? $obj->event_counts : null);

    return $html;
}

}

?>
