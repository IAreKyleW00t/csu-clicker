<?php
    require_once('inc/config.php');

    /*
        This file will automatically create a new PDO object that is connected to
        a given SQL server. This PDO object is improved by changing key attributes to
        enable things like *REAL* prepared statements in MySQL and automatically closing
        the connection. For the sake of simplicity, SQL information is defined in
        our configuration file.
    */
    $sql = new PDO(SQL_TYPE . ':host=' . SQL_HOST . ';dbname=' . SQL_DB . ';charset=utf8', SQL_USER, SQL_PASSWD);
    $sql->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); //Don't halt entire page on error
    $sql->setAttribute(PDO::ATTR_EMULATE_PREPARES, false); //Use REAL prepared statements
    $sql->setAttribute(PDO::ATTR_PERSISTENT, false); //Don't use persistent connections (security)
    return $sql;
?>
