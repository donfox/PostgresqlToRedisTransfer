<?php

/*
 *--------------------------------------------------------------------
 *  REPORTS are to be created by subclassing the 'report' class.
 *  The report names should be valid "normal" linux file names
 *  (without spaces or special characters) starting with
 *  'r_' and with a '.php' extension.
 *
 *  The display of the report OR of the form for inputing parameters
 *  is formatted in the 'gen_display' method.  The formatted text should 
 *  be valid HTML such as could be inserted within <BODY> and </BODY>
 *  (in other words, the HTML, HEAD, and BODY tags are not included).
 *
 *--------------------------------------------------------------------

 *  Forms for parameter gathering should use http 'GET' method.
 *  The following hidden fields should be included:
 *     datatype=report
 *    report_type=_______   (the class name of the report)
 *
 *  ...plus the fields for parameters.
 *
 *  In addition there must be some way to determine from the $_GET data 
 *  whether the request is to input the parameters or to print the report
 *  (after the parameter has been gathered).
 *
 *--------------------------------------------------------------------
 *  
 *  Output can either be displayed in the main window under the banner,
 *  or it can be sent to it's own window without any banner
 *  (an option that might make for better printing).
 *
 *  When output is to be displayed in the main window, the program name 
 *  should be omitted from the URL (href for 'A' tags, action for 'FORM' tags).
 *  In this case, also no 'TARGET' is specified (defaults to same window).
 *
 *  When output is to displayed in a separate window, the program name 
 *  should be specified as 'report_display.php'.  In addition the 
 *  'TARGET' value should be set to 'report'.
 */
abstract class report {

    static private $reports_list = array();

    static private function get_reports_list() {
        $app_user = $_SESSION['app_session']->get_app_user();

        $pattern = '/^(r_\w+)\.php$/';
        $dir = opendir('.');
        while ($file = readdir($dir)) {
            if (preg_match($pattern, $file, $matches) and is_readable($file)) {
                $rep_class = $matches[1];
                if (call_user_func(array($rep_class, 'can_request'),
                                   $app_user)) {
                    array_push(self::$reports_list, $rep_class);
                }
            }
        }
        closedir($dir);
    }

    //--------------------------------------------------------------------
    //  This name shows up on menus.
    //--------------------------------------------------------------------
    static function long_name() {
        die("report::long_name  must be overridden");
    }


    //--------------------------------------------------------------------
    //  Indicates if the first transaction of generating this report should
    //  start out under the normal banner.
    //
    //  A return value of false would result in the invocation of
    //  'report_display.php' in a separate window (or tab in some browsers)
    //  which displays the report without any banner.
    //
    //  If parameters need to be collected for the report, you cannot 
    //  return false, because the first transaction is used to collect
    //  those parameters and it should be in the normal window under the
    //  standard banner.
    //--------------------------------------------------------------------
    static function start_inline() {
        return true;
    }


    //--------------------------------------------------------------------
    //  Indicates if the report should be put in the pulldown menu.
    //  Lesser used reports would likely return false so as to avoid
    //  cluttering up that menu.
    //  If some reports return false, then there will be an "(Others)"
    //  item in the pulldown menu which will bring the user to a page
    //  with all the report options displayed for selection.
    //--------------------------------------------------------------------
    static function in_pulldown_menu() {
        return true;
    }


    //--------------------------------------------------------------------
    //  Checks permissions for a user with respect to this report.
    //  The return value indicates if they can request/view the report.
    //--------------------------------------------------------------------
    static function can_request(app_user $app_user=null) {
        return true;
    }


    //--------------------------------------------------------------------
    //  Generate a URL for a particular report, to be used for a link in
    //  a selection list/menu.
    //
    //  Generally, this wouldn't be overridden.
    //--------------------------------------------------------------------
    static function gen_url($report_type, $inline, $params=null) {
        $url = $inline
                ? "?datatype=report&report_type=$report_type"
                : "report_display.php?report_type=$report_type";
        if ($params) {
            foreach ($params as $key => $val) {
                $url .= sprintf("&%s=%s",
                    htmlspecialchars($key, ENT_QUOTES),
                    htmlspecialchars($key, ENT_QUOTES)
                    );
            }
        }
        return $url;
    }


    //--------------------------------------------------------------------
    //
    //--------------------------------------------------------------------
    static function gen_pd_column() {
        if (count(self::$reports_list) == 0) {
            self::get_reports_list();
        }

        $have_others = false;
        $pd_col = new pulldown_column('REPORT');
        foreach (self::$reports_list as $rep) {
            $in_pd = call_user_func(array($rep, 'in_pulldown_menu'));
            if (!$in_pd) {
                $have_others = true;
                continue;
            }

            $inline = call_user_func(array($rep, 'start_inline'));
            $url = self::gen_url($rep, $inline);
            $long_name = call_user_func(array($rep, 'long_name'));

            $pd_item = new pulldown_item($long_name, $url);
            if (!$inline)
                $pd_item->set_target("report");
            $pd_col->add_item($pd_item);
        }

        if ($have_others) {
            $pd_item = new pulldown_item('(SELECT from ALL)',
                    "?datatype=report&report_type=report");
            $pd_col->add_item($pd_item);
        }

        return $pd_col;
    }


    //--------------------------------------------------------------------
    //  Display a selector page for all of the reports.
    //
    //  This method will be overridden for each report type !
    //--------------------------------------------------------------------
    static function gen_display(request $req) {
        if (count(self::$reports_list) == 0) {
            self::get_reports_list();
        }

        $items = array();
        foreach (self::$reports_list as $rep) {
            $items[$rep] = call_user_func(array($rep, 'long_name'));
        }

        $selector = new selector('report');
        $selector->set_anchor_fmt_func(array('report','format_anchor'));

        $html = $selector->format_html($items);

        return $html;
    }


    //--------------------------------------------------------------------
    //  Generally not to be overridden.
    //--------------------------------------------------------------------
    static function format_anchor($rep, $label) {
        $start_inline = call_user_func(array($rep, 'start_inline'));

        $fmt_str = $start_inline ?
            "<A href='?datatype=report&report_type=%s'>%s</A>" :
            "<A target='report' " . 
            "href='report_display.php?datatype=report&report_type=%s'>%s</A>";

        return sprintf($fmt_str, $rep, htmlspecialchars($label, ENT_QUOTES));
    }

}
