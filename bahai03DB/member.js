function member_check(frm) {

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


var emerg_index;

function add_emergency_contact(rel_person_id, person_label) {
    var ec_table = document.getElementById('emergency_contacts_table');
    var new_row = ec_table.insertRow(emerg_index);

    var col_0 = new_row.insertCell(0);
    //col_0.innerHTML = person_label;

    slen = person_label.length;
    datatype = (person_label.substring(slen-7, slen-1) == 'member') ?
        'member' : 'person';

    col_0.innerHTML = "<A href='?datatype=" + datatype + "&mode=update&key=" + 
        rel_person_id + "'>" + person_label + "</A>\n";

    fld_name = 'emerg_' + emerg_index + '_rel_person_id';
    var col_1 = new_row.insertCell(1);
    col_1.innerHTML =
    "<INPUT type='hidden' name='" +fld_name+ "' value='" +rel_person_id+ "'>\
    </INPUT>\
     <SELECT name='emerg_" + emerg_index + "_relationship'>\
       <OPTION  value='spouse'>spouse</OPTION>\
       <OPTION  value='partner'>partner</OPTION>\
       <OPTION  value='parent'>parent</OPTION>\
       <OPTION  value='child'>child</OPTION>\
       <OPTION  value='grandparent'>grandparent</OPTION>\
       <OPTION  value='uncle/aunt'>uncle/aunt</OPTION>\
       <OPTION  value='guardian'>guardian</OPTION>\
       <OPTION  value='friend'>friend</OPTION>\
       <OPTION  value='neighbor'>neighbor</OPTION>\
       <OPTION  value='professional'>professional</OPTION>\
       <OPTION  value='other'>other</OPTION>\
       <OPTION value=''>(DELETE)</OPTION>\
     </SELECT>";

    ++emerg_index;
}


function lang_check(fld_name) {
    frm = document.forms.member_entry;
    if (fld_name == 'language') {
        if (frm.language.value == 'other') {
            frm.language_entry.disabled = false;
        }
        else {
            frm.language_entry.disabled = true;
            frm.language_entry.value = '';
        }
    }
    else {
        if (frm.language_2nd.value == 'other') {
            frm.language_2nd_entry.disabled = false;
        }
        else {
            frm.language_2nd_entry.disabled = true;
            frm.language_2nd_entry.value = '';
        }
    }
}


function check_mailing() {

    frm = document.forms.member_entry;
    myDiv = document.getElementById('mailing');
    myDiv.style.display =
         (frm.mailing_same.checked ? 'none' : 'inline');
}


function clear_res_address() {
    frm = document.forms.member_entry;

    frm.res_address_status.value = 'insert';
    frm.res_address_1.value = '';
    frm.res_address_2.value = '';
    frm.res_city.value = '';
    frm.res_state_code.selectedIndex = -1;
    frm.res_zip_postal.value = '';
}


function clear_mailing_address() {
    frm = document.forms.member_entry;

    frm.mailing_address_status.value = 'insert';
    frm.mailing_address_1.value = '';
    frm.mailing_address_2.value = '';
    frm.mailing_city.value = '';
    frm.mailing_state_code.selectedIndex = -1;
    frm.mailing_zip_postal.value = '';
}

