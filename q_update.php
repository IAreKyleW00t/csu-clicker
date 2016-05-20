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
        if (!isset($_POST['question_id']) || !isset($_POST['question'])) {
            $_SESSION['ERROR'] = "Could not process request.<br>Please try again.";
            header('Location: ' . $referrer); //Redirect to previous page
            exit;
        }

        /* Save our POST input. (Sanitizing is not needed because we use prepared statements.) */
        $question_id = $_POST['question_id'];
        $question = $_POST['question'];
        
        /* Check if the current user is the owner of the question. If they are not, then
            deny them permission from editing the question. */
        $query = $sql->prepare('SELECT 1 FROM questions q JOIN question_sets qs ON (q.set_id = qs.id) WHERE q.id = ? AND qs.user_id = ? LIMIT 1');
        $query->execute(array(
            $question_id,
            $_SESSION['USER_ID']
        ));
        
        /* If we do not get EXACTLY one row back, then we know the user is not the owner. */
        if ($query->rowCount() != 1) {
            $_SESSION['ERROR'] = "You do not have permission to access that question.";
            header('Location: ' . $referrer); //Redirect to previous page
            exit;
        }
        
        /* Update the question itself in the database (even if it hasn't changed.) */
        $query = $sql->prepare('UPDATE questions SET question = ? WHERE id = ?');
        $query->execute(array(
            htmlspecialchars($question),
            $question_id
        ));
        
        /* Check if there were any answers provided in the POST request. If none were provided,
            then we can assume the user wants to remove all answers for that question. Otherwise,
            we will update each answers that was provided to us by updating any answers,
            adding new ones, and removing ones that no longer exist. */
        if (!isset($_POST['answer_id']) && !isset($_POST['answer'])) { //No answers provided
            $query = $sql->prepare('DELETE FROM answers WHERE question_id = ?');
            $query->execute(array(
                $question_id
            ));
        } else { //Got answers
            /* Save our POST input. (Sanitizing is not needed because we use prepared statements.) */
            $answers = $_POST['answer'];
            $answer_ids = $_POST['answer_id'];
            
            /* Create our SQL query placeholders for our prepared statement later. */
            $placeholder = implode(',', array_fill(0, count($answer_ids), '?')); //Create ?,?,...,?
            $params = $answer_ids; //Copy of all the answer ID's to a new array
            array_push($params, $question_id); //Add our final parameter (the question ID) to the end
            
            /* Delete every answer that was NOT included in the list of answers we got from our POST request for
                the specified question we selected previously. */
            $query = $sql->prepare('DELETE FROM answers WHERE id NOT IN (' . $placeholder . ') AND question_id = ?');
            $query->execute(
                $params
            );
            
            /* Loop through all of the answers we got from our POST request and update any answers
                that still exist and add any new answers. */
            foreach ($answer_ids as $index => $id) {
                if ($id == -1) { //Add answer
                    $query = $sql->prepare('INSERT INTO answers (question_id, answer) VALUES (?, ?)');
                    $query->execute(array(
                        $question_id,
                        htmlspecialchars($answers[$index])
                    ));
                } else { //Update answer
                    $query = $sql->prepare('UPDATE answers SET answer = ? WHERE id = ?');
                    $query->execute(array(
                        htmlspecialchars($answers[$index]),
                        $id
                    ));
                }
            }
        }
        
        /* Notify the user that their action was successful and redirect them back to the previous page. */
        $_SESSION['NOTICE'] = "Question updated successfully!";
        header('Location: ' . $referrer); //Redirect to previous page
        exit;
    }

    /* Redirect to index if the request method is not POST. */
    header('Location: /');
    exit;
?>