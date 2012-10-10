<?php

/*

Persons (both members and non-members) are used all over in our data.
Thus a person must be selected (or perhaps created) in many contexts
throughout the input forms.  In many bahai communities, there are way too
many members to put in a pulldown list.  This would especially be true
where one has to select from the multiple communities in a cluster.
When one considers the many non-members referenced in the member data
(e.g., for healthcare providers), the numbers are even larger.

Furthermore, a natural user interface would call for creating persons on
the fly when entering data in a form, without losing context.

This object provides for a popup window used to offer a sophisticated 
interface to select or create a person.

This object is configurable as to how it behaves.

If it allows selection and there are too many to display, tabbed panes
can be used to display them.
Additionally, if there are too many even for tabbed panes, links to subsets
of the alphabet (by last name) are provided.

Restrictions can chosen by the user to make the display less cumbersome.


The display is broken down into tabbed panes:
 1) The CREATE pane, displayed if create is allowed.
 2) The SEARCH pane, displayed if select is allowed and the links to
    all possible members do NOT all fit on one page (without tabs).
 3) The SELECT pane(s), displayed if select is allowed and there are
    few enough members for all the links to be displayed.
    If they can all fit on one page then there will be one select pane,
    otherwise the display will be broken down by alphabetical order of
    last name and displayed on multiple panes.

If the above logic dictates the need for only one pane, panes will not
be used.

*/


class person_popup {

    const member_code   =  1;
    const guest_code    =  2;
    const seeker_code   =  3;
    const external_code =  4;

    static public $category_lookup = array(
        1 => 'member',
        2 => 'guest',
        3 => 'seeker',
        4 => 'external'
        );
        

    const member_bit    =  1;
    const guest_bit     =  2;
    const seeker_bit    =  4;
    const external_bit  =  8;
    const create_bit    = 16;
    const select_bit    = 32;
    const multi_bit     = 64;

    const category_bits = 15;

    static private $valid_categories =
            array('member', 'guest', 'seeker', 'external');
   
    const num_columns = 2;
    const max_in_column = 3;  //30;
    const max_select_panes = 8;


    private $cap;                   // capability mask

    private $person_category_mask;  // types of person permitted
                                    // (see bits defined at top)
    private $can_create; 
    private $can_select;

    private $update_function;

    private $cluster;

    private $search_communities;
    private $search_category;
    private $search_lastname;

    private $bahai_cmty_id;

    private $bahai_communities;


    //-----------------------------------------------------------------
    public function __construct($capabilities, $update_function) {

        $this->cap = $capabilities;
        if (!($this->cap & 15)) {
            $this->cap |= 15;
        }

        $this->person_category_mask = $this->cap & self::category_bits;
        $this->can_create = (($this->cap & self::create_bit) != 0);
        $this->can_select = (($this->cap & self::select_bit) != 0);

        $this->update_function = $update_function;

        $this->crosscheck_capabilities();

        $sess = $_SESSION['app_session'];
        $loc = $sess->get_bahai_community();
        if ($loc) {
            $this->bahai_cmty_id = $loc->get_key();
        }
    }


    //-----------------------------------------------------------------
    static public function tabs_group_name() {
        return 'person_popup_tabs';
    }


    //-----------------------------------------------------------------
    //  Make sure that the capabilities specified in parameters 
    //  doesn't exceed that of the session user.
    //-----------------------------------------------------------------
    private function crosscheck_capabilities() {

    }


    //-----------------------------------------------------------------
    static public function new_from_request($parms) {
        $ps = new person_popup($parms['cap'], $parms['update_function']);

        if (array_key_exists('cluster', $parms))
            $ps->set_cluster($parms['cluster']);

        foreach (array('communities','category','lastname') as $name) {
            if (array_key_exists($name, $parms))
                $ps->add_search_parm($name, $parms[$name]);
        }

        // VERIFY that specified communities are all within specified cluster.

        return $ps;
    }


    //-----------------------------------------------------------------
    public function set_cluster($cluster) {
        $this->cluster = $cluster;
    }


    //-----------------------------------------------------------------
    public function add_search_parm($name, $value) {
        switch ($name) {
            case 'communities':
                $this->search_communities =
                        is_array($value) ? $value : explode(',', $value);
                break;
                
            case 'category':
                if (!in_array($value, self::$valid_categories)) {
                    die("Invalid category: $value");
                }
                $this->search_category = $value;
                break;

            case 'lastname':
                $this->search_lastname = $value;
                break;

            default:
               die("Shouldn't get here");
        }
    }


    //-----------------------------------------------------------------
    public function gen_url() {
        $url = sprintf("ps_main.php?cap=%d&update_function=%s",
                $this->cap,
                $this->update_function
                );

        if ($this->cluster) {
            $url .= '&cluster=' . $this->cluster;
        }

        return $url;
    }


    //-----------------------------------------------------------------
    public function format_button($label) {
        $url = $this->gen_url();

        $size_and_pos = 'top=200,left=200,width=900,height=700';

        $html = <<<BUTTON_HTML

<button type='button' onClick=
"javascript:window.open('$url','POPPER','{$size_and_pos},menubar=1');">
{$label}
</button>

BUTTON_HTML;
    
        return $html;
    }


    //-----------------------------------------------------------------
    public function format_query_string() {

        if ($this->cluster) {
            array_push($vars, "cluster=" . $this->cluster);
        }

        if ($this->communities) {
            array_push($vars, "communities=" . implode(',', $this->communities));
        }

        return implode('&', $vars);
    }


    //-----------------------------------------------------------------
    public function title() {
        return "Select member";
    }


    //-----------------------------------------------------------------
    public function format_person_link($person_id, $label) {
        return "<a href='#' " .
            "onclick='handle_values($person_id, \"$label\");'> $label</a>\n";
    }


    //-----------------------------------------------------------------
    static function format_onclick($id, $label) {
        return "handle_values($id, \"$label\");";
    }


    //-----------------------------------------------------------------
    private function format_select_panes($persons) {

        $selector = new selector('person');
        $selector->set_onclick_formatter(
                array('person_popup','format_onclick') );

        $html = $selector->format_html($persons);

/*
        $fmt_args = array_fill(0, self::num_columns * self::max_in_column, '');

        $i=0;
        foreach ($persons as $id => $label) {
            $col = floor($i / self::max_in_column);
            $row = ($i % self::max_in_column);
            $out_ind = $row * self::num_columns + $col;
 
            $fmt_args[$out_ind] = self::format_person_link($id, $label);

            ++$i;
        }

        $style="style='padding-right:20'";
        $row_fmt = "<tr>\n" .
                   str_repeat("  <td>%s</td><td>&nbsp;&nbsp;</td>\n",
                              self::num_columns) .
                   "</tr>\n";

        $fmt_str = "<TABLE >\n" .
                   str_repeat($row_fmt, self::max_in_column) .
                   "</TABLE>\n";

        $pane = vsprintf($fmt_str, $fmt_args);

        return array($pane);
*/
        return array($html);
    }


    //-----------------------------------------------------------------
    private function format_search_pane($overflow) {
        return "SEARCH PANE";
    }


    //-----------------------------------------------------------------
    private function format_create_pane() {

        $person_category_options = '';
        if ($this->person_category_mask & self::member_bit)
            $person_category_options .=
                "<OPTION value='1' SELECTED>member</OPTION>\n";

        if ($this->person_category_mask & self::guest_bit)
            $person_category_options .=
                "<OPTION value='2'>guest</OPTION>\n";

        if ($this->person_category_mask & self::seeker_bit)
            $person_category_options .=
                "<OPTION value='3'>seeker</OPTION>\n";

        if ($this->person_category_mask & self::external_bit) {
            $sel = ($this->person_category_mask & self::member_bit) ?
                    '' : 'SELECTED';
            $person_category_options .=
                "<OPTION value='4' $sel>external</OPTION>\n";
        }

        $request = new request(
                array('datatype' => 'person', 'mode' => 'create'));

        $popup_params = new person_popup_params( array(
            'onsubmit' => 'return process_form_data(this);',
            'category_list' => array(1,2,3,4)
            ) );

        $form_html =
            person::gen_entry_form($request, null, null, $popup_params);

        return $form_html;
    }



    //-----------------------------------------------------------------
    public function format_html_body() {

        $window_close = ($this->cap & self::multi_bit) ? '' :
                   "window.close();";

        $html = <<<SEL_JS_HTML
<SCRIPT type='text/javascript'>

var person_popup_tabs;

function handle_values(id, label) {
    window.opener.{$this->update_function}(id, label);
    {$window_close}
}

</SCRIPT>

SEL_JS_HTML;

        $this->read_communities();

        $num_panes = 0;

        // Create pane if allowed.
        if ($this->can_create) {
            ++$num_panes;
        }

        // For select, we have a pane UNLESS there are too many to fit.
        if ($this->can_select) {
            
            $persons = $this->read_person_rows();

            $num_select_panes = ceil( count($persons) / 
                    (self::num_columns * self::max_in_column) );

            if (count($persons) == 0) {
                $need_select_pane = false;
                $need_search_pane = false;
            }

            else if ($num_select_panes == 1) {
                $need_select_pane = true;
                $need_search_pane = false;
            }

            else if ($num_select_panes > self::max_select_panes) {
                $need_select_pane = false;
                $need_search_pane = true;
            }

            else {
                $need_select_pane = true;
                $need_search_pane = true;
            }
        }
        else {
            $num_select_panes = 0;
            $need_select_pane = false;
            $need_search_pane = false;
        }

        $need_search_pane = false; // TO DISABLE IT UNTIL IMPLEMENTED

        $num_panes = $num_select_panes +
                ($this->can_create ? 1 : 0) + ($need_search_pane ? 1 : 0);

        if ($num_panes == 1) {
            if ($this->can_create) {
                $html .= $this->format_create_pane();
            }

            else if ($need_search_pane) {
                $html .= $this->format_search_pane(true);
            }

            else {
                $select_panes = $this->format_select_panes($persons);
                $html .= $select_panes[0];
            }
        }

        else {
            $pg = new tabs_group(self::tabs_group_name());
            if ($need_select_pane) {
                $select_panes = $this->format_select_panes($persons);
                if (count($select_panes) == 1) {
                    $pg->add_pane("Select", $select_panes[0]);
                }
                else {
                    for ($i=0; $i<count($select_panes); ++$i) {
                //$pg->add_pane("Select",
                        //      $this->format_select_panes($persons) );
                        //$start
                    }
                }
            }

            if ($need_search_pane) {
                $search_html = $this->format_search_pane(!$need_select_pane);
                $pg->add_pane("Search", $search_html);
            }

            if ($this->can_create) {
                $pg->add_pane("Create", $this->format_create_pane() );
            }

            $html .= $pg->format_html();
            $js_init = $pg->format_js_init();

            $html .= "<script type='text/javascript'>\n$js_init\n</script>\n";
        }

        if ($this->cap & self::multi_bit) {
            $html .= "<P><BUTTON type='button' " .
                     "onclick='window.close();'>Close Window</BUTTON>";
        }

        return $html;
    }


    //-----------------------------------------------------------------
    private function read_communities() {
        $query = "SELECT bahai_cmty_id, bahai_cmty_name FROM bahai_community " .
                 "WHERE ";

        if ($this->cluster) {
            $query .= "bahai_cluster = '{$this->cluster}' AND ";
            if ($this->search_communities) {
                $query .= "bahai_cmty_id IN (" . implode(',', $communities) .
                          ") AND ";
            }
        }
        else {
            $query .= "bahai_cmty_id = {$this->bahai_cmty_id} AND ";
        }

        $query = substr($query, 0, strlen($query)-4);  // truncate final AND
        $query .= " ORDER BY bahai_cmty_name;";
      
        $res = app_session::pg_query($query);
        while ($row = pg_fetch_assoc($res)) {
            $this->bahai_communities[$row['bahai_cmty_id']] =
                    $row['bahai_cmty_name'];
        }
    }


    //-----------------------------------------------------------------
    private function get_category_list() {

        if ($this->search_category) {
            $categories = array($this->search_category);
        }
        else {
            $categories = array();
            if ($this->person_category_mask & self::member_bit) {
                array_push($categories, self::member_code);
            }
            if ($this->person_category_mask & self::guest_bit) {
                array_push($categories, self::guest_code);
            }
            if ($this->person_category_mask & self::seeker_bit) {
                array_push($categories, self::seeker_code);
            }
            if ($this->person_category_mask & self::external_bit) {
                array_push($categories, self::external_code);
            }
        }

        return $categories;
    }


    //-----------------------------------------------------------------
    private function read_person_rows() {

        $query = sprintf(
                "SELECT person_label.person_id, label " .
                "FROM person_label,person WHERE " .
                "person.person_id = person_label.person_id AND " .
                "bahai_cmty_id IN (%s) AND " .
                "person_category in (%s) " .
                "%s " .
                " ORDER BY last_name,first_name;",
                implode(',', array_keys($this->bahai_communities)),
                implode(',', $this->get_category_list() ),
                ($this->search_lastname ?
                    "last_name LIKE '{$this->search_lastname}%'" : "")
                );

        $persons = array();
        $res = app_session::pg_query($query);
        while ($row = pg_fetch_assoc($res)) {
            $persons[$row['person_id']] = $row['label'];
        }
        
        return $persons;
    }

}
