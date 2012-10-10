<?php
// $Id$

/*
   A date entry field with pulldown fields for month, day, year,
   powered by javascript.

   The HTML generated is significantly different depending upon whether
   there is an initial value for the date.

   HAS INITIAL DATE:
      The date is displayed with the appropriate selected options for each of
      month, day, year.  The user then can select different options to choose
      a different date.

   NO INITIAL DATE:
      Since the normal operation of this widget is to display a date which
      can be changed, something must be different to indicate a state of
      uninitialized.  The month and day fields start out with no options to
      choose from and the year field starts out displaying instructions to
      select a year.  After a year is selected, the widget is then initialized
      to the January 1 of that year.  The user must continue to select the 
      month and day he wants.


   Note that as the month/year are changed, the number of days in the month
   might change, so the options in the day pulldown list are dynamically 
   updated.
   
*/

require_once('html_utils.php');

class date_entry {
    private $fld_name;
    private $include_time;
    private $time_granularity = 15;

    private $min_year;
    private $max_year;

    private $def_value;

    private $def_year;
    private $def_month;
    private $def_day;

    private $def_hour;   // stored in 24 hour format
    private $def_minute;

    static private $months = array(
            'January', 'February', 'March', 'April', 'May', 'June', 'July',
            'August', 'September', 'October', 'November', 'December');

    function __construct($fld_name, $include_time=false) {

        $this->fld_name = $fld_name;
        $this->include_time = $include_time;

        $this->set_year_range_relative(-20,20);
    }


    //-----------------------------------------------------------------
    function init_value($init_date) {
        if (!$init_date)
            return;

        $this->def_value = $init_date;

        $tokens = explode(' ', $init_date);
        $date_portion = $tokens[0];

        if ($this->include_time) {
            $time_portion = $tokens[1];
            $tokens = explode(':', $time_portion);
            $this->def_hour = $tokens[0];
            $this->def_minute = $tokens[1];
        }
        
        list($this->def_year, $this->def_month, $this->def_day) =
                explode('-', $date_portion);

        $this->rectify_year_range(); 
    }


    //-----------------------------------------------------------------
    function init_date_to_current() {
/*
        if (function_exists('date_default_timezone_set')) {
            date_default_timezone_set("America/Los_Angeles");
        }
*/
                              // should probably do this in init file instead
        $date = getdate();
        $ts = sprintf("%04d-%02d-%02d",
                      $date['year'], $date['mon'], $date['mday']);
        if ($this->include_time) {
            $ts .= " 00:00";
        }

        $this->init_value($ts);
    }


    //-----------------------------------------------------------------
    function rectify_year_range() {
        if (!$this->def_year)
            return;

        if ($this->def_year < $this->min_year) {
            $this->min_year = $this->def_year;
        }
        if ($this->def_year > $this->max_year) {
            $this->max_year = $this->def_year;
        }
    }


    //-----------------------------------------------------------------
    function set_time_granularity($time_granularity) {
        $this->time_granularity = $time_granularity;
    }


    //-----------------------------------------------------------------
    static function days_in_month($month, $is_leap) {
        if ($month == 2)
            return ($is_leap ? 29 : 28);

        return ($month == 4 || $month == 6 || $month == 7 ||
                $month == 9 || $month == 11) ? 30 : 31;
    }


    //-----------------------------------------------------------------
    function set_year_range_absolute($min_year, $max_year) {
        $this->min_year = $min_year;
        $this->max_year = $max_year;
        $this->rectify_year_range(); 
    }


    //-----------------------------------------------------------------
    static function current_year() {
/*
        if (function_exists('date_default_timezone_set')) {
            date_default_timezone_set("America/Los_Angeles");
        }
*/
                              // should probably do this in init file instead

        $date = getdate();
        return $date['year'];
    }


    //-----------------------------------------------------------------
    function set_year_range_relative($delta_min_year, $delta_max_year) {
        $current = self::current_year();

        $this->min_year = $current + $delta_min_year;
        $this->max_year = $current + $delta_max_year;
        $this->rectify_year_range(); 
    }


    //-----------------------------------------------------------------
    function submit_function_name() {
        return 'set_' . $this->fld_name;
    }


    //----------------------------------------------
    function format_date_field() {

        $month_options = '';
        if ($this->def_month) {
            for ($m=1; $m<=12; ++$m) {
                 $month_options .=
                     sprintf("<OPTION value='%d' %s> %s</OPTION>\n",
                     $m,
                     ($m == $this->def_month) ? ' SELECTED ' : '',
                     self::$months[$m-1] );
            }
        }

        $day_options = '';
        if ($this->def_month) {
            $days_in_month = !$this->def_year ? 31 : self::days_in_month(
                    $this->def_month, (($this->def_year % 4) == 0));

            for ($d=1; $d<=$days_in_month; ++$d) {
                $day_options .= sprintf("<OPTION value='%d' %s>%d</OPTION>\n",
                        $d,
                        ($d == $this->def_day) ? ' SELECTED ' : '',
                        $d);
            }
        }

        $year_onchange = sprintf('%s("%s");date_entry_set("%s")',
                ($this->def_year > 1000 ? "date_entry_update_days" :
                        "date_entry_init"),
                $this->fld_name,
                $this->fld_name
                );


        $year_options = '';
        if (!$this->def_year) {
            $year_options .= "<OPTION>YEAR</OPTION>\n";
        }

        for ($y=$this->min_year; $y<=$this->max_year; ++$y) {
            $year_options .= sprintf("<OPTION VALUE='%s' %s>%s</OPTION>\n",
                 $y,
                 ($y == $this->def_year) ? 'SELECTED' : '',
                 $y);
        }


        $html = <<<DATE_ENTRY_FIELDS_HTML

<INPUT type='hidden' name='{$this->fld_name}' id='{$this->fld_name}'
    value='{$this->def_value}' />
<NOBR>
<SELECT name='{$this->fld_name}_year' id='{$this->fld_name}_year'
  ONCHANGE='{$year_onchange}'>
{$year_options}
</SELECT>
<SELECT name='{$this->fld_name}_month' id='{$this->fld_name}_month'
  ONCHANGE='date_entry_update_days("{$this->fld_name}");date_entry_set("{$this->fld_name}");' >
{$month_options}
</SELECT>
<SELECT id='{$this->fld_name}_day' name='{$this->fld_name}_day'
  ONCHANGE='date_entry_set("{$this->fld_name}");' >
{$day_options}
</SELECT>

DATE_ENTRY_FIELDS_HTML;

        if ($this->include_time) {

            $display_hour = ($this->def_hour == 0 || $this->def_hour == 12) ?
                    12 : sprintf("%02d", $this->def_hour%12);
            $hour_options = '';
            for ($h=1; $h<=12; ++$h) {
                $hour_options .=
                    sprintf("<OPTION value='%02d' %s>%02d</OPTION>\n",
                        $h,
                        ($h == $display_hour) ? ' SELECTED ' : '',
                        $h);
            }

            $minute_options = '';
            $num_minute_options = floor(60 / $this->time_granularity);
            $fmt_str = "<OPTION value='%02d' %s>%02d</OPTION>\n";
            for ($m=0; $m<$num_minute_options; ++$m) {
                $min = $m * $this->time_granularity;
                $minute_options .= sprintf($fmt_str, $min, 
                        (($min == $this->def_minute) ? 'SELECTED' : ''), $min);
            }

            $am_checked = ($this->def_hour < 12) ? 'checked' : '';
            $pm_checked = ($this->def_hour >= 12) ? 'checked' : '';

            $html .= <<<TIME_HTML
&nbsp;&nbsp;&nbsp;&nbsp;
<SELECT name='{$this->fld_name}_hour' id='{$this->fld_name}_hour'
  ONCHANGE='date_entry_set("{$this->fld_name}");' >
{$hour_options}
</SELECT>
&nbsp;:&nbsp;
<SELECT name='{$this->fld_name}_minute' id='{$this->fld_name}_minute'
  ONCHANGE='date_entry_set("{$this->fld_name}");' >
{$minute_options}
</SELECT>
<INPUT name='{$this->fld_name}_am_pm' id='{$this->fld_name}_am'
  type='radio' value='am' {$am_checked}
  ONCHANGE='date_entry_set("{$this->fld_name}");' />AM
<INPUT name='{$this->fld_name}_am_pm' id='{$this->fld_name}_pm'
  type='radio' value='pm' {$pm_checked}
  ONCHANGE='date_entry_set("{$this->fld_name}");' />PM
TIME_HTML;

        }

        $html .= "</NOBR>\n";

        return $html;
    }


} // end class date_entry
?>
