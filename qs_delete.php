<?php
    /* Load SESSION and SQL Connection */
    require_once('inc/session.php');
    $sql = include('inc/sql_connection.php');
    
    /* Check if user is logged in and has permission */
    if (!isset($_SESSION['USER_ID']) || $_SESSION['USER_PERMISSION_LEVEL'] < 2) {
        header('Location: /'); //Silently redirect to index
        exit;
    }
    
    /* Check request method is POST */ 
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        /* Validate input */
        if (!isset($_POST['set_id'])) {
            $_SESSION['ERROR'] = "Invalid POST request.<br>Please try again.";
            header('Location: /faculty.php'); //Redirect to faculty page
            exit;
        }
    
        /* Save and sanitize input */
        $set_id = htmlspecialchars($_POST['set_id'], ENT_QUOTES, 'UTF-8');
    
        /* Check if the current user is the owner of this question set */
        $query = $sql->prepare('SELECT 1 FROM question_sets WHERE id = ? AND user_id = ? LIMIT 1');
        $query->execute(array($set_id, $_SESSION['USER_ID']));
        
        /* If we do not get exactly 1 result back then this user is not the owner */
        if ($query->rowCount() != 1) {
            $_SESSION['ERROR'] = "You do not have permission to access this question set.";
            header('Location: /faculty.php'); //Redirect to faculty page
            exit;
        }
        
        /* Delete question set */
        $query = $sql->prepare('DELETE FROM question_sets WHERE id = ?');
        $query->execute(array($set_id));
        
        /* Notify the user that this action was successful */
        $_SESSION['NOTICE'] = "Question set deleted successfully!";
    }
        
    /* Redirect to faculty page */
    header('Location: /panel');
    exit;
?>