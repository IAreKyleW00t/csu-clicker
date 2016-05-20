<?php
    require_once 'inc/session.php';
    $sql = include 'inc/sql_connection.php';

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
        /* Validate all POST input. This should always be valid since
            they are filled via a form on another page. */
        if (!isset($_POST['question_id'])) {
            echo json_encode($response);
            exit;
        }
    
        /* Save our POST input. (Sanitizing is not needed at this point because we use prepared statements.) */
        $question_id = $_POST['question_id'];
        
        /* Check if the current user is the owner of the question. If they are not, then
            deny them permission from deleting the question. */
        $query = $sql->prepare('SELECT 1 FROM questions q JOIN question_sets qs ON (q.set_id = qs.id) WHERE q.id = ? AND qs.user_id = ? LIMIT 1');
        $query->execute(array(
            $question_id,
            $_SESSION['USER_ID']
        ));
        
        /* If we do not get EXACTLY one row back, then we know the user is not the owner. */
        if ($query->rowCount() != 1) {
            echo json_encode($response);
            exit;
        }
        
        /* Remove the question from the database after we have verified that the current
            user is the owner of the question. */
        $query = $sql->prepare('DELETE FROM questions WHERE id = ?');
        $query->execute(array(
            $question_id
        ));
        
        /* Set our response to be successful. */
        $response['success'] = true;
    }
    
    /* Return our JSON-formatted response to the user. */
    echo json_encode($response);
    exit;
?>