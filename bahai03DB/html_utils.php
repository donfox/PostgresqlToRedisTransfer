<?php
// $Id: html_utils.php,v 1.1 2006/04/20 05:51:12 bmartin Exp $

class html_utils {

    //-------------------------------------------------------------------
    static function assign_to_js_var($var_name, $text) {
        $mod_text = addcslashes($text, '"');
        $mod_text = str_replace("\n", "\\\n", $mod_text);
    
        $js = <<<ASSIGN_JAVASCRIPT
{$var_name} = "\
{$mod_text}";
ASSIGN_JAVASCRIPT;
    
        return $js;
    }

    
    /*
        Expecting an associative array with value stored in key,
        label stored in value (of key-value pair).
    */
    static function format_options($options, $selected_option=null) {
        $html = '';
        foreach($options as $value => $label) {
            $html .= sprintf("<option value='%s' label='%s' %s>%s</option>\n",
                         $value, $label, 
                         ($value == $selected_option) ? 'selected' : '',
                         $label );
        } 
    
        return $html;
    }
    
}

?>
