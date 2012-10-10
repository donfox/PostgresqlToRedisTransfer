<?php // $Id$

require_once('html_utils.php');

class ca_location extends auto_construct implements type_in_db {

    public $bahai_cmty_id;
    public $province_abbr;
    public $municipality;


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
                "SELECT insert_ca_location(%d,'%s','%s');",
                $this->bahai_cmty_id,
                pg_escape_string($this->province_abbr),
                pg_escape_string($this->municipality) );

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
                "SELECT update_ca_location(%d,'%s','%s');",
                $this->bahai_cmty_id,
                pg_escape_string($this->province_abbr),
                pg_escape_string($this->municipality) );

        $res = app_session::pg_query($query);
        if (!$res) {
            die("Location update error");
        }

        $row = pg_fetch_array($res);

        return $this->bahai_cmty_id;
    }


    //----------------------------------------------------------------
    static function delete_from_db($key) {
        die("ca_location::delete_from_db not implemented");
    }


    //----------------------------------------------------------------
    static function read_from_db($key) {
        $query = "SELECT * from ca_location WHERE bahai_cmty_id = $key;";
        $res = app_session::pg_query($query);
        $row = pg_fetch_array($res);
 
        return $row ?  new ca_location($row) : null;
    }


    //----------------------------------------------------------------
    static function format_fields($obj=null) {

        $province_abbr = $obj ? $obj->province_abbr : null;

        $province_options_html =
            html_utils::format_options(ca_province::$options, $province_abbr);

        $fmt_str = <<<LOCATION_HTML

<table>
  <tr>
    <td colspan='2'><label class='field_header'>Province</label></td>
    <td colspan='2'><label class='field_header'>Municipality</label></td>
  </tr>
  <tr>
    <td>
      <SELECT name='province_abbr' id='province_abbr'>
      <OPTION value=''>(Select a province)</OPTION>
      {$province_options_html}
      </SELECT>

    </td>
    <td>&nbsp;&nbsp;&nbsp;&nbsp;</td>
    <td><input name='municipality' type='text' value='%s'/></td>
  </tr>
</table>

LOCATION_HTML;
    
        $html = sprintf($fmt_str,
                $obj ? $obj->municipality : '');

        return $html;
    }

}
?>
