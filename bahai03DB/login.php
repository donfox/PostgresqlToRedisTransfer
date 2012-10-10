<?php
// $Id: login.php,v 1.3 2006/04/20 05:57:57 bmartin Exp $

unset($_COOKIE[session_name()]);
require_once('init.php');

require_once('app_session.php');

if (array_key_exists('logoff', $_GET)) {
    //$_SESSION['app_session']->logoff();
}

$error = '';

if (array_key_exists('login', $_POST)) {

    $login = $_POST['login'];
    if (substr($login, 0, 1) == '~') {
        $login = substr($login, 1);
        $trace = true;
    }
    else {
       $trace = false;
    }

    try {
        $_SESSION['app_session'] =
               new app_session($login, $_POST['passwd'], $trace);
    }
    catch (Exception $e) {
        $error = 'Invalid login/password';
    }

    if (!$error) {
        $last_ts = $_SESSION['app_session']->previous_login_ts();
        $message = sprintf("Welcome %s.\n", $login);

        header("Location: main.php?message=$message");
    }
}

?>

<html>
<head>

  <meta content="text/html; charset=ISO-8859-1" http-equiv="content-type">
  <title><?= BAHAI ?> Login</title>
  <link rel="stylesheet" type="text/css" href="bahai.css" />

</head>
<body>
<?= browser::browser_check(); ?>
Bahai Login<br>

<?php if ($error) : ?>
<p style='color:red'> <?= htmlspecialchars($error, ENT_QUOTES) ?> </p>
<?php endif; ?>

<br>
<form method='POST' action='login.php' name='login_form'>

  <label for="login" class="required_field">Login name</label>&nbsp;
  <input maxlength="20" size="12" name="login" type="text"><br>

  <label for="passwd" class="required_field">Password</label>&nbsp;
  <input maxlength="20" size="12" name="passwd" type="password">
  <p>
  <input type="submit" value='submit' name='submit' id='submit_login'>
</form>

<script>

document.forms['login_form'].login.focus();

</script>

</body>
</html>
