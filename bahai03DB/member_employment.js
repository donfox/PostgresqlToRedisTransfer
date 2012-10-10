var empl_names = new Array();
var max_num_empl;
var num_empl;

//--------------------------------------------------------------------------
//  Called when a field in the form for an employer has changed.
//--------------------------------------------------------------------------
function empl_field_changed(index, change_status) {
    fld = document.getElementById('empl_' + index + '_status');
    fld.value = change_status;
}


//--------------------------------------------------------------------------
// After a new employer name has been entered and the Button to open the form
// has been clicked, fill the employer name into the form and make it visible.
//--------------------------------------------------------------------------
function display_new_employer() {

    var index;

    // Get the name of the new employer
    old_fld = document.getElementById('new_employer_name');
    empl_name = old_fld.value;
    old_fld.value = '';

    // Make sure this is not a duplicate (employer name).
    // Note that Microsoft Internet Explorer doesn't properly implement
    // the indexOf method.
    if (navigator.appName.indexOf('Microsoft') >= 0) {
        pos = -1;
        for (i=0; i<empl_names.length; ++i) {
            if (empl_names[i] == empl_name) {
                pos = i;
                break;
            }
        }
    }
    else {
        pos = empl_names.indexOf(empl_name);
    }

    if (pos != -1) {
        alert("Employer '" + empl_name + "' already exists!");
        return;
    }

    // If the limit of employers has not been reached, display the next one.
    if (num_empl < max_num_empl) {
        index = ++num_empl;
        empl = document.getElementById('empl_' + index);
        empl.style.display = 'inline';
    }
    else {
        index = num_empl;
    }

    // Propagate the name of the employer into the form.
    // The second name field is a read only field used in a label.
    new_fld_1 = document.getElementById('empl_' + index + '_employer_name');
    new_fld_2 = document.getElementById('empl_' + index + '_name2');
    new_fld_1.value = empl_name;
    new_fld_2.value = empl_name;

    // Disable the "NEW" button until a new name is entered.
    button = document.getElementById('new_employer_button');
    button.disabled = true;

    // If the limit of number of employers has been reached, hide the
    // form for creating a new employer.
    if (num_empl == max_num_empl) {
        whole_row = document.getElementById('new_employer_row');
        whole_row.style.display = 'none';
    }
    empl_names.push(empl_name);

    empl_field_changed(index, 'insert');
}


//--------------------------------------------------------------------------
//  Called when a key is pressed in the employer name entry field.
//--------------------------------------------------------------------------
function key_new_employer(fld) {
    new_empl_button = document.getElementById('new_employer_button');
    new_empl_button.disabled = (fld.value.length == 0);
}


//--------------------------------------------------------------------------
//  Enables ENTER to be used after typing in the name of an employer
//  (overrides the default behavior of submitting the form).
//--------------------------------------------------------------------------
function listen_for_enter(event) {
    if (event.keyCode == 13) {
        new_empl_button = document.getElementById('new_employer_button');
        new_empl_button.click();
        event.cancel;
        return false;
    }
    else {
        return true;
    }
}
