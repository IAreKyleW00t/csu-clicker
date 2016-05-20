<?php
    require_once('inc/session.php');
    $sql = include('inc/sql_connection.php');

    /* Check if user is logged in. If not, silently redirect them
        to the index page. */
    if (!isset($_SESSION['USER_ID'])) {
        header('Location: /');
        exit;
    }
    
    /* Create a new array and set the value of "success"
        as false by default. */
    $response = array(
        'success' => false
    );
    
    /* Set our Content-Type to be in JSON format. */
    header('Content-Type: application/json');

    /* Check if request method is POST. */
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        /* Validate other POST input. This should always be valid since
            they are filled via a form on another page. */
        if (!isset($_POST['sess_id']) || !isset($_POST['current_question'])) {
            echo json_encode($response);
            exit;
        }
    
        /* Save our POST input. (Sanitizing is not needed because we use prepared statements.) */
        $sess_id = $_POST['sess_id'];
        $current_question = $_POST['current_question'];
    
        /* Check if the current user is the owner of the session. If they are not, then
            deny them permission from modifying the session. */
        $query = $sql->prepare('SELECT 1 FROM sessions s JOIN question_sets qs ON (s.set_id = qs.id) WHERE s.id = ? AND qs.user_id = ? LIMIT 1');
        $query->execute(array(
            $sess_id,
            $_SESSION['USER_ID']
        ));
        
        /* If we do not get EXACTLY one row back, then we know the user is not the owner. */
        if ($query->rowCount() != 1) {
            echo json_encode($response);
            exit;
        }
        
        /* Disable the session in the database by setting the 'current_question' to NULL. We don't
            actually delete the row because we will need it for statistical purposes later on. */
        $query = $sql->prepare('UPDATE sessions SET current_question = ? WHERE id = ?');
        $query->execute(array(
            $current_question,
            $sess_id
        ));
        
        /* Set our response to be successful. */
        $response['success'] = true;
    }
    
    /* Return our JSON-formatted response to the user. */
    echo json_encode($response);
    exit;
?>