<?php
require_once('init.php');

if ($_SERVER['REQUEST_METHOD'] == 'GET') {   // Display selector
?>
    
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<HTML xmlns="http://www.w3.org/1999/xhtml">
<HEAD profile="http://gmpg.org/xfn/1">
  <SCRIPT src='ajax.js' type='text/javascript'> </SCRIPT>
  <SCRIPT src='db_checker.js' type='text/javascript'></SCRIPT>
  <SCRIPT src='person.js' type='text/javascript'></SCRIPT>
  <SCRIPT src='address.js' type='text/javascript'></SCRIPT>
  <SCRIPT src='tabs_group.js' type='text/javascript'></SCRIPT>
  <SCRIPT src='person_popup.js' type='text/javascript'></SCRIPT>
  <STYLE type="text/css" media="screen">
    /*<![CDATA[*/
      @import url( 'css/tabs_group.css' );
    /*]]>*/
  </STYLE>
</HEAD>

<BODY>
<?php
    $ps = person_popup::new_from_request($_GET);
    print( $ps->format_html_body() );
?>
</BODY>
</HTML>

<?php
}


else {     //  Process CREATE data

    $per = new person($_POST);
    $id = $per->insert_to_db();
    $loc = $_SESSION['app_session']->get_bahai_community();

/*
    if ($_POST['person_category'] == 1) {
        $address_id = null;
        member::insert_member_stub($id, $loc->get_key(),
                $_POST['last_name'], $_POST['first_name'],
                $_POST['bahai_id_country'], $_POST['bahai_id'] );
    }
*/

    $label = sprintf("%s,%s (%s:%s)", 
            $_POST['last_name'], $_POST['first_name'],
            $loc->get_key(),
            person::$categories[ $_POST['person_category'] ] 
            );

    print("$id:$label");
}
