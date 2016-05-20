<?php
    require_once 'inc/session.php';
    $sql = include 'inc/sql_connection.php';
    
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
        if (!isset($_POST['sess_id'])) {
            echo json_encode($response);
            exit;
        }
    
        /* Save our POST input. (Sanitizing is not needed at this point because we use prepared statements.) */
        $sess_id = $_POST['sess_id'];
        
        /* Select the question set ID, question ID, and the question itself from the database based on the session
            ID that was provided. This should return more than one result, but it will be filtered out later on
            based on the current_question we have for our session. */
        $query = $sql->prepare('SELECT s.id AS session_id, s.current_question, qs.id AS set_id, q.id AS question_id, q.question FROM sessions s JOIN question_sets qs ON (s.set_id = qs.id) JOIN questions q ON (qs.id = q.set_id) WHERE s.id = ?');
        $query->execute(array(
            $sess_id
        ));

        /* If we do not get ANY results back, then we know this session ID is invalid. */
        if ($query->rowCount() == 0) {
            echo json_encode($response);
            exit;
        }
        
        /* Save the data from our search results. */
        $rows = $query->fetchAll(PDO::FETCH_ASSOC); //Save all rows
        
        /* We will use 'current_question' as our index
            to determine which row we need to get data from. */
        $current_question = $rows[0]['current_question'];
        
        if ($current_question == -1){
            /* Set our response to be successful. */
            $response['success'] = true;
            
            /* Send back basic information for us to detect that the session has
                ended on another page. */
            $response['session_id'] = $sess_id;
            $response['current_question'] = -1;
            
            /* Notify the user that this session has ended once they get redirected. */
            $_SESSION['NOTICE'] = "The session has ended.<br>Thank you for using CSU Clicker!";
            
            
            /* Return our response array in JSON format. */
            echo json_encode($response);
            exit;
        }
        
        $set_id = $rows[$current_question - 1]['set_id'];
        $question_id = $rows[$current_question - 1]['question_id'];
        $question =  $rows[$current_question - 1]['question'];
        $total_questions = $query->rowCount();
        
        /* Select all of the answers for the current question we are on. */
        $query = $sql->prepare('SELECT a.id, a.answer, (SELECT COUNT(1) FROM responses r WHERE r.ans_id = a.id AND r.sess_id = ?) AS responses FROM answers a WHERE a.question_id = ?');
        $query->execute(array(
            $sess_id,
            $question_id
        ));
        
        /* Save the data from our search results. */
        $rows = $query->fetchAll(PDO::FETCH_ASSOC); //Save all rows
        $response['session_id'] = intval($sess_id);
        $response['current_question'] = intval($current_question);
        $response['total_questions'] = intval($total_questions);
        $response['set_id'] = intval($set_id);
        $response['question_id'] = intval($question_id);
        $response['question'] = $question;
        $response['answers'] = $rows;
        $response['total_responses'] = 0;
        
        /* Count the total number of responses we got for this question. */
        foreach ($rows as $a) {
            $response['total_responses'] += $a['responses'];
        }
        
        /* Set our response to be successful. */
        $response['success'] = true;
    }
    
    /* Return our response array in JSON format. */
    echo json_encode($response);
    exit;
?>