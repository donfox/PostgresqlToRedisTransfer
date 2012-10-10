<?php


class pulldown_menu {

    public $columns = array();   // 

    public function gen_html() {
        print("<div class='pd_menu'>\n");
        foreach ($this->columns as $column) {
            print("\n<ul>\n");
            $column->gen_html();
            print("</ul>\n");
        }
        print("</div>\n");
    }


    public function add_column(pulldown_column $column) {
        array_push($this->columns, $column);
    }
}



class pulldown_column {

    public $column_header;
    public $column_link;
    public $items = array();

    public function __construct($column_header, $column_link=null) {
        $this->column_header = $column_header;
        if ($column_link)
            $this->column_link = $column_link;
    }

    public function add_item(pulldown_item $item) {
        array_push($this->items, $item);
    }


    public function gen_html() {
        $link = '';
        if ($this->column_link)
            $link = sprintf("href='%s'", $this->column_link);

        printf("<li>\n<a %s>%s</a>\n",
                $link, $this->column_header);

        if (count($this->items) > 0) {
            print("<table> <tbody><tr><td><ul>\n");

            foreach ($this->items as $item) {
                printf("<li><a href='%s' title='%s' %s>%s</a></li>\n", 
                    $item->link,
                    $item->label,
                    ($item->target ? "target=" . $item->target : ''),
                    $item->label
                    );
            }

            print("</ul></td></tr></tbody></table></a>\n");
        }
        print("</li>\n");
    }

}


class pulldown_item {
    public $label;
    public $link;
    public $target;

    public function __construct($label, $link) {
        $this->label = $label;
        $this->link = $link;
    }

    public function set_target($target) {
        $this->target = $target;
    }
}
