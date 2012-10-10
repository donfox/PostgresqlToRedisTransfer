<?php

class gb_address extends address implements type_in_db {

    public $address_1;
    public $building_name;
    public $street_address;
    public $locality;
    public $post_town;
    public $postcode;


    //--------------------------------------------------------------------
    function __construct(array $array_data, $prefix=null) {
        $this->_copy_properties($array_data, $prefix);
    }


    //--------------------------------------------------------------------
    function __toString() {

        $pieces = array();
        foreach (array('address_1', 'building_name', 'street_address',
                       'post_town') as $fld) {

            if ($this->$fld) {
                array_push($pieces, $this->$fld);
            }

        }

        return implode(',', $pieces);
    }


    //--------------------------------------------------------------------
    function is_blank() {
        foreach (array( 'address_1', 'building_name', 'street_address',
                'locality', 'post_town', 'postcode') as $fld) {
            if ($this->{$fld})
                return false;
        }

        return true;
    }


    //--------------------------------------------------------------------
    static function read_from_db($key) {
        $query = sprintf("SELECT * from gb_address where address_id = %d;",
                $key);
        $res = app_session::pg_query($query);
        $row = pg_fetch_array($res);
        if (!$row)
            return null;

        return new address($row);
    }


    //--------------------------------------------------------------------
    function insert_to_db() {
        $query = sprintf(
                "SELECT insert_gb_address('%s','%s','%s','%s','%s','%s');",
                pg_escape_string($this->address_1),
                pg_escape_string($this->building_name),
                pg_escape_string($this->street_address),
                pg_escape_string($this->locality),
                pg_escape_string($this->post_town),
                pg_escape_string($this->postcode) );
                //pg_escape_string($this->country_code)

        $res = app_session::pg_query($query);
        if (!$res) {
            die("Address insert error");
        }

        $row = pg_fetch_array($res);

        $this->address_id = $row[0];

        return $this->address_id;
    }


    //--------------------------------------------------------------------
    function update_in_db() {
        $query = sprintf(
                "SELECT update_gb_address(%d,'%s','%s','%s','%s','%s','%s');",
                $this->address_id,
                pg_escape_string($this->address_1),
                pg_escape_string($this->building_name),
                pg_escape_string($this->street_address),
                pg_escape_string($this->locality),
                pg_escape_string($this->post_town),
                pg_escape_string($this->postcode) );

        $res = app_session::pg_query($query);
        if (!$res) {
            die("Address update error");
        }

        return $this->address_id;
    }


    //--------------------------------------------------------------------
    static function format_fields($prefix, $obj=null, $onchange_js=null) {

        $status_html = parent::format_status_html($prefix, $obj);

        $fmt_str = <<<ADDRESS_HTML
<table>
<tr>
<td colspan='5'>

{$status_html}

<label for='{$prefix}address_1' class='field_header'>Address line 1</label>
<br>
  <input maxlength='100' size='60' name='{$prefix}address_1'
   onchange="javascript:address_changed('{$prefix}');{$onchange_js}"
   value='%s'   />
</td>
</tr>

<tr>
<td>
<label for='{$prefix}building_name' class='field_header'>Building</label>
<br>
  <input maxlength='30' size='25' name='{$prefix}building_name'
   onchange="javascript:address_changed('{$prefix}');{$onchange_js}"
   value='%s'   />
</td>

<td>&nbsp; &nbsp;</td>

<td colspan='2'>
<label for='{$prefix}street_address' class='field_header'>
Street Address</label>
<br>
  <input maxlength='70' size='50'
   name='{$prefix}street_address' id='{$prefix}street_address'
   onchange="javascript:address_changed('{$prefix}');{$onchange_js}"
  value='%s' />
</td>
</tr>

<tr>
<td colspan='2'>
<label for="{$prefix}locality" class='field_header'>Locality</label>
<br>
  <input maxlength='30' size='20'
   name='{$prefix}locality' id='{$prefix}locality'
   onchange="javascript:address_changed('{$prefix}');{$onchange_js}"
  value='%s' />
</td>

<td>
<label for='{$prefix}post_town' class='field_header'>Post Town</label>
<br>
<input maxlength='40' size='25' name='{$prefix}post_town'
   onchange="javascript:address_changed('{$prefix}');{$onchange_js}"
  value='%s'/>
</td>

<td>
<label for='{$prefix}postcode' class='field_header'>PostCode</label>
<br>
<input maxlength='10' size='10' name='{$prefix}postcode'
   onchange="javascript:address_changed('{$prefix}');{$onchange_js}"
  value='%s'/>
</td>

</tr>
</table>

ADDRESS_HTML;

        $html = sprintf($fmt_str,
            ($obj ? htmlspecialchars($obj->address_1, ENT_QUOTES) : ''),
            ($obj ? htmlspecialchars($obj->building_name, ENT_QUOTES) : ''),
            ($obj ? htmlspecialchars($obj->street_address, ENT_QUOTES) : ''),
            ($obj ? htmlspecialchars($obj->locality, ENT_QUOTES) : ''),
            ($obj ? htmlspecialchars($obj->post_town, ENT_QUOTES) : ''),
            ($obj ? htmlspecialchars($obj->postcode, ENT_QUOTES) : '')
            );

        return $html;

    }  // END  format_fields

}
