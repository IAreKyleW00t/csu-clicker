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

        /* Attempt to select the token provided in our GET request from our database. If this fails, then the
            token does not exist or is not the correct type. */
        $query = $sql->prepare('SELECT id AS token_id, user_id, expires_on FROM account_tokens WHERE token = ? AND type = ? LIMIT 1');
        $query->execute(array(
            $token,
            'RECOVERY'
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
        $expires_on = strtotime($row['expires_on']);
        
        /* Check if the token has expired. If so, delete it from the database and
            tell the user to generate a new one. */
        if ($expires_on - time() <= 0) {
            $query = $sql->prepare('DELETE FROM account_tokens WHERE id = ?');
            $query->execute(array(
                $token_id
            ));
            
            $_SESSION['ERROR'] = "Token has expired.<br>Please generate a new one .";
            header('Location: /'); //Redirect to index
            exit;
        }

        /* Save token information into SESSION */
        $_SESSION['RECOVERY_TOKEN_ID'] = $token_id;

    /* Check if request method is POST. */
    } else if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        /* Check if we have a referrer and default to the
            current page if it was not provided. */
        $referrer = $_SERVER['PHP_SELF'];
        if (isset($_POST['referrer'])) {
            $referrer = $_POST['referrer'];
        }
        
        /* Validate all other POST input. These should always be valid since
            they are filled via a form on this page. */
        if (!isset($_POST['password']) || !isset($_POST['password_confirm']) || !isset($_SESSION['RECOVERY_TOKEN_ID'])) {
            $_SESSION['ERROR'] = "Could not process request.<br>Please try again.";
            header('Location: ' . $referrer); //Redirect to previous page
            exit;
        }

        /* Check to make sure the new passwords match. */
        if (strcmp($_POST['password'], $_POST['password_confirm']) != 0) {
            $_SESSION['ERROR'] = "Passwords do not match.<br>Please try again.";
            header('Location: ' . $referrer); //Redirect to previous page
            exit;
        }

        /* Save our POST input. (Sanitizing is not needed because we use prepared statements.) */
        $new_hash = password_hash(str_rot13($_POST['password']), PASSWORD_DEFAULT); //Hash new password now
        
        /* Update the users password based on who the token is linked to. Regardless of who makes
            this request, all tokens are linked to a single user, so it will always update only
            that single user. */
        $query = $sql->prepare('UPDATE accounts a JOIN account_tokens t ON (a.id = t.user_id) SET a.hash = ? WHERE t.id = ?');
        $query->execute(array(
            $new_hash,
            $_SESSION['RECOVERY_TOKEN_ID']
        ));

        /* Remove our [used] token from the database. */
        $query = $sql->prepare('DELETE FROM account_tokens WHERE id = ?');
        $query->execute(array(
            $_SESSION['RECOVERY_TOKEN_ID']
        ));

        /* Clean up token-related SESSION data. */
        unset($_SESSION['RECOVERY_TOKEN_ID']);

        /* Notify the user that their action was successful and redirect them to the login page. */
        $_SESSION['NOTICE'] = "Password updated successfully!";
        header('Location: /login.php');
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
                                <input id="referrer" class="hidden" type="text" name="referrer" value="<?php echo $_SERVER['REQUEST_URI']; ?>" readonly required>
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
                                        <a href="/contact.php" class="btn btn-sm btn-link btn-accent">Still need help?</a>
                                    </div> <!-- /.col -->
                                </div> <!-- /.row -->

                                <div class="row clearfix">
                                    <div class="col-xs-12">
                                        <div class="text-right">
                                            <button id="submit-recover" class="btn btn-raised btn-accent" type="submit">Submit</button>
                                            <a href="/login.php" id="cancel-recover" class="btn btn-default">Cancel</a>
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