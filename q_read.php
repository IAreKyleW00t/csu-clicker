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
    
        /* Save the POST input. (Sanitizing is not needed because we use prepared statements.) */
        $question_id = $_POST['question_id'];
        
        /* Check if the current user is the owner of the question. If they are not, then
            deny them permission from viewing the question. */
        $query = $sql->prepare('SELECT 1 FROM questions q JOIN question_sets qs ON (q.set_id = qs.id) WHERE q.id = ? AND qs.user_id = ? LIMIT 1');
        $query->execute(array(
            $question_id,
            $_SESSION['USER_ID']
        ));
        
        /* If we do not get EXACTLY one row back, then we know the token is invalid. */
        if ($query->rowCount() != 1) {
            echo json_encode($response);
            exit;
        }
        
        /* Select all of the answers relating to the question that was previously selected.
            We could do this in our previous query, but we would not be able to verify if the user
            is the owner AND check if there aren't any answers without knowing which one occurred. To
            get around this we just check them separately. */
        $query = $sql->prepare('SELECT id AS answer_id, answer FROM answers WHERE question_id = ?');
        $query->execute(array(
            $question_id
        ));

        /* Save the data from our search results. */
        $rows = $query->fetchAll(PDO::FETCH_ASSOC); //Save all rows
        $response += $rows;
        
        /* Set our response to be successful. */
        $response['success'] = true;
    }
    
    /* Return our JSON-formatted response to the user. */
    echo json_encode($response);
    exit;
?>