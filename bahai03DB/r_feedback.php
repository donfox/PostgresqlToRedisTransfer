<?php

class r_feedback extends report {


    static function long_name() {
        return "Feedback Report";
    }


    // Visible only to superuser.
    static function can_request(app_user $app_user=null) {
        return ($app_user == null);
    }


/*
CREATE TABLE feedback(
    ts                   timestamp default now(),
    login                varchar,
    feedback_text        varchar,
    PRIMARY KEY(ts, login)
);
*/

    
    static function gen_display(request $req) {

        $html = "<H1>Feedback Report</H1>\n";
        $fmt_str = <<<FMT_HTML

<HR>
%s  (%s)
<P>
%s

FMT_HTML;

        $query = "SELECT * FROM feedback ORDER BY ts;";

        $res = app_session::pg_query($query);
        while ($row = pg_fetch_assoc($res)) {
            $html .= sprintf($fmt_str,
                    substr($row['ts'], 0, strlen($row['ts'])-10),
                    $row['login'],
                    htmlspecialchars($row['feedback_text'], ENT_QUOTES) );
        }
        
        return $html;
    }

}
