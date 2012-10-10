<?php

class r_membership extends report {

    //-----------------------------------------------------------------
    static function can_request(app_user $app_user=null) {
        if ($app_user) {
            $member_priv = $app_user->get_privilege('member');
            return ($member_priv > 0);
        }

        else {  // superuser
            $bloc = $_SESSION['app_session']->get_bahai_community();
            return ($bloc != null);
        }
    }


    //-----------------------------------------------------------------
    static function long_name() {
        return 'Membership Report';
    }


    //-----------------------------------------------------------------
    static function start_inline() {
        return false;
    }

    
    //-----------------------------------------------------------------
    static function gen_display(request $req) {

        $fmt_str = <<<REPORT_HTML

<CENTER>
<H2>
Membership Report
<br>
%s Community of %s
</h2>
<p>%s
</CENTER>

<TABLE border='1' cellpadding='4'>

  <TR>
    <TH>Last Name</TH>
    <TH>First Name</TH>
    <TH>Age Category</TH>
    <TH>Phone</TH>
    <TH>Email</TH>
  </TR>
%s
</TABLE>
<br>
<br>

<TABLE border='1' cellpadding='3'>
  <TR> <TD colspan='2'> Community Summary</TD> </TR>
  <TR> <TD>Adults</TD>     <TD>%3d</TD> </TR>
  <TR> <TD>Youths</TD>     <TD>%3d</TD> </TR>
  <TR> <TD>Jr. Youths</TD> <TD>%3d</TD> </TR>
  <TR> <TD>Children</TD>   <TD>%3d</TD> </TR>
</TABLE>


REPORT_HTML;

        $current_ts = strftime("%Y/%m/%d %H:%M", time());

        $bahai_cmty = $_SESSION['app_session']->get_bahai_community();

        $query = "SELECT person.last_name, person.first_name, " .
                 "       person.primary_phone, person.primary_email, " .
                 "       calc_age_category(member.date_of_birth, " .
                 "       CAST ('$current_ts' as TIMESTAMP), " .
                 "          member.age_category) as age_category " .
                 "FROM person, member " .
                 "WHERE " .
                 "  person.bahai_cmty_id = " . $bahai_cmty->get_key() .
                 " AND " .
                 "  person.person_category = 1  AND " .
                 "  person.person_id = member.person_id " .
                 "ORDER by person.last_name, person.first_name;";
        $res = app_session::pg_query($query);


        $counts = array('A' => 0, 'Y' => 0, 'J' => 0, 'C' => 0);

        $rows_html = '';
        while ($row = pg_fetch_assoc($res)) {
            ++$counts[$row['age_category']];
            $rows_html .= self::format_member_row($row);
        } 

        $current_date = htmlspecialchars(
                strftime("%Y/%m/%d %H:%M", time()), ENT_QUOTES);
        
        return sprintf($fmt_str, 
            BAHAI,
            $bahai_cmty->get_bahai_cmty_name(),
            $current_date,
            $rows_html,
            $counts['A'],
            $counts['Y'],
            $counts['J'],
            $counts['C']);
    }


    //-----------------------------------------------------------------
    static private function format_member_row(array $row) {

        $category_labels = array(
            'A' => 'Adult',     'Y' => 'Youth',
            'J' => 'Jr. Youth', 'C' => 'Child' );

        $member_row_fmt_str = <<<ROW_HTML

<TR>
  <TD>%s</TD>
  <TD>%s</TD>
  <TD>%s</TD>
  <TD>%s</TD>
  <TD>%s</TD>
</TR>

ROW_HTML;

        return sprintf($member_row_fmt_str,
            htmlspecialchars($row['last_name'], ENT_QUOTES),
            htmlspecialchars($row['first_name'], ENT_QUOTES),
            $category_labels[$row['age_category']],
            htmlspecialchars($row['primary_phone'], ENT_QUOTES),
            htmlspecialchars($row['primary_email'], ENT_QUOTES) );
    }

}
