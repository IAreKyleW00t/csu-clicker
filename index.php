<?php
    require_once 'inc/session.php';

    /* Check if user is logged in. If so, silently redirect them
        to the user panel. Otherwise redirect them to the login page. */
    if (isset($_SESSION['USER_ID'])) {
        header('Location: /panel.php');
    } else {
        header('Location: /login.php');
    }
    exit;
?>