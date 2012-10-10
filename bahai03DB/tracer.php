<?php

class tracer {

    static private $trace_text = '';

    //------------------------------------------------------------------
    static public function trace_start() {
        if (!(array_key_exists('trace', $_SESSION) && $_SESSION['trace']))
            return;

        error_reporting(E_ALL | E_STRICT);

        self::$trace_text = <<<TRACE_INIT_HTML

<script type='text/javascript'>
    var new_window;
    new_window = window.open('','name',
            'height=800,width=800,scrollbars=yes,menubar=yes');
    new_window.document.writeln("<HTML><BODY><TABLE border='1'>");

TRACE_INIT_HTML;

        $input = $_SERVER['REQUEST_METHOD'] == 'GET' ? $_GET : $_POST;
        foreach ($input as $key => $val) {
            $k = htmlspecialchars($key, ENT_QUOTES);
            $v = htmlspecialchars($val, ENT_QUOTES);

            self::$trace_text .= <<<TRACE_DATA_HTML
    new_window.document.writeln("<TR><TD>{$k}</TD><TD>{$v}</TD></TR>");
TRACE_DATA_HTML;
        }

        self::$trace_text .= 'new_window.document.writeln("</TABLE>\n");';
    }


    //------------------------------------------------------------------
    static public function trace_sql($sql) {
        if (!(array_key_exists('trace', $_SESSION) && $_SESSION['trace']))
            return;

        $safe_sql = htmlspecialchars($sql, ENT_QUOTES);

        self::$trace_text .= <<<TRACE_SQL_HTML
    new_window.document.writeln("<P>");
    new_window.document.writeln("<PRE>{$safe_sql}</PRE>");
    new_window.document.writeln("<HR>");
TRACE_SQL_HTML;

    }


    //------------------------------------------------------------------
    static public function trace_end() {
        if (!(array_key_exists('trace', $_SESSION) && $_SESSION['trace']))
            return;

        self::$trace_text .= <<<TRACE_END_HTML
    new_window.document.writeln("</BODY></HTML>");
    new_window.document.close();
</script>
TRACE_END_HTML;

        print(self::$trace_text);
    }

}
