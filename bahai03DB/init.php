<?php
/*  This module must be included first in any main (top level) module. */

require_once('defines.php');

error_reporting(0);

//----------------------------------------------------------------------
//  This function allows 'require_once' to be omitted for class files
//  whose filenames match the class names.
//----------------------------------------------------------------------
function __autoload($class_name) {

    if ($class_name == 'edit_error') {
        $class_name = 'edit_errors_group';
    }

    if ($class_name == 'db_error') {
        $class_name = 'app_session';
    }

    require_once($class_name . '.php');
}

session_start();

if (array_key_exists('app_session', $_SESSION)) {
    $_SESSION['app_session']->validate_session(); // exception thrown if error
    
    $cmty = $_SESSION['app_session']->get_bahai_community();
    if ($cmty) {
        date_default_timezone_set($cmty->get_time_zone());
    }
}

