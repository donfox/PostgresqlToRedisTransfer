<?php

class edit_errors_group {

    public $edit_errors_group_id;
    public $datatype;
    public $row_descriptor;

    public $edit_errors;

    //---------------------------------------------------------------------
    function __construct(array $array_data) {
        foreach(array('edit_errors_group_id','datatype',
                      'row_descriptor', 'edit_errors')
                as $fld_name) {
            if (array_key_exists($fld_name,$array_data)) {
                $this->$fld_name = $array_data[$fld_name];
            }
        }
    }


    //---------------------------------------------------------------------
    function add_edit_error(edit_error $edit_error) {
        if (is_null($this->edit_errors)) {
            $this->edit_errors = array();
        }
        array_push($this->edit_errors, $edit_error);
    }


    //---------------------------------------------------------------------
    static function read_from_db($edit_errors_group_id) {
        $query = sprintf("SELECT * from edit_errors_group " . 
                         "WHERE edit_errors_group_id = %d;",
                         $edit_errors_group_id);
        $res = app_session::pg_query($query);
        $row = pg_fetch_assoc($res);
        $obj = new edit_errors_group($row);

        $query = sprintf("SELECT message,context from edit_error " .
                         "WHERE edit_errors_group_id = %d " .
                         "ORDER BY edit_error_num;",
                         $edit_errors_group_id);
        $res = app_session::pg_query($query);
        while ($row = pg_fetch_assoc($res)) {
            $edit_error = new edit_error($row['message'], $row['context']);
            $obj->add_edit_error($edit_error);
        }

        return $obj;
    }


    //---------------------------------------------------------------------
    function __toString() {
         return sprintf("%s (%s)",
             $this->datatype, $this->row_descriptor);
    }


    //---------------------------------------------------------------------
    //  Returns the 'edit_errors_group_id', which is needed to update the 
    //  affected row to point to the edit results.
    //---------------------------------------------------------------------
    function insert_to_db( ) {
        $query = sprintf("SELECT insert_edit_errors_group('%s', '%s');",
                         $this->datatype, $this->row_descriptor);
        $res = app_session::pg_query($query);
        $this->edit_errors_group_id = pg_fetch_result($res, 0);

        foreach ($this->edit_errors as $edit_error) {
            $query = sprintf("SELECT add_edit_error(%d, '%s', '%s');",
                    $this->edit_errors_group_id,
                    $edit_error->message,
                    $edit_error->context);
            app_session::pg_query($query);
        }

        return $this->edit_errors_group_id;
    }


    //---------------------------------------------------------------------
    //  
    //---------------------------------------------------------------------
    function gen_banner_link() {
        $url = sprintf("edit_errors_display.php?edit_errors_group_id=%d",
                       $this->edit_errors_group_id);

        $text = sprintf("(%d errors)", count($this->edit_errors));
        $size_and_pos = 'top=200,left=200,width=900,height=500';

        $html = "<A href='$url' target='ERRORS'>" .
                "<SPAN class='edit_error'>$text</SPAN>" .
                "</A>\n";

        return $html;
    }


    //---------------------------------------------------------------------
    //
    //---------------------------------------------------------------------
    function gen_detailed_display() {

        $title = sprintf("%s (%s) Errors",
            call_user_func(array($this->datatype, 'type_long_name')),
            $this->row_descriptor);

        $error_table = "<TABLE>\n";
        foreach ($this->edit_errors as $edit_error) {
            $error_table .= sprintf("<TR><TD>%s</TD><TD>%s</TD>\n",
                $edit_error->message, $edit_error->context);
        }
        $error_table .= "</TABLE>\n";

        $html = <<<DISPLAY_HTML

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<HTML xmlns="http://www.w3.org/1999/xhtml">
<HEAD profile="http://gmpg.org/xfn/1">
<TITLE>$title</TITLE>
</HEAD>
<BODY>
<H1>$title</H1>
$error_table
</BODY>
</HTML>

DISPLAY_HTML;
  
        return $html;
    }

}



//---------------------------------------------------------------------
class edit_error {

    public $message;
    public $context;

    function __construct($message, $context) {
        $this->message = $message;
        $this->context = $context;
    }

};
