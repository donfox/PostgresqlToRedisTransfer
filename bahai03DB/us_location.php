<?php // $Id$

require_once('html_utils.php');

class us_location extends auto_construct implements type_in_db {

    public $bahai_cmty_id;
    public $state_code;
    public $city;


    //----------------------------------------------------------------
    // Can be constructed from either:
    //   Database row data  OR  Form data ($_POST).
    //----------------------------------------------------------------
    function __construct(array $array_data) {
        $this->_copy_properties($array_data);
    }


    //----------------------------------------------------------------
    function insert_to_db() {
       $query = sprintf(
                "SELECT insert_us_location(%d,'%s','%s');",
                $this->bahai_cmty_id,
                pg_escape_string($this->state_code),
                pg_escape_string($this->city) );

        $res = app_session::pg_query($query);
        if (!$res) {
            die("Location insert error");
        }

        $row = pg_fetch_array($res);

        return $this->bahai_cmty_id;
    }


    //----------------------------------------------------------------
    function update_in_db() {

        $query = sprintf(
                "SELECT update_us_location(%d,'%s','%s');",
                $this->bahai_cmty_id,
                pg_escape_string($this->state_code),
                pg_escape_string($this->city) );

        $res = app_session::pg_query($query);
        if (!$res) {
            die("Location update error");
        }

        $row = pg_fetch_array($res);

        return $this->bahai_cmty_id;
    }


    //----------------------------------------------------------------
    static function delete_from_db($key) {
        die("us_location::delete_from_db not implemented");
    }


    //----------------------------------------------------------------
    static function read_from_db($key) {
        $query = "SELECT * from us_location WHERE bahai_cmty_id = $key;";
        $res = app_session::pg_query($query);
        $row = pg_fetch_array($res);
 
        return $row ?  new us_location($row) : null;
    }


    //----------------------------------------------------------------
    static function format_fields($obj=null) {

        $state_code = $obj ? $obj->state_code : null;

        $state_options_html =
            html_utils::format_options(us_state::$options, $state_code);

        $fmt_str = <<<LOCATION_HTML

<table>
  <tr>
    <td colspan='2'><label class='field_header'>State</label></td>
    <td colspan='2'><label class='field_header'>City</label></td>
  </tr>
  <tr>
    <td>
      <SELECT name='state_code' id='state_code'>
      <OPTION value=''>(Select a state)</OPTION>
      {$state_options_html}
      </SELECT>

    </td>
    <td>&nbsp;&nbsp;&nbsp;&nbsp;</td>
    <td><input name='city' type='text' value='%s'/></td>
  </tr>
</table>

LOCATION_HTML;
    
        $html = sprintf($fmt_str,
                $obj ? $obj->city : '');

        return $html;
    }

}
?>
