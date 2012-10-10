<?php

class ca_address extends address implements type_in_db {

    public $address_1;
    public $address_2;
    public $municipality;
    public $province_abbr;
    public $postal_code;


    //--------------------------------------------------------------------
    function __construct(array $array_data, $prefix=null) {
        $this->_copy_properties($array_data, $prefix);
    }


    //--------------------------------------------------------------------
    function __toString() {
        return $this->address_1;
    }


    //--------------------------------------------------------------------
    function to_short_string() {
        return $this->address_1;
    }


    //--------------------------------------------------------------------
    function is_blank() {
        foreach (array('address_1', 'address_2', 'municipality',
                       'province_abbr', 'postal_code') as $fld) {
            if ($this->{$fld})
                return false;
        }

        return true;
    }


    //--------------------------------------------------------------------
    static function read_from_db($key) {
        $query = sprintf("SELECT * from ca_address where address_id = %d;",
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
                "SELECT insert_ca_address('%s','%s','%s','%s','%s','%s');",
                pg_escape_string($this->address_1),
                pg_escape_string($this->address_2),
                pg_escape_string($this->municipality),
                pg_escape_string($this->province_abbr),
                pg_escape_string($this->postal_code),
                'US' );
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
                "SELECT update_ca_address(%d,'%s','%s','%s','%s','%s','%s');",
                $this->address_id,
                pg_escape_string($this->address_1),
                pg_escape_string($this->address_2),
                pg_escape_string($this->municipality),
                pg_escape_string($this->province_abbr),
                pg_escape_string($this->postal_code),
                pg_escape_string($this->country_code) );

        $res = app_session::pg_query($query);
        if (!$res) {
            die("Address update error");
        }
    }


    //--------------------------------------------------------------------
    static function format_fields($prefix, $obj=null, $onchange_js=null) {

        $province_options_html =
                html_utils::format_options(ca_province::$options,
                    $obj ?  $obj->province_abbr : null);

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
<td colspan='5'>
<label for='{$prefix}address_2' class='field_header'>Address line 2</label>
<br>
  <input maxlength='100' size='60' name='{$prefix}address_2'
   onchange="javascript:address_changed('{$prefix}');{$onchange_js}"
   value='%s'   />
</td>
</tr>

<tr>
<td>
<label for='{$prefix}municipality' class='field_header'>Municipality</label>
<br>
  <input maxlength='30' size='20' name='{$prefix}municipality'
   id='{$prefix}municipality'
   onchange="javascript:address_changed('{$prefix}');{$onchange_js}"
  value='%s' />
</td>
<td>&nbsp;&nbsp;&nbsp;&nbsp;</td>

<td>
<label for="{$prefix}province_abbr" class='field_header'>
Province or Territory</label>
<br>

<SELECT name='{$prefix}province_abbr' id='{$prefix}province_abbr'
   onchange="javascript:address_changed('{$prefix}');{$onchange_js}"
>
<OPTION value=''>(Select a province)</OPTION>
{$province_options_html}
</SELECT>
</td>

<td>&nbsp;&nbsp;&nbsp;&nbsp;</td>

<td>
<label for='{$prefix}postal_code' class='field_header'>Postal Code</label>
<br>
<input maxlength='10' size='10' name='{$prefix}postal_code'
   onchange="javascript:address_changed('{$prefix}');{$onchange_js}"
  value='%s'/>
</td>
</tr>
</table>

ADDRESS_HTML;

        $html = sprintf($fmt_str,
            ($obj ? htmlspecialchars($obj->address_1, ENT_QUOTES) : ''),
            ($obj ? htmlspecialchars($obj->address_2, ENT_QUOTES) : ''),
            ($obj ? htmlspecialchars($obj->municipality, ENT_QUOTES) : ''),
            ($obj ? htmlspecialchars($obj->postal_code, ENT_QUOTES) : '')
            );

        return $html;

    }  // END  format_fields

}
