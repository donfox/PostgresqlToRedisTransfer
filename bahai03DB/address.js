function address_changed(prefix) {
    var status_fld = document.getElementById(prefix + 'address_status');
    var pending_status_fld =
        document.getElementById(prefix + 'address_pending_status');

    status_fld.value = pending_status_fld.value;
}
