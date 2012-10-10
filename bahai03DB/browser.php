<?php

class browser {

    static function browser_check() {

        $browser = $_SERVER['HTTP_USER_AGENT'];
        if ((!$browser)
            or
            (preg_match('/MSIE\s+(\d+)/', $browser, $matches) and
             $matches[1] >= 7)
            or
            (preg_match('/Windows.* Firefox\/(\d\.\d+)/', $browser, $matches)
             and $matches[1] >= 1.5)
            or
            (preg_match('/Linux.* Firefox\/(\d\.\d+)/', $browser, $matches) 
             and $matches[1] >= 2)
           ) {
            return '';
        }

        else return <<<WARNING_HTML
<h1>
Your browser may not be supported by this Bah&aacute;'&iacute; application.
</h1>
<p>
Supported browsers include:
<ul>
<li>Microsoft Internet Explorer version 7</li>
<li>Firefox version 1.5.0.11 on Windows</li>
<li>Firefox version 2 on linux</li>
<li>... and more recent versions of these browsers (probably).</li>
</ul>
<hr>
<p>
WARNING_HTML;
    }
}
