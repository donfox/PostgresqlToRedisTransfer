var atc_member_index;
function add_atc_member(person_id, person_label) {

    var am_table = document.getElementById('atc_members_table');
    var new_row = am_table.insertRow(atc_member_index);

    var col_0 = new_row.insertCell(0);
    col_0.innerHTML = person_label +
    "<INPUT type='hidden' name='atc_" + atc_member_index + "_person_id' " +
    "value='" + person_id + "'/>";

    var col_1 = new_row.insertCell(1);
    col_1.innerHTML =
        "<INPUT type='checkbox' name='atc_" + atc_member_index + "_delete' />";

    ++atc_member_index;
}

