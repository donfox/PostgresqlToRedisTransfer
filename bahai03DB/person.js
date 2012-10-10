function person_check(frm) {

    var error_msg = null;

    if (frm.first_name.value == '' || frm.last_name.value == '') {
        alert('First and Last names are required.');
        return false;
    }

    if (frm.mode.value == 'create') {
        var db_check_req = new Object();
        db_check_req.check_type = 'unique';
        db_check_req.datatype = 'person';
        db_check_req.last_name = frm.last_name.value;
        db_check_req.first_name = frm.first_name.value;
        error_msg = db_checker(db_check_req);

        if (error_msg) {
            alert(error_msg);
            return false;
        }
    }

    return true;
}


function convert_to_member(frm) {

    frm.datatype.value = 'member';
    //frm.mode.value = 'convert';

    return true;
}
