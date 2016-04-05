<?php
    require_once('inc/session.php');
    $sql = include('inc/sql_connection.php');

    /* Check if user is logged in. If not, silently redirect them
        to the index page. */
    if (!isset($_SESSION['USER_ID'])) {
        header('Location: /');
        exit;
    }
    
    /* Check if request method is POST */
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        /* Check if we have a referer and default to our
            panel if it was not provided. */
        $referer = "/panel";
        if (isset($_POST['referer'])) {
            $referer = $_POST['referer'];
        }
        
        //TODO: Implementing joining/checking sessions
        header('Location: ' . $referrer); //Redirect to previous page
    }

    /* Redirect to index if the request method is not POST. */
    header('Location: /');
    exit;
?>