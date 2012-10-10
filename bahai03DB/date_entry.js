function days_in_month(month, is_leap) {
    return (month == 2) ? (is_leap ? 29 : 28) :
            (month == 4 || month == 6 || month == 7 ||
                month == 9 || month == 11) ? 30 : 31;
}


function date_entry_update_days(fld_name) {
    year_fld = document.getElementById(fld_name + '_year');
    month_fld = document.getElementById(fld_name + '_month');
    day_fld = document.getElementById(fld_name + '_day');

    month = month_fld.value;

    num_days = days_in_month(month, (year_fld.value % 4 == 0));

    old_num_days = day_fld.options.length;
    if (old_num_days < 1)
        old_num_days = 1;

    if (num_days > old_num_days) {
        for (day=old_num_days; day<=num_days; ++day) {
            day_fld.options[day-1] = new Option(day,day);
        }
    }
    else if (num_days < old_num_days) {
        day_fld.options.length = num_days;
    }
}


function date_entry_set(fld_name) {
    year_fld = document.getElementById(fld_name + '_year');
    month_fld = document.getElementById(fld_name + '_month');
    day_fld = document.getElementById(fld_name + '_day');
    fld = document.getElementById(fld_name);

    if (year_fld.value > 1000) {
        var new_value = 
            year_fld.value + '-' + month_fld.value + '-' + day_fld.value;

        hour_fld = document.getElementById(fld_name + '_hour');
        if (hour_fld) {
            minute_fld = document.getElementById(fld_name + '_minute');
            var is_pm = document.getElementById(fld_name + '_pm').checked;
            hour = hour_fld.value;
            if (hour == 12) {
                hour = is_pm ? '12' : '00';
            }
            else if (is_pm) {
                var hour_str = ((hour - 0) + 112) + '';
                hour = hour_str.substr(1,2);
            }

            new_value += ' ' + hour + ':' + minute_fld.value;
        }

        document.getElementById(fld_name).value = new_value;
    }
}


function date_entry_init(fld_name) {

    year_fld = document.getElementById(fld_name + '_year');
    month_fld = document.getElementById(fld_name + '_month');
    day_fld = document.getElementById(fld_name + '_day');

    month_fld.options[0]  = new Option('Jan', 1);
    month_fld.options[1]  = new Option('Feb', 2);
    month_fld.options[2]  = new Option('Mar', 3);
    month_fld.options[3]  = new Option('Apr', 4);
    month_fld.options[4]  = new Option('May', 5);
    month_fld.options[5]  = new Option('Jun', 6);
    month_fld.options[6]  = new Option('Jul', 7);
    month_fld.options[7]  = new Option('Aug', 8);
    month_fld.options[8]  = new Option('Sep', 9);
    month_fld.options[9]  = new Option('Oct', 10);
    month_fld.options[10] = new Option('Nov', 11);
    month_fld.options[11] = new Option('Dec', 12);

    month_fld.value = '1';

    day_fld.options.length = 0;
    date_entry_update_days(fld_name);

    year_fld.onchange =
        function onchange(event) {
            date_entry_update_days(fld_name);
        };
}
