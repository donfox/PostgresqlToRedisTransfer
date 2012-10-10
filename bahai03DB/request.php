<?php
// $Id: request.php,v 1.1 2006/04/03 05:13:08 bmartin Exp $

class request extends auto_construct {

    public $datatype;
    public $mode;

    public $status;    // empty for success, or 'warn' or 'error'
    public $message;   // describing results of last transaction

    public $bahai_community;
    public $key;

    public $report_type;
    public $report_inline;

    public $extra_fields;  // for data specific to particular requests


    //-------------------------------------------------------------------
    function __construct(array $array_data) {
        $this->_copy_properties($array_data, null, true);
        $sess = $_SESSION['app_session'];
        $this->bahai_community = $sess->get_bahai_community();
    }


    //-------------------------------------------------------------------
    //  Format the banner for data entry forms, including:
    //    1) mode of entry form (create or update)
    //    2) if update, then the descriptor of the object being updated
    //    3) results of previous transaction (if applicable)
    //  
    //     OR
    //
    //  at least a message describing the results of the previous transaction.
    //-------------------------------------------------------------------
    function title_text($obj_descriptor, $edit_errors_group) {

        $title = '';

        if ($this->datatype) {
            $title .= sprintf("<span class='form_header'> %s : %s</span>\n",
                ucfirst($this->mode), 
                call_user_func(array($this->datatype, 'type_long_name')) );
        }

        if ($obj_descriptor) {
            $title .= sprintf(" (%s)", $obj_descriptor);
        }

        if ($this->message) {
            $title .= sprintf(" <span class='last_transaction'>%s</span>\n",
                              $this->message);
        }

        if ($edit_errors_group) {
            $title .= ' ' . $edit_errors_group->gen_banner_link();
        }

        return $title;
    }


    //-------------------------------------------------------------------
    function gen_display($obj=null) {

        $html = '';

        //  Update requires an object to act upon.
        if ($this->mode == 'update') {
            $obj = call_user_func(array($this->datatype, 'read_from_db'),
                                  $this->key);
            $edit_errors_group = $obj->get_edit_errors_group();
        }
        else {
            $obj = null;
            $edit_errors_group = null;
        }

/*
        if (!($this->datatype == 'report' or $this->mode == 'select')) {
            $obj = ($this->mode != 'create' and $this->key) ?
                call_user_func(array($this->datatype, 'read_from_db'),
                               $this->key) : null;
        }
*/

        if ($this->datatype) {
            $html .= sprintf("<div class='form_header'>%s </div>\n", 
                    $this->title_text($obj, $edit_errors_group));
        }


        if ($this->datatype == 'report') {
            $html .= report::gen_display($this);
        }

        else if ($this->mode == 'select') {
            $criteria = $this->datatype == 'person' ?
                new person_criteria(array('category_list' => array(2,3,4)))
                : null;
            $select_items = call_user_func(
                    array($this->datatype, 'get_select_items'), $criteria);

            $selector = new selector($this->datatype);
            $html .= $selector->format_html($select_items);
        }

        //  Default is entry form for datatype (allowing create/update/delete)
        else if ($this->datatype) {
            $html .= call_user_func(array($this->datatype, 'gen_display'),
                    $this, $obj);
        }

        else {
            $html .= $this->title_text($obj, $edit_errors_group);
        }

        return $html;
    }

}
