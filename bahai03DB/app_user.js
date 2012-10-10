function validate_create(frm) {
    if (frm.mode.value != 'create')
        return true;

    var db_check_req = new Object;
    db_check_req.datatype = 'app_user';
    db_check_req.check_type = 'unique';
    db_check_req.login = frm.login.value;

    error_msg = db_checker(db_check_req);
    if (error_msg) {
        alert(error_msg);
        return false;
    }

    if (!frm.password.value) {
        alert("Password is required.");
        return false;
    }

    if (frm.password.value != frm.password_dup.value) {
        alert("Passwords don't match.");
        return false;
    }

    return true;
}


function validate_password_change(frm) {

    if (!frm.password.value) {
        alert("Password is required.");
        return false;
    }

    if (frm.password.value != frm.password_dup.value) {
        alert("Passwords don't match.");
        return false;
    }

    var db_check_req = new Object;
    db_check_req.datatype = 'app_user';
    db_check_req.check_type = 'password_ok';
    db_check_req.login = frm.login.value;
    db_check_req.password = frm.password.value;

    error_msg = db_checker(db_check_req);
    if (error_msg) {
        alert(error_msg);
        return false;
    }

    return true;
}
