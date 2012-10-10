<?php
/*------------------------------------------------------------
 *  $Id
 *
 *  This is the main top level program for the Bahai application
 *  (other top levels are login.php and modules invoked using ajax).
 *------------------------------------------------------------*/

require_once('init.php');  // encapsulates common session initialization
tracer::trace_start();
$sess = $_SESSION['app_session'];

//---------------------------------------------------------------------
//  If there is form data to process (usually from an entry form), then
//  the data must be processed first (usually involves database transaction).
//---------------------------------------------------------------------
$request = ($_SERVER['REQUEST_METHOD'] == 'POST') ?
        call_user_func(array($_POST['datatype'], 'process_post_data')) :
        new request($_GET);


//---------------------------------------------------------------------
//  Bahai Community is central to the application because it
//  constrains the scope of menus, etc.
//---------------------------------------------------------------------
if ($request->datatype == 'bahai_community' and $request->key) {
    $bahai_cmty = bahai_community::read_from_db($request->key);
    $sess->set_bahai_community($bahai_cmty);
}


$css_files = array('bahai.css', 'pd_menu.css');
$js_files = array();
$tabs_groups = array();

if (user_root_class::is_root_class($request->datatype)) {
    $js_files = call_user_func(array($request->datatype, 'required_js_files'));

    $tabs_groups = call_user_func(array($request->datatype, 'tabs_groups'));
    if (count($tabs_groups) > 0) {
        array_push($css_files, 'tabs_group.css');
        array_push($js_files, 'tabs_group.js');
    }
}

$title = BAHAI . " Management";

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<HTML xmlns="http://www.w3.org/1999/xhtml">
<HEAD profile="http://gmpg.org/xfn/1">

<STYLE type="text/css" media="screen">
  /*<![CDATA[*/
<?php
    foreach ($css_files as $fname) {
        printf("@import url( 'css/%s' );\n", $fname);
    }
?>
  /*]]>*/
</STYLE>

<?php
foreach ($js_files as $js_file) {
    printf("<SCRIPT src='%s' type='text/javascript'></SCRIPT>\n", $js_file);
}

if (count($tabs_groups) > 0) {
    print("<SCRIPT type='text/javascript'>\n");
    foreach ($tabs_groups as $tab_group) {
        printf("var %s;\n", $tab_group);
    }
    print("</SCRIPT>\n");
}

?>
<TITLE><?= $title ?></TITLE>

<SCRIPT type='text/javascript'>
  function go_to(datatype, key) {
      window.location = 'main.php?mode=update&datatype='+datatype+'&key='+key;
  }

  function disable_form(form_name) {

      var elems = document.getElementById(form_name).elements;
          for (var i=0; i<elems.length; ++i) {
              elems[i].disabled = true;
          }
  }

</SCRIPT>

</HEAD>

<BODY>
<TABLE width='100%'>

<!---------------------------->
<!--  BANNER at top of page -->
<!---------------------------->
<tr><td class='banner' colspan='2'>
&nbsp; <?= $title ?>
<?php 
$loc = $sess->get_bahai_community();
$user = $sess->get_app_user();

printf(" : %s &nbsp;&nbsp (%s)\n",
       $loc ? htmlspecialchars($loc->get_bahai_cmty_name(), ENT_QUOTES) :
           'no community selected',
       $user ? htmlspecialchars($user->get_login(), ENT_QUOTES) :
           '<font +3>&infin;</font>');
?>
</td></tr>


<!-------------------------------------->
<!-- MENU                             -->
<!-------------------------------------->
<tr><td colspan='2'>
<?php pd_menu::gen_menu(); ?>
</td></tr>


<!-------------------------------->
<!-- Data entry form            -->
<!-------------------------------->
<tr><td><div class='form_body'>
<?php

if ($request->mode == 'help') {
    include( 'help_' . $request->datatype . '.html' );
}

else {
    switch ($request->datatype) {
        case 'feedback':
            $html = feedback::gen_display();
            break;

        case 'report':
            $html = call_user_func(
                array($request->report_type, 'gen_display'), $request);
            break;

        default:
            $html = $request->gen_display();
    }

    print($html);
}


?>
</div></td></tr>
</TABLE>

<?php tracer::trace_end(); ?>

</BODY>
</HTML>
