<?php

class selector {

    private $datatype;
    private $criteria;

    private $items;
    private $max_num_rows = 40;
    private $max_num_cols = 6;

    private $anchor_fmt_func;
    private $onclick_fmt_func;


    //--------------------------------------------------------------
    function __construct($datatype, $criteria=null) {

        $this->datatype = $datatype;
        $this->criteria = $criteria;
    }


    //--------------------------------------------------------------
    function set_anchor_fmt_func($anchor_fmt_func) {
        $this->anchor_fmt_func = $anchor_fmt_func;
    }


    //--------------------------------------------------------------
    function set_onclick_formatter($onclick_fmt_func) {
        $this->onclick_fmt_func = $onclick_fmt_func;
    }


    //--------------------------------------------------------------
    function set_dimensions($max_num_rows, $max_num_cols) {
        $this->max_num_rows = $max_num_rows;
        $this->max_num_cols = $max_num_cols;
    }


    //--------------------------------------------------------------
    function format_html($items) {
        if (!$items or count($items) == 0) {
            $func = array($this->datatype, 'get_select_items');
            if ($this->criteria) {
                $items = call_user_func($func, $this->criteria);
            }
        }
        return $this->format_select_table($items);
    }


    //--------------------------------------------------------------
    private function format_select_table($items) {

        if (count($items) == 0)
            return '';

        $portion_filled =
                count($items)/($this->max_num_rows * $this->max_num_cols);
        $num_rows = ceil($this->max_num_rows * sqrt($portion_filled));
        if ($num_rows > $this->max_num_rows)
            $num_rows = $this->max_num_rows;

        $num_cols = ceil(count($items)/$num_rows);

        $fmt_args = array_fill(0, $num_rows * $num_cols, '');


        //$num_rows = ceil(count($items)/$this->max_num_cols);

        $i=0;
        foreach ($items as $key => $label) {
            $col = floor($i / $num_rows);
            $row = ($i % $num_rows);
            $out_ind = $row * $num_cols + $col;

            $fmt_args[$out_ind] = $this->anchor_fmt_func ?
                call_user_func($this->anchor_fmt_func, $key, $label) :
                $this->format_anchor($key, $label);

            ++$i;
        }

        $style="style='padding-right:20'";
        $row_fmt = "<tr>\n" .
                   str_repeat("  <td>%s</td><td>&nbsp;&nbsp;</td>\n",
                              $num_cols) .
                   "</tr>\n";

        $fmt_str = "<TABLE >\n" .
                   str_repeat($row_fmt, $num_rows) .
                   "</TABLE>\n";

        $html = vsprintf($fmt_str, $fmt_args);

        return $html;
    }


    //--------------------------------------------------------------
    private function format_anchor($id, $label) {

        if ($this->onclick_fmt_func) {
            $onclick_text =
                call_user_func($this->onclick_fmt_func, $id, $label);
            $onclick_html = "ONCLICK='$onclick_text'";
            $href_value = '#';
        }
        else {
            $href_value = "?mode=update&datatype={$this->datatype}&key=$id";
            $onclick_html = '';
        }

        $html = sprintf("<A HREF='%s' %s>%s</A>\n",
                        $href_value, $onclick_html, $label);

        return $html;
    }
    
}
