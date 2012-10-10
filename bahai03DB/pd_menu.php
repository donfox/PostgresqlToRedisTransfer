<?php

require_once 'pulldown.php';

class pd_menu {

    //-------------------------------------------------------------------
    private static function gen_help_column() {
        $classes = user_root_class::get_class_list();

        $pd_col = new pulldown_column('HELP');

        $pd_col->add_item(
                new pulldown_item('(general)', '?mode=help&datatype=help') );

        foreach ($classes as $class) {
            $help_file = "help_{$class}.html";
            if (!file_exists($help_file))
                continue;

            $long_name = call_user_func(array($class, 'type_long_name'));
            $url = "?mode=help&datatype=$class";
            $pd_col->add_item( new pulldown_item($long_name, $url) );
        }

        return $pd_col;
    }
    
    
    //-------------------------------------------------------------------
    static function gen_menu() {
        $classes = user_root_class::get_class_list();
    
        $pd_menu = new pulldown_menu();
    
        foreach (array('select', 'create') as $verb) {
            $pd_items = array();
            
            foreach ($classes as $class) {
                $fnx = array($class, 'mode_supported');
                if (call_user_func($fnx, $verb)) {
                    $long_name =
                        call_user_func(array($class, 'type_long_name'));
                    $item = new pulldown_item($long_name,
                           sprintf("?mode=%s&datatype=%s", $verb, $class) );
                    array_push($pd_items, $item);
                }
            }
            if (count($pd_items) > 0) {
                $pd_col = new pulldown_column(strtoupper($verb));
                foreach ($pd_items as $item) {
                    $pd_col->add_item($item);
                }
                $pd_menu->add_column($pd_col);
            }
        }
    
        $session_user = $_SESSION['app_session']->get_app_user();

/*
        if ($session_user) {
            $priv = $session_user->get_privilege('atc_members');
            if ($priv > 0) {
                require_once('bahai_community.php');
                $loc = $session_user->get_bahai_community();
                $cluster_code = $loc->get_cluster_code();
                $pd_menu->add_column( new pulldown_column('ATC Members',
                    "?mode=update&datatype=atc_members&key=$cluster_code"));
            }
        }
*/
    
        $pd_menu->add_column(report::gen_pd_column());
    
        if ($session_user) {
            $pd_menu->add_column(
                    new pulldown_column('FEEDBACK', '?datatype=feedback'));
        }

        $pd_menu->add_column( self:: gen_help_column() );

        $pd_menu->add_column(
                new pulldown_column('LOGOFF', 'login.php?logoff=true') );
    
        $pd_menu->gen_html();
    }

}
    
?>
