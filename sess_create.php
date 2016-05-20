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
        if (!isset($_POST['set_id'])) {
            echo json_encode($response);
            exit;
        }
    
        /* Save our POST input. (Sanitizing is not needed because we use prepared statements.) */
        $set_id = $_POST['set_id'];
    
        /* Check if the current user is the owner of the question set and see if there is an active
            session already open for that set. If they are not the owner, then deny them permission
            from creating a session. If a session is active, we will get the ID of it, if not, then
            it will be NULL. */
        $query = $sql->prepare('SELECT 1, s.id AS sess_id, s.token AS sess_token FROM question_sets qs LEFT JOIN sessions s ON (qs.id = s.set_id AND s.current_question > 0) WHERE qs.id = ? AND qs.user_id = ? LIMIT 1');
        $query->execute(array(
            $set_id,
            $_SESSION['USER_ID']
        ));
        
        /* If we do not get EXACTLY one row back, then we know the user is not the owner. */
        if ($query->rowCount() != 1) {
            echo json_encode($response);
            exit;
        }
        
        /* Save the data from our search results. */
        $row = $query->fetch(PDO::FETCH_ASSOC); //Save entire row
        $sess_id = $row['sess_id'];
        $sess_token = $row['sess_token'];
        
        /* Check if an active session already exists. If so, use the information stored
            in the database instead of recreating it. */
        if ($sess_id != null) {
            /* Save the data into our response. */
            $response['created'] = false;
            $response['id'] = $sess_id;
            $response['token'] = $sess_token;
        } else {
            /* Create a unique token for our the session. */
            $sess_token = bin2hex(mcrypt_create_iv(4, MCRYPT_DEV_URANDOM));
            
            /* Create a new session for the question set the user has selected. */
            $query = $sql->prepare('INSERT INTO sessions (token, set_id) VALUES (?, ?)');
            $query->execute(array(
                $sess_token,
                $set_id
            ));
            
            /* Save the ID of the question set we created. */
            $sess_id = $sql->lastInsertId();
            
            /* Save the data into our response. */
            $response['created'] = true;
            $response['id'] = intval($sess_id);
            $response['token'] = $sess_token;
        }
        
        /* Set our response to be successful. */
        $response['success'] = true;
    }
    
    /* Return our JSON-formatted response to the user. */
    echo json_encode($response);
    exit;
?>