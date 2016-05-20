<?php
    require_once 'inc/session.php';
    $sql = include 'inc/sql_connection.php';

    /* Check if user is logged in. If not, silently redirect them
        to the index page. */
    if (!isset($_SESSION['USER_ID'])) {
        header('Location: /');
        exit;
    }
    
    /* Check if request method is POST. */
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        /* Check if we have a referrer and default to
            the panel if it was not provided. */
        $referrer = "/panel.php";
        if (isset($_POST['referrer'])) {
            $referrer = $_POST['referrer'];
        }
        
        /* Validate other POST input. This should always be valid since
            they are filled via a form on another page. */
        if (!isset($_POST['label'])) {
            $_SESSION['ERROR'] = "Could not process request.<br>Please try again.";
            header('Location: ' . $referrer); //Redirect to previous page
            exit;
        }
    
        /* Save our POST input. (Sanitizing is not needed because we use prepared statements.) */
        $label = $_POST['label'];
        
        /* Create a new question set with the label provided by the user and set the current user
            as the owner of the question set. */
        $query = $sql->prepare('INSERT INTO question_sets (user_id, label) VALUES (?, ?)');
        $query->execute(array(
            $_SESSION['USER_ID'],
            htmlspecialchars($label)
        ));
        
        /* Save the ID of the question set we created. */
        $qs_id = $sql->lastInsertId();

        /* Notify the user that their action was successful and redirect them to the new page to
            view their question set. */
        $_SESSION['NOTICE'] = "Question set created successfully!";
        header('Location: /view.php?id=' . $qs_id);
        exit;
    }

    /* Redirect to index if the request method is not POST. */
    header('Location: /');
    exit;
?>