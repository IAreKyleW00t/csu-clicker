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
        /* Validate all POST input. These should always be valid. */
        if (!isset($_POST['sess_id']) || !isset($_POST['ans_id'])) {
            echo json_encode($response);
            exit;
        }
    
        /* Save our POST input. (Sanitizing is not needed because we use prepared statements.) */
        $sess_id = $_POST['sess_id'];
        $ans_id = $_POST['ans_id'];
    
        /* Check if the answer we are submitting is actually part of the session as a whole.
            We do this by checking if the answer is in the question set the session belongs to.*/
        $query = $sql->prepare('SELECT 1 FROM answers a JOIN questions q ON (a.question_id = q.id) JOIN question_sets qs ON (q.set_id = qs.id) JOIN sessions s ON (qs.id = s.set_id) WHERE a.id = ? AND s.id = ? LIMIT 1');
        $query->execute(array(
            $ans_id,
            $sess_id
        ));
        
        /* If we do not get EXACTLY one row back, then we know the answer is not part of this session. */
        if ($query->rowCount() != 1) {
            echo json_encode($response);
            exit;
        }
        
        $query = $sql->prepare('INSERT INTO responses (user_id, sess_id, ans_id) VALUES (?, ?, ?)');
        $query->execute(array(
            $_SESSION['USER_ID'],
            $sess_id,
            $ans_id
        ));
        
        /* Set our response to be successful. */
        $response['success'] = true;
    }
    
    /* Return our JSON-formatted response to the user. */
    echo json_encode($response);
    exit;
?>