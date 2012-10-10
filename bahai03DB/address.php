<?php

class address extends auto_construct {

    public $address_status;
    public $address_id;
    public $country_code;


    //--------------------------------------------------------------------
    static function dispatch_construct($country_code, array $array_data,
            $prefix=null) {
        $address_class = country::get_address_class($country_code);
        return new $address_class($array_data, $prefix);
    }


    //--------------------------------------------------------------------
    static function dispatch_read_from_db($country_code, $key) {

        $address_class = country::get_address_class($country_code);

        $query = sprintf("SELECT * from %s where address_id = %d;",
                $address_class, $key);
        $res = app_session::pg_query($query);
        $row = pg_fetch_array($res);
        if (!$row)
            return null;

        return new $address_class($row);
    }


    //--------------------------------------------------------------------
    static function delete_from_db($key) {
        die("Not used?");
    }


    //--------------------------------------------------------------------
    static function format_status_html($prefix, $obj=null) {

        $fmt_str = <<<STATUS_HTML

<input type='hidden' value='%s'
 name='{$prefix}address_status' id='{$prefix}address_status' />

<input type='hidden' value='%s'
 name='{$prefix}address_pending_status' id='{$prefix}address_pending_status'/>

<input type='hidden' name='{$prefix}address_id' value='%s'/>

STATUS_HTML;

        $html = sprintf($fmt_str, 
            ($obj ? 'unchanged' : ''),
            ($obj ? 'update' : 'insert'),
            ($obj ? $obj->address_id : '')
            );

        return $html;
    }


    //--------------------------------------------------------------------
    static function dispatch_format_fields($country_code, $prefix, $obj=null,
            $onchange_js=null) {

        $address_class = country::get_address_class($country_code);

        return call_user_func(array($address_class, 'format_fields'),
                $prefix, $obj, $onchange_js);
    }


}
