<?php

class event_counts extends auto_construct {

    public $event_id;

    public $counts_combined;
    public $num_bahai_adults;
    public $num_non_bahai_adults;
    public $num_new_non_bahai_adults;
    public $num_bahai_youths;
    public $num_non_bahai_youths;
    public $num_new_non_bahai_youths;
    public $num_bahai_juniors;
    public $num_non_bahai_juniors;
    public $num_new_non_bahai_juniors;
    public $num_bahai_children;
    public $num_non_bahai_children;
    public $num_new_non_bahai_children;


    //-----------------------------------------------------------------
    function __construct(array $array_data) {
        $this->_copy_properties($array_data);
    }


    //-----------------------------------------------------------------
    function is_empty() {
        foreach (array(
                'num_bahai_adults', 'num_non_bahai_adults',
                    'num_new_non_bahai_adults', 
                'num_bahai_youths', 'num_non_bahai_youths',
                    'num_new_non_bahai_youths', 
                'num_bahai_juniors', 'num_non_bahai_juniors',
                    'num_new_non_bahai_juniors', 
                'num_bahai_children', 'num_non_bahai_children',
                    'num_new_non_bahai_children') as $fld) {
            if ($this->$fld > 0)
                return false;
        }

        return true;
    }


    //-----------------------------------------------------------------
    function __toString() {
        return '';
    }


    //-----------------------------------------------------------------
    static function read_from_db($key) {

        $query = "SELECT * from event_counts WHERE event_id = {$key};";
        $res = app_session::pg_query($query);
        $row = pg_fetch_assoc($res);

        return $row ? new event_counts($row) : null;
    }


    //-----------------------------------------------------------------
    function format_counts_combined() {

        return implode(':', array(
                $this->num_bahai_adults,       
                $this->num_non_bahai_adults,
                $this->num_new_non_bahai_adults,

                $this->num_bahai_youths,        
                $this->num_non_bahai_youths, 
                $this->num_new_non_bahai_youths,

                $this->num_bahai_juniors,       
                $this->num_non_bahai_juniors,
                $this->num_new_non_bahai_juniors,

                $this->num_bahai_children,     
                $this->num_non_bahai_children, 
                $this->num_new_non_bahai_children
                ) );
    }


    //-----------------------------------------------------------------
    function event_set_counts_in_db() {
        $query = sprintf("SELECT event_set_counts(%d," .
                         "CAST (%d as smallint)," .
                         "CAST (%d as smallint)," .
                         "CAST (%d as smallint)," .
                         "CAST (%d as smallint)," .
                         "CAST (%d as smallint)," .
                         "CAST (%d as smallint)," .
                         "CAST (%d as smallint)," .
                         "CAST (%d as smallint)," .
                         "CAST (%d as smallint)," .
                         "CAST (%d as smallint)," .
                         "CAST (%d as smallint)," .
                         "CAST (%d as smallint) );",
                $this->event_id,
                $this->num_bahai_adults,
                $this->num_bahai_youths,
                $this->num_bahai_juniors,
                $this->num_bahai_children,
                $this->num_non_bahai_adults,
                $this->num_non_bahai_youths,
                $this->num_non_bahai_juniors,
                $this->num_non_bahai_children,
                $this->num_new_non_bahai_adults,
                $this->num_new_non_bahai_youths,
                $this->num_new_non_bahai_juniors,
                $this->num_new_non_bahai_children );

        $res = app_session::pg_query($query);
    }
    

    //-----------------------------------------------------------------
    static function format_fields($obj) {
        $bahai = BAHAI;
 
        $fmt_str = <<<COUNTS_HTML
    
<TABLE cellpadding='8'>
<tr>
  <th>
     <input type='hidden' name='counts_combined' value='%s'/>
  </th>
  <th>{$bahai}</th>
  <th>non-{$bahai}</th>
  <th>New non-{$bahai}</th>
</tr>

<tr>
  <td>Adults</td>
  <td>
    <input type='text' name='num_bahai_adults' size='2' value='%d'/>
  </td>
  <td>
    <input type='text' name='num_non_bahai_adults' size='2' value='%d'/>
  </td>
  <td>
    <input type='text' name='num_new_non_bahai_adults' size='2' value='%d'/>
  </td>
</tr>
<tr>
  <td>Youths</td>
  <td>
    <input type='text' name='num_bahai_youths' size='2' value='%d'/>
  </td>
  <td>
    <input type='text' name='num_non_bahai_youths' size='2' value='%d'/>
  </td>
  <td>
    <input type='text' name='num_new_non_bahai_youths' size='2' value='%d'/>
  </td>
</tr>
<tr>
  <td>Juniors</td>
  <td>
    <input type='text' name='num_bahai_juniors' size='2' value='%d'/>
  </td>
  <td>
    <input type='text' name='num_non_bahai_juniors' size='2' value='%d'/>
  </td>
  <td>
    <input type='text' name='num_new_non_bahai_juniors' size='2' value='%d'/>
  </td>
</tr>
<tr>
  <td>Children</td>
  <td>
    <input type='text' name='num_bahai_children' size='2' value='%d'/>
  </td>
  <td>
    <input type='text' name='num_non_bahai_children' size='2' value='%d'/>
  </td>
  <td>
    <input type='text' name='num_new_non_bahai_children' size='2' value='%d'/>
  </td>
</tr>
</TABLE>
    
COUNTS_HTML;
    
        $values = ($obj) ?  array(
                $obj->num_bahai_adults,       
                $obj->num_non_bahai_adults,
                $obj->num_new_non_bahai_adults,

                $obj->num_bahai_youths,        
                $obj->num_non_bahai_youths, 
                $obj->num_new_non_bahai_youths,

                $obj->num_bahai_juniors,       
                $obj->num_non_bahai_juniors,
                $obj->num_new_non_bahai_juniors,

                $obj->num_bahai_children,     
                $obj->num_non_bahai_children, 
                $obj->num_new_non_bahai_children
                )
            : array_fill(0, 12, '0');
    
        array_unshift($values,
            $obj ? $obj->format_counts_combined() : '0:0:0:0:0:0:0:0:0:0:0:0'
            );
    
        $html = vsprintf($fmt_str, $values);
    
        return $html;
    }

};
