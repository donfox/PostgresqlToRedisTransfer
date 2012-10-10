var event_person_index;
var follow_up_ts_template;

function add_event_person(person_id, person_label) {

    var ep_table = document.getElementById('event_persons_table');
    var new_row = ep_table.insertRow(event_person_index+1);

    var col_0 = new_row.insertCell(0);
    col_0.innerHTML = person_label;

    var col_1 = new_row.insertCell(1);
    col_1.innerHTML = "<INPUT type='hidden' name='evper_" +
     event_person_index +
     "_person_id' value='" + person_id + "'/>\
     <SELECT name='evper_" + event_person_index + "_role'>\
       <OPTION SELECTED value='attendee'>attendee</OPTION>\
       <OPTION  value='host'>host</OPTION>\
       <OPTION  value='tutor'>tutor</OPTION>\
       <OPTION  value='facilitator'>facilitator</OPTION>\
       <OPTION value=''>(DELETE)</OPTION>\
     </SELECT>";

    var col_2 = new_row.insertCell(2);
    col_2.innerHTML = "<INPUT type='checkbox' name='evper_" +
                      event_person_index + "_follow_up' />";
    
    var col_3 = new_row.insertCell(3);
    col_3.innerHTML = "<INPUT type='text' name='evper_" + event_person_index +
                      "_follow_up_action' size='40'/>";

    var col_4 = new_row.insertCell(4);
    col_4.innerHTML = follow_up_ts_template.replace(/@/g, event_person_index);

    ++event_person_index;
}


function event_check(frm) {

    var error_msg = null;

    if (frm.event_start_ts.value == '') {
        alert('Start time is required.');
        return false;
    }

    if (frm.event_start_ts.value > frm.event_end_ts.value) {
        alert('End time must be later than start time.');
        return false;
    }

    return true;
}
