<?php
    require_once('inc/session.php');
    $sql = include('inc/sql_connection.php');

    /* Check if user is logged in. If not, silently redirect them
        to the index page. */
    if (!isset($_SESSION['USER_ID'])) {
        header('Location: /');
        exit;
    }
    
    /* Check if request method is POST. */
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        /* Check if we have a referer and default to our
            panel if it was not provided. */
        $referer = "/panel";
        if (isset($_POST['referer'])) {
            $referer = $_POST['referer'];
        }
        
        /* Validate all POST input. These should always be valid. */
        if (!isset($_POST['label'])) {
            $_SESSION['ERROR'] = "Could not process request.<br>Please try again.";
            header('Location: ' . $referrer); //Redirect to previous page
            exit;
        }
    
        /* Save our POST input. (Sanitizing is not needed at this point because we use prepared statements.) */
        $label = $_POST['label'];
        
        /* Create a new question set with the label provided by the user. The ID for the question set
            will be generated automatically and parsed laster. */
        $query = $sql->prepare('INSERT INTO question_sets (user_id, label) VALUES (?, ?)');
        $query->execute(array(
            $_SESSION['USER_ID'],
            $label
        ));
        
        /* Select the latest row that was inserted into the database. This is used to get the
            ID of the row, which is the ID of the question set. */
        $query = $sql->prepare('SELECT LAST_INSERT_ID()');
        $query->execute();
        
        /* Save the ID of the question set we created. */
        $qs_id = $sql->lastInsertId();

        /* Notify the user that their action was successful and redirect them to the view page for their
            newly created question set. */
        $_SESSION['NOTICE'] = "Question set created successfully!";
        header('Location: /view?id=' . $qs_id);
        exit;
    }

    /* Redirect to index if the request method is not POST. */
    header('Location: /');
    exit;
?>