<?php

require_once('init.php');

$edit_errors_group =
        edit_errors_group::read_from_db($_GET['edit_errors_group_id']);

print $edit_errors_group->gen_detailed_display();

