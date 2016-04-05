<?php
    require_once('inc/session.php');
    $sql = include('inc/sql_connection.php');

    /* Check if user is logged in. If so, silently redirect them
        to the index page. */
    if (isset($_SESSION['USER_ID'])) {
        header('Location: /');
        exit;
    }

    /* Check if request method is GET */
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        /* Validate all GET input. (Check if a token was provided.) */
        if (!isset($_GET['token'])) {
            header('Location: /'); //Silently redirect to index page
            exit;
        }

        /* Save our GET input. (Sanitizing is not needed at this point because we use prepared statements.) */
        $token = $_GET['token'];

        /* Attempt to select the token provided from our database. If this fails, then the token does not exist.*/
        $query = $sql->prepare('SELECT id AS token_id, user_id FROM account_tokens WHERE token = ? AND type = ? LIMIT 1');
        $query->execute(array(
            $token,
            'RECOVERY'
        ));

        /* If we do not get EXACTLY one result back, then we know this token is invalid. */
        if ($query->rowCount() != 1) {
            $_SESSION['ERROR'] = "Invalid recovery token.<br>Please try again.";
            header('Location: /'); //Redirect to index
            exit;
        }

        /* Save the data from our search results. */
        $row = $query->fetch(PDO::FETCH_ASSOC);
        $user_id = $row['user_id'];

        /* Save token information into SESSION */
        $_SESSION['USER_TOKEN_ID'] = $row['token_id'];

    /* Check if request method is POST */
    } else if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        /* Check if we have a referer and default to our
            index page if it was not provided. */
        $referer = "/";
        if (isset($_POST['referer'])) {
            $referer = $_POST['referer'];
        }
        
        /* Validate all POST input. These should always be valid. */
        if (!isset($_POST['password']) || !isset($_POST['password_confirm']) || !isset($_SESSION['USER_TOKEN_ID'])) {
            $_SESSION['ERROR'] = "Could not process request.<br>Please try again.";
            header('Location: ' . $referer); //Redirect to previous page
            exit;
        }

        /* Check if both passwords match. */
        if (strcmp($_POST['password'], $_POST['password_confirm']) != 0) {
            $_SESSION['ERROR'] = "Passwords do not match.<br>Please try again.";
            header('Location: ' . $referer); //Redirect to previous page
            exit;
        }

        /* Save our POST input. (Sanitizing is not needed at this point because we use prepared statements.)
            To make things safer, we hash our POST input now instead of later. */
        $new_hash = password_hash(str_rot13($_POST['password']), PASSWORD_DEFAULT);

        /* Validate our token again to prevent fake ones from being used. (SQL injections) */
        $query = $sql->prepare('SELECT id AS token_id, user_id FROM account_tokens WHERE id = ? AND type = ? LIMIT 1');
        $query->execute(array(
            $_SESSION['USER_TOKEN_ID'],
            'RECOVERY'
        ));

        /* If we do not get EXACTLY one result back, then we know this token is invalid. */
        if ($query->rowCount() != 1) {
            $_SESSION['ERROR'] = "Invalid recovery token.<br>Please try again.";
            header('Location: ' . $referer); //Redirect to previous page
            exit;
        }
        
        /* Save the data from our search results. */
        $row = $query->fetch(PDO::FETCH_ASSOC);

        /* Save the users new password into the database after we've validated ALL input.
            This will update the user the token belongs to since they are linked together. */
        $query = $sql->prepare('UPDATE accounts SET hash = ? WHERE id = ?');
        $query->execute(array(
            $new_hash,
            $row['user_id']
        ));

        /* Remove our [used] token from the database. */
        $query = $sql->prepare('DELETE FROM account_tokens WHERE id = ?');
        $query->execute(array(
            $row['token_id']
        ));

        /* Clean up our SESSION by removing token-related data. */
        unset($_SESSION['USER_TOKEN_ID']);

        /* Notify the user that their action was successful and redirect them to the login page. */
        $_SESSION['NOTICE'] = "Password updated successfully!";
        header('Location: /login');
        exit;
    }
?>
<!DOCTYPE html>
<!--[if lt IE 7]><html class="no-js lt-ie9 lt-ie8 lt-ie7" lang="en"><![endif]-->
<!--[if IE 7]><html class="no-js lt-ie9 lt-ie8" lang="en"><![endif]-->
<!--[if IE 8]><html class="no-js lt-ie9" lang="en"><![endif]-->
<!--[if gt IE 8]><!--><html class="no-js" lang="en"><!--<![endif]-->
    <head>
        <?php include 'inc/meta.php'; ?>
        <title>Recover :: CSU Clicker</title>

        <?php include 'inc/header.php'; ?>
        
        <!-- Custom CSS -->
        <style>
            @media (min-width: 768px) {
                body {
                    padding-top: 70px;
                }
            }
        </style>
    </head>
    <body>
        <main class="container">
            <section class="row clearfix">
                <div class="col-xs-12">
                    <a href="/"><img class="img-responsive center-block" src="img/logo_dark.png"></a>
                </div> <!-- /.col -->
            </section> <!-- /.row -->
            
            <section class="row clearfix">
                <div class="col-xs-12 col-md-8 col-lg-6 col-md-offset-2 col-lg-offset-3">
                    <div class="panel">
                        <div class="panel-body">
                            <h2>Recover</h2>
                            <h3 class="text-light text-center">Please enter a new password</h3>
                            
                            <form id="form-recover" class="form-vertical" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" validate>
                                <div class="row clearfix">
                                    <div class="col-xs-12">
                                        <fieldset class="form-group disabled">
                                            <div class="input-group">
                                                <span class="input-group-addon"><i class="material-icons">account_circle</i></span>
                                                <input id="user_id" class="form-control" type="text" name="user_id" value="<?php echo htmlspecialchars(substr_replace($user_id, '***', 0, 3)); ?>" disabled>
                                            </div> <!-- /.input-group -->
                                        </fieldset> <!-- /.form-group -->
                                    </div> <!-- /.col -->
                                </div> <!-- /.row -->

                                <div class="row clearfix">
                                    <div class="col-sm-6">
                                        <fieldset class="form-group">
                                            <div class="input-group">
                                                <span class="input-group-addon"><i class="material-icons">lock</i></span>
                                                <input id="password" class="form-control" type="password" name="password" placeholder="New password" required autofocus>
                                            </div> <!-- /.input-group -->
                                        </fieldset> <!-- /.form-group -->
                                    </div> <!-- /.col -->

                                    <div class="col-sm-6">
                                        <fieldset class="form-group">
                                            <input id="password_confirm" class="form-control" type="password" name="password_confirm" placeholder="Confirm password" required>
                                        </fieldset> <!-- /.form-group -->
                                    </div> <!-- /.col -->
                                </div> <!-- /.row -->

                                <div class="row clearfix">
                                    <div class="col-xs-12">
                                        <a href="/contact" class="btn btn-sm btn-link btn-accent">Still need help?</a>
                                    </div> <!-- /.col -->
                                </div> <!-- /.row -->

                                <div class="row clearfix">
                                    <div class="col-xs-12">
                                        <div class="text-right">
                                            <button id="submit-recover" class="btn btn-raised btn-accent" type="submit">Submit</button>
                                            <a href="/login" id="cancel-recover" class="btn btn-default">Cancel</a>
                                        </div> <!-- /.text-right -->
                                    </div> <!-- /.col -->
                                </div> <!-- /.row -->
                            </form> <!-- /#form-recover -->
                        </div> <!-- /.panel-body -->
                    </div> <!-- /.panel -->
                </div> <!-- /.col -->
            </section> <!-- /.row -->
        </main> <!-- /.container -->
        
        <?php include 'inc/footer.php'; ?>
        <?php include 'inc/notice.php'; ?>
        <?php include 'inc/error.php'; ?>
    </body>
</html>