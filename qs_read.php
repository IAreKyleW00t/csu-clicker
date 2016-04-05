<?php
    /* Load SESSION and SQL Connection */
    require_once('inc/session.php');
    $sql = include('inc/sql_connection.php');
    
    /* Check if user is logged in and has permission */
    if (!isset($_SESSION['USER_ID']) || $_SESSION['USER_PERMISSION_LEVEL'] < 2) {
        header('Location: /'); //Silently redirect to index
        exit;
    }
    
    $response = array();
    $response['valid'] = false;
    
    /* Check request method is POST */ 
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        /* Validate input */
        if (!isset($_POST['qs_id'])) {
            echo json_encode($response);
            exit;
        }
    
        /* Save the POST input. (Sanitizing is not needed because we use prepared statements.) */
        $qs = $_POST['qs_id'];
        
        /* Create a new question set with the given label */
        $query = $sql->prepare('SELECT id AS answer_id, answer FROM answers WHERE question_id = ?');
        $query->execute(array($qs));

        /* Save the data from our search results. */
        $rows = $query->fetchAll(PDO::FETCH_ASSOC);
        $response += $rows;
        $response['valid'] = true;
    }
    
    echo json_encode($response);
    exit;
?>