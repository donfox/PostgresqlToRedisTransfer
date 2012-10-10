<?php

class location {

    //--------------------------------------------------------------------
    static function dispatch_read_from_db($country_code, $key) {

        $location_class = country::get_location_class($country_code);

        return call_user_func(array($location_class, 'read_from_db'), $key);
    }


    //--------------------------------------------------------------------
    static function dispatch_construct($country_code, Array $array_data) {
        $location_class = country::get_location_class($country_code);
        return new $location_class($array_data);
    }


    //--------------------------------------------------------------------
    static function dispatch_format_fields($country_code, $obj=null) {

        $location_class = country::get_location_class($country_code);

        $html = call_user_func(array($location_class, 'format_fields'), $obj);
        return $html;
    }

}
