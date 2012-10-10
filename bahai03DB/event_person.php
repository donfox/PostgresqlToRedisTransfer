<?php

class event_person extends auto_construct implements type_in_db {

    public $event_id;
    public $person_id;
    public $role;
    public $follow_up;
    public $follow_up_ts;
    public $follow_up_action;


    //---------------------------------------------------------------
    function __construct(array $array_data) {
        $this->_copy_properties($array_data);

        $this->follow_up = false;   // *********** MUST CHANGE ************
    }


    //---------------------------------------------------------------
    function marked_for_delete() {
        return !$this->role;
    }


    //---------------------------------------------------------------
    static function read_from_db($key) {
        list($event_id, $person_id) = explode(KEY_SEPARATOR, $key);
        $query = sprintf("SELECT * from event_person WHERE " .
                         "event_id = %d AND person_id = %d;",
                         $event_id, $person_id);
        $res = app_session::pg_query($query);
        $row = pg_fetch_assoc($res);
        if (!$row)   return NULL;

        return new event_person($row);
    }


    //---------------------------------------------------------------
    //  Read all event_person rows for an event.
    //---------------------------------------------------------------
    static function read_all_from_db($event_id) {
        $evps = array();
        $query = sprintf("SELECT * from event_person WHERE event_id = %d;",
                         $event_id);
        $res = app_session::pg_query($query);
        while ($row = pg_fetch_assoc($res)) {
            array_push($evps, new event_person($row));
        }

        return $evps;
    }


    //---------------------------------------------------------------
    function insert_to_db() {
        $this->insert_update_in_db();
        return $this->key;
    }


    //---------------------------------------------------------------
    function update_in_db($edit_errors_group_id=null) {
        $this->insert_update_in_db();
        return $this->key;
    }


    //---------------------------------------------------------------
    function insert_update_in_db() {
        $query = sprintf("SELECT insert_update_event_person(%d,%d,'%s'," .
            "CAST ('%s' as bool), CAST (%s as TIMESTAMP), '%s');",
                $this->event_id,
                $this->person_id,
                pg_escape_string($this->role),
                ($this->follow_up ? 't' : 'f'),
                ($this->follow_up_ts ?
                     "'" . $this->follow_up_ts . "'" : 'NULL'),
                pg_escape_string($this->follow_up_action) 
                );
        $res = app_session::pg_query($query);
    }


    //---------------------------------------------------------------
    function __toString() {
        return $this->event_id . ':' . $this->person_id;
    }


    //---------------------------------------------------------------
    function get_key() {
        return '' . $this;
    }


    //---------------------------------------------------------------
    static function delete_from_db($key) {
        list($event_id, $person_id) = explode(KEY_SEPARATOR, $key);
        $query = sprintf("SELECT delete_event_person(%d,%d);",
            $event_id, $person_id);
        $res = app_session::pg_query($query);
    }


    //---------------------------------------------------------------
    static function format_section($event_id, array $event_persons) {

        $rows_html = '';
        $index = 1;
        foreach($event_persons as $ev_per) {
            $rows_html .= sprintf("<TR>\n%s\n</TR>\n",
                                  $ev_per->format_row($index++) );
        }
        $bahai_label = BAHAI;

        $html = <<<EVENT_PERSON_TABLE_HTML
<TABLE id='event_persons_table' border='1' cellpadding='7'>

<tr>
  <th colspan='2'>
    Attendee
  </th>
  <th colspan='3'>
    Follow-up
  </th>
</tr>

<tr>
  <th>
    Name ({$bahai_label} status)
  </th>
  <th>
    Role
  </th>
  <th>
    Y/N
  </th>
  <th>
    Action
  </th>
  <th>
    Time
  </th>
</tr>
{$rows_html}

</TABLE>
EVENT_PERSON_TABLE_HTML;

        $cap = person_popup::select_bit |
               person_popup::multi_bit |
               person_popup::member_bit |
               person_popup::create_bit |
               person_popup::guest_bit |
               person_popup::seeker_bit |
               person_popup::external_bit;
        $per_sel = new person_popup($cap, 'add_event_person');
        $per_sel_html = $per_sel->format_button("OPEN ATTENDEES SELECTOR");

        $html .= <<<APPENDER_HTML

<SCRIPT type='text/javascript'>
event_person_index = {$index};
</SCRIPT>

{$per_sel_html}

APPENDER_HTML;

        return $html;
    }


    //---------------------------------------------------------------
    //  Format a row for pre-existing event person (read from database).
    //---------------------------------------------------------------
    function format_row($index) {
        $follow_up_ts_fld = new date_entry("evper_{$index}_follow_up_ts", true);
        $follow_up_ts_fld->set_year_range_relative(-1,6);
        if ($this->follow_up_ts) {
            $follow_up_ts_fld->init_value($this->follow_up_ts);
        }
        $ts_fld_html = $follow_up_ts_fld->format_date_field();

        $label = person::read_label($this->person_id);

        $fmt_str = <<<EVENT_PERSON_ROW_HTML
  <td>%s</td>
  <td>
     <INPUT type='hidden' name='evper_@_person_id' value='%s'/>
     <SELECT name='evper_@_role'>
       <OPTION %s value='attendee'>attendee</OPTION>
       <OPTION %s value='host'>host</OPTION>
       <OPTION %s value='tutor'>tutor</OPTION>
       <OPTION %s value='facilitator'>facilitator</OPTION>
       <OPTION value=''>(DELETE)</OPTION>
     </SELECT>
  </td>
  <td>
    <INPUT type='checkbox' name='evper_@_follow_up' %s/>
  </td>
  <td>
    <INPUT type='text' name='evper_@_follow_up_action' value='%s' size='40'/>
  </td>
  <td>
    %s
  </td>

EVENT_PERSON_ROW_HTML;

        $html = sprintf($fmt_str,
            htmlspecialchars($label, ENT_QUOTES),
            htmlspecialchars($this->person_id, ENT_QUOTES),
            ($this->role == 'attendee' ? 'SELECTED' : ''),
            ($this->role == 'host' ? 'SELECTED' : ''),
            ($this->role == 'tutor' ? 'SELECTED' : ''),
            ($this->role == 'facilitator' ? 'SELECTED' : ''),
            ($this->follow_up ? 'CHECKED' : ''),
            htmlspecialchars($this->follow_up_action, ENT_QUOTES), 
            $ts_fld_html);

        $html = str_replace('@', $index, $html);

        return $html;
    }

};
