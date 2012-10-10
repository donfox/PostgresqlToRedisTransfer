<?php

class pane {
    public $selector_label;
    public $html_content;
    public $pg;  // parent tabs_group


    public function __construct($pg, $selector_label, $html_content) {
        $this->pg = $pg;
        $this->html_content = $html_content;
        $this->selector_label = $selector_label;
    }


    public function format_selector($num) {
        $html = sprintf("<li><a href='#' id='%s_selector_%d' " .
                "onClick='javascript:%s.selector_clicked(%d);'>%s</a></li>\n",
                $this->pg->prefix, $num,
                $this->pg->prefix, $num,
                $this->selector_label);

        return $html;
    }


    public function format_pane($num) {
        return sprintf("<div id='%s_pane_%d'>\n%s\n</div>\n",
                $this->pg->prefix, $num, $this->html_content);
    }
}



class tabs_group {

    public $prefix;
    private $panes = array();
    static private $prefixes = array();


    function __construct($prefix) {
        $this->prefix = $prefix;
        array_push(self::$prefixes, $prefix);
    }


    function add_pane($selector_label, $html_content) {
        array_push($this->panes,
            new pane($this, $selector_label, $html_content) );
    }


    //-------------------------------------------------------------------
    public function format_html() {

        $html = "<ul class='tabs'>\n";
        for ($i=0; $i<count($this->panes); ++$i) {
            $html .= $this->panes[$i]->format_selector($i+1);
        }
        $html .= "</ul>\n";

        for ($i=0; $i<count($this->panes); ++$i) {
            $html .= $this->panes[$i]->format_pane($i+1);
        }

        return $html;
    }


    //-------------------------------------------------------------------
    //  Generates html to initialize the tabs_group variable.
    //-------------------------------------------------------------------
    public function format_js_init() {
        $html = sprintf("    %s = new tabs_group('%s', %d);\n",
            $this->prefix, $this->prefix, count($this->panes) );

        return $html;
    }

}
