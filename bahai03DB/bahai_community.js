function bahai_community_check(frm) {

    if (!(frm.bahai_cmty_code.value && frm.bahai_cmty_name.value)) {
        alert("Error: Both bahai_cmty_code and bahai_cmty_name are required!");
        return false;
    }

    if (!(frm.country_code.value)) {
        alert("Error: Country code is required.");
        return false;
    }

    if (!(frm.bahai_cluster.value)) {
        alert("Error: Cluster is required.");
        return false;
    }

    if (frm.mode.value == 'create') {
        var db_check_req = new Object();
        db_check_req.check_type = 'unique';
        db_check_req.datatype = 'bahai_community';
        db_check_req.country_code = frm.country_code.value;
        db_check_req.bahai_cmty_code = frm.bahai_cmty_code.value;

        error_msg = db_checker(db_check_req);

        if (error_msg) {
            alert(error_msg);
            return false;
        }
    }

    return true;
}
