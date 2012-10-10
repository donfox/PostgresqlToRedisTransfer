<?php // $Id$

require_once('html_utils.php');

class gb_location extends auto_construct implements type_in_db {

    public $bahai_cmty_id;
    public $post_town;


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
                "SELECT insert_gb_location(%d,'%s');",
                $this->bahai_cmty_id,
                pg_escape_string($this->post_town) );

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
                "SELECT update_gb_location(%d,'%s');",
                $this->bahai_cmty_id,
                pg_escape_string($this->post_town) );

        $res = app_session::pg_query($query);
        if (!$res) {
            die("Location update error");
        }

        $row = pg_fetch_array($res);

        return $this->bahai_cmty_id;
    }


    //----------------------------------------------------------------
    static function delete_from_db($key) {
        die("gb_location::delete_from_db not implemented");
    }


    //----------------------------------------------------------------
    static function read_from_db($key) {
        $query = "SELECT * from gb_location WHERE bahai_cmty_id = $key;";
        $res = app_session::pg_query($query);
        $row = pg_fetch_array($res);
 
        return $row ?  new gb_location($row) : null;
    }


    //----------------------------------------------------------------
    static function format_fields($obj=null) {

        $fmt_str = <<<LOCATION_HTML

<table>
  <tr>
    <td colspan='2'><label class='field_header'>Post Town</label></td>
  </tr>
  <tr>
    <td><input name='post_town' type='text' value='%s'/></td>
  </tr>
</table>

LOCATION_HTML;
    
        $html = sprintf($fmt_str, $obj ? $obj->post_town : '');

        return $html;
    }

}
?>
