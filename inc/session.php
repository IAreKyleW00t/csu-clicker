<?php
    /*
        This file will automatically start a PHP session if (and only if) a session
        is not already started (ie: `start_session()` was never called.)

        For improved security, we also harden the session cookie by setting
        an expiration time (30 minutes of inactivity) and enforce HTTPS if the user is using it.
    */
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params(1800, '/', $_SERVER['SERVER_NAME'], (isset($_SERVER['HTTPS']) ? true : false), true); //Set cookie params
        session_start(); //Start session
    }

    /*
        To help prevent session ID hijacking (while not spamming the server),
        we allow a 1/5 chance that the session ID will be regenerated. This is only
        done if this PHP script is called.
    */
    if (mt_rand(0, 4) === 0) session_regenerate_id(true);
?>
