<?php

//$connect_str = file_get_contents('pg_connect_file');
$db = pg_connect('dbname=bahai2 user=bmartin');

$query = "SELECT * from event_type order by display_order;";
$res = pg_query($query);

$options_html = '';

$optgroup = '';
while ($row = pg_fetch_assoc($res)) {

    if ($row['optgroup'] != $optgroup) {
        if ($optgroup)
            $options_html .= "</OPTGROUP>\n";

        $optgroup = $row['optgroup'];
        if ($optgroup) {
            $options_html .= "<OPTGROUP label='{$optgroup}'>\n";
        }
    }

    $options_html .= sprintf("<OPTION value='%s'>%s</OPTION>\n",
        $row['event_type_code'], $row['full_label']);

}

if ($optgroup)
    $options_html .= "</OPTGROUP>\n";

?>

<SELECT name='event_type_code' id='event_type_code'>
<?= $options_html ?>
</SELECT>
