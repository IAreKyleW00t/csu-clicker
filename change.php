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
        /* Check if we have a referrer and default to our
            index page if it was not provided. */
        $referrer = "/";
        if (isset($_POST['referrer'])) {
            $referrer = $_POST['referrer'];
        }
        
        /* Validate all other POST input. These should always be valid. */
        if (!isset($_POST['password_current']) || !isset($_POST['password_new']) || !isset($_POST['password_confirm'])) {
            $_SESSION['ERROR'] = "Could not process request.<br>Please try again.";
            header('Location: ' . $referrer); //Redirect to previous page
            exit;
        }

        /* Check to make sure the new passwords match. */
        if (strcmp($_POST['password_new'], $_POST['password_confirm']) != 0) {
            $_SESSION['ERROR'] = "Passwords do not match.<br>Please try again.";
            header('Location: ' . $referrer); //Redirect to previous page
            exit;
        }

        /* Save our POST input. (Sanitizing is not needed because we use prepared statements.) */
        $current_password = $_POST['password_current'];
        $new_password = $_POST['password_new'];

        /* Hash the users new password. For the best security, we use PASSWORD_DEFAULT
            since it will always used the "best" hashing algorithm at the time it is used.
            For extra security, we rotate the password by 13 characters before hashing. */
        $new_hash = password_hash(str_rot13($new_password), PASSWORD_DEFAULT);

        /* Attempt to select the current user from the database. This should never fail, but it helps
            prevent false USER_ID's from being used. */
        $query = $sql->prepare('SELECT id AS user_id, hash AS current_hash FROM accounts WHERE id = ? LIMIT 1');
        $query->execute(array(
            $_SESSION['USER_ID']
        ));

        /* If we do not get EXACTLY one result back, then we know the user is invalid. If that happens,
            then we will forcefully log the user out. */
        if ($query->rowCount() != 1) {
            header('Location: /logout.php');
            exit;
        }

        /* Save the data from our search results. */
        $row = $query->fetch(PDO::FETCH_ASSOC); //Save entire row
        $user_id = $row['user_id'];
        $current_hash = $row['current_hash'];

        /* Verify that the users current password with the current hash stored on file. */
        if (!password_verify(str_rot13($current_password), $current_hash)) {
            $_SESSION['ERROR'] = "Incorrect password.<br>Please try again.";
            header('Location: ' . $referrer); //Redirect to previous page
            exit;
        }

        /* Update the users hash on file with the new hash. */
        $query = $sql->prepare('UPDATE accounts SET hash = ? WHERE id = ?');
        $query->execute(array(
            $new_hash,
            $user_id
        ));

        /* Notify the user that their action was successful and redirect back to the previous page. */
        $_SESSION['NOTICE'] = "Password updated successfully!";
        header('Location: ' . $referrer);
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
        <title>Change Password :: CSU Clicker</title>

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
                            <h2>Change Password</h2>
                            <h3 class="text-light text-center">Please enter a new password</h3>
                            
                            <form id="form-change" class="form-vertical" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" validate>
                            
                            <input id="referer" class="hidden" type="text" name="referer" value="<?php echo $_SERVER['PHP_SELF']; ?>" readonly required>
                                <div class="row clearfix">
                                    <div class="col-xs-12">
                                        <fieldset class="form-group">
                                            <div class="input-group">
                                                <span class="input-group-addon"><i class="material-icons">verified_user</i></span>
                                                <input id="password_current" class="form-control" type="password" name="password_current" placeholder="Current password" required autofocus>
                                            </div> <!-- /.input-group -->
                                        </fieldset> <!-- /.form-group -->
                                    </div> <!-- /.col -->
                                </div> <!-- /.row -->

                                <div class="row clearfix">
                                    <div class="col-sm-6">
                                        <fieldset class="form-group">
                                            <div class="input-group">
                                                <span class="input-group-addon"><i class="material-icons">lock</i></span>
                                                <input id="password_new" class="form-control" type="password" name="password_new" placeholder="New password" required>
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
                                        <div class="text-right">
                                            <button id="submit-change" class="btn btn-raised btn-accent" type="submit">Submit</button>
                                            <a href="/panel" id="cancel-change" class="btn btn-default">Cancel</a>
                                        </div> <!-- /.text-right -->
                                    </div> <!-- /.col -->
                                </div> <!-- /.row -->
                            </form> <!-- /#form-change -->
                        </div> <!-- /.panel-body -->
                    </div> <!-- /.panel -->
                </div> <!-- /.col -->
            </section> <!-- /.row -->
        </main> <!-- /.container -->
        
        <?php include 'inc/footer.php'; ?>
        <?php include 'inc/notice.php'; ?>
        <?php include 'inc/error.php'; ?>
        <script>
            $(document).ready(function() {
                $('#cancel-change').attr('href', document.referrer);
            });
        </script>
    </body>
</html>