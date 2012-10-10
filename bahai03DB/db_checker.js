function db_checker(key_vals) {
    var error_str;
    var check_str = format_request_string(key_vals);
    error_str = ajax('db_checker.php', check_str);
    return error_str;
}

function format_request_string(key_vals) {
    var query_str = '';

    for (var key in key_vals) {
        query_str = query_str + key + '=' + key_vals[key] + '&';
    }

    return query_str.substring(0, query_str.length-1);
}
