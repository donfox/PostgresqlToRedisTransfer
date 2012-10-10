<?php

class r_events extends report {

    //-----------------------------------------------------------------
    static function long_name() {
        return 'Events Report';
    }


    //-----------------------------------------------------------------
    static function start_inline() {
        return false;
    }

    
    //-----------------------------------------------------------------
    static function gen_display(request $req) {


        $fmt_str = <<<REPORT_HTML

<CENTER>
<H1>Events Report</H1>
<h2> %s of %s </h2>
<p>%s
</CENTER>

REPORT_HTML;


        $bahai_cmty = $_SESSION['app_session']->get_bahai_community();
        $current_date = htmlspecialchars(
                strftime("%Y/%m/%d %H:%M", time()), ENT_QUOTES);
        $html = sprintf($fmt_str,
                bahai_community::type_long_name(),
                $bahai_cmty,
                $current_date);

        $sub_query = sprintf("SELECT event_id FROM event " .
            "WHERE event.event_type_code = event_type.event_type_code AND " .
            "event.bahai_cmty_id = %d", $bahai_cmty->get_key());

        $query = "SELECT event_type_code,full_label " . 
                 "FROM event_type " .
                 "WHERE EXISTS($sub_query) " .
                 "ORDER BY display_order;";
        $res = app_session::pg_query($query);

        while ($row = pg_fetch_assoc($res)) {
            $data = new event_type_data($row['event_type_code'],
                                        $row['full_label']);
            $html .= $data->gen_report_section();
        }

        return $html;
    }

}


//---------------------------------------------------------------------
class event_totals {
    public $bahai_totals;
    public $non_bahai_totals;
    public $total;

    public $count;  // only used with summary


    public function __construct(event $event=null) {
        $this->bahai_totals =     array('A'=>0,'Y'=>0,'J'=>0,'C'=>0);
        $this->non_bahai_totals = array('A'=>0,'Y'=>0,'J'=>0,'C'=>0);
        $this->total = 0;

        if (is_null($event))
            return;

        if ($event->event_counts && !$event->event_counts->is_empty()) {
            $this->calc_from_event_counts($event->event_counts);
        }
        else {
            $this->calc_from_event_attendees($event);
        }
    }


    static function generate_sum_totals(array $event_totals_list) {
        $sum_obj = new event_totals();
        foreach ($event_totals_list as $evt) {
            foreach (array('A','Y','J','C') as $letter) {
                $sum_obj->bahai_totals[$letter] += 
                        $evt->bahai_totals[$letter];
                $sum_obj->non_bahai_totals[$letter] += 
                        $evt->non_bahai_totals[$letter];
            }
            $sum_obj->total += $evt->total;
        }
        return $sum_obj;
    }


    public function format_totals_cells() {

        $html = sprintf("<TD>%d</TD>\n", $this->total);

        foreach (array('A','Y','J','C') as $letter) {
            $html .= sprintf("<TD>%d&nbsp;&nbsp;/&nbsp;&nbsp;%d</TD>\n", 
                     $this->bahai_totals[$letter],
                     $this->non_bahai_totals[$letter]);
        }

        return $html;
    }


    public function format_averages_cells($divisor) {

        $html = sprintf("<TD>%.1f</TD>\n", $this->total/$divisor);

        foreach (array('A','Y','J','C') as $letter) {
            $html .= sprintf("<TD>%.1f&nbsp;&nbsp;/&nbsp;&nbsp;%.1f</TD>\n", 
                     $this->bahai_totals[$letter]/$divisor,
                     $this->non_bahai_totals[$letter]/$divisor );
        }

        return $html;
    }


    static public function calc_sums(array $event_totals_list) {
        $sums = new event_totals();

        foreach ($event_totals_list as $event_totals) {
            foreach (array('A','Y','J','C') as $letter) {
                $sums->bahai_totals[$letter] +=
                        $event_totals->bahai_totals[$letter];
                $sums->non_bahai_totals[$letter] += 
                        $event_totals->non_bahai_totals[$letter];
            }
        }
        return $sums;
    }


    private function calc_from_event_counts(event_counts $event_counts) {
        $this->total = 0;
        foreach ( array(
                'adults'   => 'A',
                'youths'   => 'Y',
                'juniors'  => 'J',
                'children' => 'C'
                ) as $label => $letter) {
            $fld1 = 'num_bahai_' . $label;
            $this->bahai_totals[$letter] = $event_counts->$fld1;

            $fld1 = 'num_non_bahai_' . $label;
            $fld2 = 'num_new_non_bahai_' . $label;
            $this->non_bahai_totals[$letter] =
                $event_counts->$fld1 + $event_counts->$fld2;

            $this->total += ($this->bahai_totals[$letter] +
                             $this->non_bahai_totals[$letter]);
        }
    }
            

    //---------------------------------------------------------------------
    private function calc_from_event_attendees(event $event) {
        $this->total = 0;
        $current_ts = strftime("%Y/%m/%d %H:%M", time());

        foreach ($event->event_persons as $evper) {
        
            $query = sprintf("SELECT person_get_age_category(" .
                             "%d, CAST ('%s' as timestamp));",
                             $evper->person_id,
                             $current_ts);
            $res = app_session::pg_query($query);
            $age_cat = pg_fetch_result($res, 0);
            ++$this->bahai_totals[$age_cat];
            ++$this->total;
        }
    }
 
}


//---------------------------------------------------------------------
class event_data {
    public $event_type;
    public $event_start;
    public $event_loc;
    public $event_totals;


    //---------------------------------------------------------------------
    public function __construct(event $event) {
        $this->event_totals = new event_totals($event);
        $this->event_start = $event->event_start_ts;
        $addr_loc = $event->event_address;
        if ($addr_loc) {
            $this->event_loc = '' . $addr_loc;
        }
    }


    //---------------------------------------------------------------------
    public function gen_report_line() {

        $totals_cells_html = $this->event_totals->format_totals_cells();

        $fmt_str = <<<REPORT_LINE_HTML

<TR>
  <TD>%s</TD>
  <TD>%s</TD>
  {$totals_cells_html}
</TR>

REPORT_LINE_HTML;

         $html = sprintf($fmt_str, 
                 htmlspecialchars($this->event_start, ENT_QUOTES),
                 htmlspecialchars($this->event_loc, ENT_QUOTES) );
             
         return $html;
    }

}


//---------------------------------------------------------------------
class event_type_data {

    public $type_label;
    public $events_data;


    //---------------------------------------------------------------------
    public function __construct($event_type_code, $type_label) {
        $this->type_label = $type_label;
        $this->events_data = array();

        $bahai_cmty = $_SESSION['app_session']->get_bahai_community();
        $bahai_cmty_id = $bahai_cmty->get_key();

        $query = "SELECT event_id FROM event " .
                 "WHERE event_type_code = '$event_type_code' AND " .
                 " bahai_cmty_id = $bahai_cmty_id " .
                 "ORDER BY event_start_ts;";
        $res = app_session::pg_query($query);
        while ($row = pg_fetch_assoc($res)) {
            $event = event::read_from_db($row['event_id']);
            array_push($this->events_data, new event_data($event));
        }
    }


    //---------------------------------------------------------------------
    public function gen_report_section() {

        if (count($this->events_data) == 0)
            return '';

        $event_totals_list = array();
        $rows_html = '';
        foreach ($this->events_data as $data) {
            $rows_html .= $data->gen_report_line();
            array_push($event_totals_list, $data->event_totals);
        }

        $sum_totals = event_totals::generate_sum_totals($event_totals_list);
        $sum_totals_html = $sum_totals->format_totals_cells();
        $avg_totals_html =
                $sum_totals->format_averages_cells(count($event_totals_list));

        $fmt_str = <<<SECTION_HDR_HTML
<TABLE border='1' cellpadding='4'>

  <TR><TH colspan='7'>%s</TH></TR>
  <TR>
    <TH>Start Date and Time</TH>
    <TH>Event Location</TH>
    <TH>Total</TH>
    <TH>Adults<br>(B/nonB)</TH>
    <TH>Youth<br>(B/nonB)</TH>
    <TH>Jr. Youth<br>(B/nonB)</TH>
    <TH>Children<br>(B/nonB)</TH>
  </TR>
%s
<TR>
  <TD colspan='2'>GRAND TOTAL</TD>
  %s
</TR>
<TR>
  <TD colspan='2'>AVERAGE</TD>
  %s
</TR>
</TABLE>
<P>&nbsp;</P>
SECTION_HDR_HTML;

        $html = sprintf($fmt_str,
                $this->type_label,
                $rows_html,
                $sum_totals_html,
                $avg_totals_html);

        return $html;
    }
}

