<?php  // $Id$

// If there's a problem, an error message is returned, otherwise empty return.

require_once('init.php');

$input = $_POST;  // can be changed to GET for debugging

switch ($input['datatype']) {

    case 'person':
        person_db_check($input);
        break;

    case 'app_user':
        app_user_db_check($input);
        break;

    case 'bahai_community':
        bahai_community_db_check($input);
        break;

}


//-------------------------------------------------------------------
function person_db_check(array $input) {
    
    if ($input['check_type'] == 'unique') {
        $query = sprintf("SELECT person_id FROM person " .
                         "WHERE last_name = '%s' and first_name = '%s';",
                         pg_escape_string($input['last_name']),
                         pg_escape_string($input['first_name']));
    
        $res = app_session::pg_query($query);
        $row = pg_fetch_assoc($res);
        if ($row) {
            printf("Person \"%s, %s\" already exists.",
                   htmlspecialchars($input['last_name']),
                   htmlspecialchars($input['first_name']));
        }
    }
}

//-------------------------------------------------------------------
function app_user_db_check(array $input) {

    if ($input['check_type'] == 'unique') {
        $query = sprintf("SELECT login FROM app_user WHERE login = '%s';",
                         pg_escape_string($input['login']));
    
        $res = app_session::pg_query($query);
        $row = pg_fetch_assoc($res);
        if ($row) {
            printf("Application user \"%s\" already exists.",
                   htmlspecialchars($input['login']));
        }
    }

    else if ($input['check_type'] == 'password_ok') {
        $query = sprintf("SELECT password_is_ok('%s',MD5('%s'));",
                pg_escape_string($input['login']),
                $input['password']);

        $res = app_session::pg_query($query);
        $is_ok = pg_fetch_result($res, 0);

        if ($is_ok == 'f') {
            print("You cannot reuse current or most recent password.");
        }
    }
}



//-------------------------------------------------------------------
function bahai_community_db_check(array $input) {
    
    if ($input['check_type'] == 'unique') {
        $query = sprintf("SELECT bahai_cmty_id FROM bahai_community " .
                         "WHERE country_code = '%s' AND bahai_cmty_code = '%s';",
                         pg_escape_string($input['country_code']),
                         pg_escape_string($input['bahai_cmty_code']) );
    
        $res = app_session::pg_query($query);
        $row = pg_fetch_assoc($res);
        if ($row) {
            printf("Bahai location with Country code='%s', Location code='%s' "
                   . "already exists.",
                   htmlspecialchars($input['country_code']),
                   htmlspecialchars($input['bahai_cmty_code']));
            return;
        }
    }

}
