<?php
    require_once 'inc/session.php';
    $sql = include 'inc/sql_connection.php';

    /* Check if user is logged in. If so, silently redirect them
        to the index page. */
    if (isset($_SESSION['USER_ID'])) {
        header('Location: /');
        exit;
    }

    /* Check if request method is GET. */
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        /* Validate all GET input. If this is invalid then the user did not specify a token in the URL. */
        if (!isset($_GET['token'])) {
            $_SESSION['ERROR'] = "Could not process request.<br>Please try again.";
            header('Location: /'); //Redirect to index
            exit;
        }

        /* Save our GET input. (Sanitizing is not needed because we use prepared statements.) */
        $token = $_GET['token'];

        /* Attempt to find the token ID and user this token belongs to. Since we are activating an account,
            we will filter the results to only include REGISTER tokens. */
        $query = $sql->prepare('SELECT id AS token_id, user_id FROM account_tokens WHERE token = ? AND type = ? LIMIT 1');
        $query->execute(array(
            $token,
            'REGISTER'
        ));

        /* If we do not get EXACTLY one row back, then we know the token is invalid. */
        if ($query->rowCount() != 1) {
            $_SESSION['ERROR'] = "Invalid token provided.<br>Please try again.";
            header('Location: /'); //Redirect to index
            exit;
        }

        /* Save the data from our search results. */
        $row = $query->fetch(PDO::FETCH_ASSOC); //Save entire row
        $token_id = $row['token_id'];
        $user_id = $row['user_id'];

        /* Activate the account this token belongs to. */
        $query = $sql->prepare('UPDATE accounts SET activated = ? WHERE id = ?');
        $query->execute(array(
            TRUE,
            $user_id
        ));

        /* Remove our [used] token from the database. */
        $query = $sql->prepare('DELETE FROM account_tokens WHERE id = ?');
        $query->execute(array(
            $token_id
        ));

        /* Notify the user that their action was successful. */
        $_SESSION['NOTICE'] = "Account activated successfully!";
    }

    /* Redirect to index if the request is not GET or once we're
        done activing the users account. */
    header('Location: /');
    exit;
?>