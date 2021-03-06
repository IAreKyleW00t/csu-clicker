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
        if (!isset($_POST['set_id'])) {
            $_SESSION['ERROR'] = "Could not process request.<br>Please try again.";
            header('Location: ' . $referrer); //Redirect to previous page
            exit;
        }
    
        /* Save our POST input. (Sanitizing is not needed because we use prepared statements.) */
        $set_id = $_POST['set_id'];
    
        /* Check if the current user is the owner of the question set. If they are not, then
            deny them permission from deleting the question set. */
        $query = $sql->prepare('SELECT 1 FROM question_sets WHERE id = ? AND user_id = ? LIMIT 1');
        $query->execute(array(
            $set_id,
            $_SESSION['USER_ID']
        ));
        
        /* If we do not get EXACTLY one row back, then we know the user is not the owner. */
        if ($query->rowCount() != 1) {
            $_SESSION['ERROR'] = "You do not have permission to access that question set.";
            header('Location: ' . $referrer); //Redirect to previous page
            exit;
        }
        
        /* Remove the question set from the database after we have verified that the current
            user is the owner of the set. */
        $query = $sql->prepare('DELETE FROM question_sets WHERE id = ?');
        $query->execute(array(
            $set_id
        ));
        
        /* Notify the user that their action was successful and redirect them back to the panel. */
        $_SESSION['NOTICE'] = "Question set deleted successfully!";
        header('Location: /panel.php');
        exit;
    }
        
    /* Redirect to index if the request method is not POST. */
    header('Location: /');
    exit;
?>