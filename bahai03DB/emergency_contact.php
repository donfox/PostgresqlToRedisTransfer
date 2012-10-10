<?php

class emergency_contact extends auto_construct implements type_in_db {

    public $person_id;
    public $rel_person_id;
    public $relationship;


    //---------------------------------------------------------------
    function __construct(array $array_data) {
        $this->_copy_properties($array_data);
    }


    //---------------------------------------------------------------
    function marked_for_delete() {
        return !$this->relationship;
    }


    //---------------------------------------------------------------
    static function read_from_db($key) {
        list($person_id, $rel_person_id) = explode(KEY_SEPARATOR, $key);
        $query = sprintf("SELECT * from emergency_contact WHERE " .
                         "person_id = %d AND rel_person_id = %d;",
                         $person_id, $rel_person_id);
        $res = app_session::pg_query($query);
        $row = pg_fetch_assoc($res);
        if (!$row)   return NULL;

        return new emergency_contact($row);
    }


    //---------------------------------------------------------------
    //  Read all emergency_contact rows for a member.
    //---------------------------------------------------------------
    static function read_all_from_db($person_id) {
        $evps = array();
        $query = sprintf("SELECT * from emergency_contact " . 
                         "WHERE person_id = %d;",
                         $person_id);
        $res = app_session::pg_query($query);
        while ($row = pg_fetch_assoc($res)) {
            array_push($evps, new emergency_contact($row));
        }

        return $evps;
    }


    //---------------------------------------------------------------
    function insert_to_db() {
        $this->insert_update_in_db();
        return $this->key;
    }


    //---------------------------------------------------------------
    function update_in_db() {
        $this->insert_update_in_db();
        return $this->key;
    }


    //---------------------------------------------------------------
    function insert_update_in_db() {
        $query =
            sprintf("SELECT insert_update_emergency_contact(%d,%d,'%s');",
                $this->person_id,
                $this->rel_person_id,
                pg_escape_string($this->relationship)
                );
        $res = app_session::pg_query($query);
    }


    //---------------------------------------------------------------
    function __toString() {
        return $this->person_id . ':' . $this->rel_person_id;
    }


    //---------------------------------------------------------------
    function get_key() {
        return $this->person_id . KEY_SEPARATOR . $this->rel_person_id;
    }


    //---------------------------------------------------------------
    static function delete_from_db($key) {
        list($person_id, $rel_person_id) = explode(KEY_SEPARATOR, $key);
        $query = sprintf("SELECT delete_emergency_contact(%d,%d);",
            $person_id, $rel_person_id);
        $res = app_session::pg_query($query);
    }


    //---------------------------------------------------------------
    static function format_section($person_id, $emergency_contacts) {

        $rows_html = '';
        $index = 1; 
        if ($emergency_contacts) {
            foreach($emergency_contacts as $emer) {
                $rows_html .= sprintf("<TR>\n%s\n</TR>\n",
                                      $emer->format_row($index++) );
            }
        }

        $html = <<<EMERGENCY_CONTACT_TABLE_HTML
<TABLE id='emergency_contacts_table' border='1' cellpadding='7'>

<tr>
  <th>
    Contact
  </th>
  <th>
    Relationship
  </th>
</tr>

{$rows_html}

</TABLE>
EMERGENCY_CONTACT_TABLE_HTML;

        $cap = person_popup::select_bit |
               person_popup::multi_bit |
               person_popup::member_bit |
               person_popup::create_bit |
               person_popup::guest_bit |
               person_popup::seeker_bit |
               person_popup::external_bit;
        $per_sel = new person_popup($cap, 'add_emergency_contact');
        $per_sel_html = $per_sel->format_button("OPEN PERSON SELECTOR");

        $html .= <<<APPENDER_HTML

<SCRIPT type='text/javascript'>
emerg_index = {$index};
</SCRIPT>

{$per_sel_html}

APPENDER_HTML;

        return $html;
    }


    //---------------------------------------------------------------
    //  Format a row for pre-existing member person (read from database).
    //---------------------------------------------------------------
    function format_row($index) {

        $relationship_types = array(
            'spouse' => 'spouse',
            'partner' => 'partner',
            'parent' => 'parent',
            'child' => 'child',
            'grandparent' => 'grandparent',
            'uncle or aunt' => 'uncle or aunt',
            'guardian' => 'guardian',
            'friend' => 'friend',
            'neighbor' => 'neighbor',
            'professional' => 'professional',
            'other' => 'other',
            '' => '(DELETE)'
            );

        $relationship_options_html = html_utils::format_options(
                $relationship_types, $this->relationship);

        $label = person::read_label($this->rel_person_id);
        $category = person::read_person_category($this->rel_person_id);
        $datatype = ($category == 1) ? 'member' : 'person';
        $url = sprintf('?datatype=%s&mode=update&key=%d',
                       $datatype, $this->rel_person_id);
        $link = sprintf("<A href='%s'>%s</A>", $url,
                        htmlspecialchars($label, ENT_QUOTES));

        $fmt_str = <<<EMERGENCY_CONTACT_ROW_HTML
  <td>%s</td>
  <td>
     <INPUT type='hidden' name='emerg_@_rel_person_id' value='%s'/>

     <SELECT name='emerg_@_relationship'>
{$relationship_options_html}
     </SELECT>
  </td>

EMERGENCY_CONTACT_ROW_HTML;

        $html = sprintf($fmt_str,
            $link,
            htmlspecialchars($this->rel_person_id, ENT_QUOTES)
            );

        $html = str_replace('@', $index, $html);

        return $html;
    }

};
