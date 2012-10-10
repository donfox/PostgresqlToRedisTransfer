<?php

class website extends auto_construct implements type_in_db {
    public $website_status;
    public $website_url;
    public $website_url_orig;
    public $webmaster;
    public $webmaster_name;
    public $hosting_company;

    public $host_company_addr;
    public $host_company_addr_id;
    public $host_company_addr_status;


    //------------------------------------------------------------------
    function __construct(array $array_data, $prefix=null) {
        $this->_copy_properties($array_data, $prefix);

        if (!array_key_exists('datatype', $array_data)) {  // from database
            $this->website_url_orig = $this->website_url;
        }
    }


    //------------------------------------------------------------------
    static function read_from_db($url) {
        $query = sprintf("SELECT * from website WHERE website_url = '%s';",
                     pg_escape_string($url));
        $res = app_session::pg_query($query);
    
        if (pg_num_rows($res) == 1) {
            $row = pg_fetch_array($res);
            $obj = new website($row);
            if ($obj->host_company_addr) {
                $obj->hosting_company =
                    address::read_from_db($obj->host_company_addr);
            }
            return $obj;
        }

        else {
            return NULL;
        }
    }


    //------------------------------------------------------------------
    function insert_to_db($process_results_id=null) {
        $query = sprintf("SELECT insert_website('%s',%s,'%s',%s);",
            pg_escape_string($this->website_url),
            (is_null($this->webmaster) ? $this->webmaster : 'NULL'),
            pg_escape_string($this->website_url),
            (is_null($this->host_company_addr_id) ?
             $this->host_company_addr_id : 'NULL')
            );

        $res = app_session::pg_query($query);

        return $this->website_url;
    }


    //------------------------------------------------------------------
    function update_in_db($process_results_id=null) {
        $query = sprintf("SELECT update_website('%s',%s,'%s',%s);",
            pg_escape_string($this->website_url),
            (is_null($this->webmaster) ? $this->webmaster : 'NULL'),
            pg_escape_string($this->website_url),
            (is_null($this->host_company_addr_id) ?
             $this->host_company_addr_id : 'NULL')
            );

        $res = app_session::pg_query($query);

        return $this->website_url;
    }


    //------------------------------------------------------------------
    static function delete_from_db($key) {
        $query = sprintf("SELECT delete_website('%s');",
            pg_escape_string($this->website_url));
    }


    //------------------------------------------------------------------
    static function format_url_field($name, $value) {

        $url_prefix = htmlspecialchars('http://', ENT_QUOTES);
        $url_change_js = 'javascript:' .
            'if (this.value.substr(0,7).toLowerCase() == "http://") {' .
            'this.value = this.value.substr(7); }';

        $mod_value = htmlspecialchars($value, ENT_QUOTES);

        $html = <<<URL_FIELD_HTML

<font size='+1'>{$url_prefix}</font>
<input name='{$name}' value='{$mod_value}' size='90'
 onchange='{$url_change_js}' />

URL_FIELD_HTML;

        return $html;
    }


    //------------------------------------------------------------------
    static function format_fields($prefix, $obj) {

        $change_status = $obj ? 'update' : 'insert';

        $url_prefix = htmlspecialchars('http://', ENT_QUOTES);

        $url_change_js = 'javascript:' .
            'if (this.value.substr(0,7).toLowerCase() == "http://") {' .
            'this.value = this.value.substr(7); }';

        $address_html = address::format_fields($prefix,
            ($obj ? $obj->host_company_addr : null) );

        $webmaster_html = 'WEBMASTER SELECTOR GOES HERE';

        $website_fmt_str = <<<WEBSITE_HTML

<SCRIPT>
function {$prefix}website_changed(fld) {
    status_fld = fld.form.{$prefix}website_status;

    if (status_fld.value)
        return;

    status_fld.value = '$change_status';
}
</SCRIPT>

<table>
<tr>
  <td>
    <label for='{$prefix}website_url'>URL</label>
    <br>
    <font size='+1'>{$url_prefix}</font>
    <input size='90' type='text' name='{$prefix}website_url' value='%s'
     onchange='{$url_change_js}' />

    <input type='hidden' name='{$prefix}website_url_orig' value='%s'
     name='{$prefix}website_url_orig' id='{$prefix}website_url_orig' />
  </td>
</tr>
</table>
WEBSITE_HTML;

/*
<tr>
  <td>
    <label for='{$prefix}webmaster'>Webmaster</label>
    <br>
    {$webmaster_html}
  </td>
</tr>
<tr>
  <td>
    <label for='{$prefix}hosting_company'>Hosting Company</label>
    <br>
    <input type='text' name='{$prefix}hosting_company' value='%s'
     onchange="{$prefix}website_changed(this);" />
  </td>
</tr>
<tr>
  <td colspan='2'>
    {$address_html}
  </td>
</tr>
</table>

WEBSITE_HTML;
*/

        return sprintf($website_fmt_str, 
            ($obj ? htmlspecialchars($obj->website_url_orig, ENT_QUOTES) : ''),
            ($obj ? htmlspecialchars($obj->website_url, ENT_QUOTES) : '')
            );

//          ($obj ? htmlspecialchars($obj->hosting_company, ENT_QUOTES) : '')

    }

}

?>
