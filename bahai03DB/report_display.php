<?php

require_once('init.php');

$request = new request($_GET);

$html = call_user_func(array($request->report_type, 'gen_display'), $request);

print($html);
