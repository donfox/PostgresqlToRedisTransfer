<?php

class event_notice  extends auto_construct implements type_in_db {

    public $key;
    public $event_id;
    public $event_notice_ts;
    public $event_notice_type;
    public $event_notice_text;


    //-------------------------------------------------------------------
    function __construct() {
        parent::_init_props();
    }


    //-------------------------------------------------------------------
    function __toString() {
        return $this->key;
    }


    //-------------------------------------------------------------------
    static function read_from_db($key) {
        list($event_id, $ts) = split(":", $key, 2);

        $query = sprintf("SELECT * from event_notice WHERE " .
            "event_id = %d AND event_notice_ts = CAST ('%s' as TIMESTAMP);",
            $event_id, $ts);

        $res = app_session::pg_query($query);
        $row = pg_fetch_assoc($res);

        return $row ? new event_notice($row) : NULL;
    }


    //-------------------------------------------------------------------
    function insert_to_db() {
        $query = sprintf("SELECT insert_event_notice(%d,'%s');",
            $this->event_id, pg_escape_string($this->event_notice_type));

        $res = app_session::pg_query($query);
        $row = pg_fetch_row($res);
        $this->event_notice_ts = $row[0];

        return $this->key;
    }


    //-------------------------------------------------------------------
    function update_in_db() {
        die("UPDATE not supported for event_notice");
    }


    //-------------------------------------------------------------------
    static function format_fields($obj) {

        $html = <<<EVENT_NOTICE_HTML

<TEXTAREA name='event_notice_text' rows='24' cols='60'/>
<input type='hidden' name='event_notice_ts' value=''/>
<input type='hidden' name='event_notice_type' value=''/>

EVENT_NOTICE_HTML;

        return $html;
    }

}
