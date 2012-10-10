function process_form_data(frm) {
    var data_ok;

    data_ok = person_check(frm);

    if (!data_ok)
        return false;

    var query_string = '';
    for (var i=0; i<frm.elements.length; ++i) {
        fld = frm.elements[i];
        if (fld.type != 'submit') {
            query_string = query_string + fld.name + '=' + fld.value + '&';
        }
    }
    query_string = query_string.substring(0, query_string.length-2);

    str = ajax("ps_main.php", query_string);
    pos = str.indexOf(":");
    handle_values(str.substr(0,pos), str.substr(pos+1));

    frm.reset();
    return false;
    //return true;
}
