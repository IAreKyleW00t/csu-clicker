<?php
    require_once 'inc/session.php';
    require_once 'inc/sendmail.php';
    $sql = include 'inc/sql_connection.php';

    /* Check if user is logged in. If so, silently redirect them
        to the index page. */
    if (isset($_SESSION['USER_ID'])) {
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
        
        /* Validate all POST input. These should always be valid. */
        if (!isset($_POST['user_id']) || !isset($_POST['password'])) {
            $_SESSION['ERROR'] = "Could not process request.<br>Please try again.";
            header('Location: ' . $referrer); //Redirect to previous page
            exit;
        }

        /* Save our POST input. (Sanitizing is not needed at this point because we use prepared statements.) */
        $id = $_POST['user_id'];
        $password = $_POST['password'];

        /* Attempt to select the matching user from the database based on the given ID. (Password will be checked later.) */
        $query = $sql->prepare('SELECT id AS user_id, first_name, last_name, email AS user_email, hash AS current_hash, permission_level, activated FROM accounts WHERE id = ? LIMIT 1');
        $query->execute(array(
            $id
        ));

        /* If we do not get EXACTLY one result back, then we know this user is invalid. */
        if ($query->rowCount() != 1) {
            $_SESSION['ERROR'] = "Invalid login credentials.<br>Please try again.";
            header('Location: ' . $referrer); //Redirect to previous page
            exit;
        }
        
        /* Save the data from our search results. */
        $row = $query->fetch(PDO::FETCH_ASSOC);
        $user_id = $row['user_id'];
        $first_name = $row['first_name'];
        $last_name = $row['last_name'];
        $user_email = $row['user_email'];
        $current_hash = $row['current_hash'];
        $permission_level = $row['permission_level'];
        $activated = $row['activated'];

        /* Verify the users password by comparing it to what was is saved on file using PHP's password_verify() function.
            This will automatically determine what hash was used and adjust how the function works accordingly. */
        if (!password_verify(str_rot13($password), $current_hash)) {
            $_SESSION['ERROR'] = "Invalid login credentials.<br>Please try again.";
            header('Location: ' . $referrer); //Redirect to previous page
            exit;
        }

        /* Check if we need to rehash the users password. Because we used PASSWORD_DEFAULT with PHP's password_hash()
            function, the "best" hashing algorithm could change in the future. This will automatically detect if a saved
            hash is using an older algorithm and rehash and save the password. */
        if (password_needs_rehash($current_hash, PASSWORD_DEFAULT)) {
            $hash = password_hash(str_rot13($password), PASSWORD_DEFAULT);
            $query = $sql->prepare('UPDATE accounts SET hash = ? WHERE id = ?');
            $query->execute(array(
                $hash,
                $user_id
            ));
        }

        /* Check if the user account is activated. If not, resend an activation email to the one saved on file for
            that account. TODO: Allow users to manually request a new email without needing to login again. */
        if (!$activated) {
            /* Create a unique token for our the user to activate their password with. */
            $token = bin2hex(mcrypt_create_iv(16, MCRYPT_DEV_URANDOM));

            /* Insert our new REGISTER token into the database so it can be used later. This token can
                only be used with the account that created/requested it. This token will automatically expire
                in 30 minutes after being created. For the user to get a new token they must attempt to login
                again and a new one will be sent automatically. */
            $query = $sql->prepare('INSERT INTO account_tokens (user_id, token, type) VALUES (?, ?, ?)');
            $query->execute(array(
                $user_id,
                $token,
                'REGISTER'
            ));

            /* Email-related variables. */
            $from = "no-reply@csuoh.io";
            $subject = 'CSUClicker: Account Activation';
            
            /* Format the email message so it is easier for the end-user to read. */
            $message = "Thanks for signing up to use the CSU Clicker app! Click the link below to be finish activating your account.\r\n\r\n"
                     . "Please note this link will expire in 30 minutes! If you need a new activation link, simply login and new one will be sent to you.\r\n\r\n"
                     . "https://clicker.csuoh.io/activate.php?token=" . $token;

            /* Send email to recipient via our `sendmail()` function. (see: inc/sendmail.php) */
            sendmail($user_email, $subject, $message, $from, "no-reply");

            /* Notify the user that their action was somewhat successfull (activation required)
                and redirect them back to the previous page. */
            $_SESSION['NOTICE'] = "A new activation email has been sent to $user_email.<br><b>Please be sure to check your Spam/Junk folder!</b>";
            header('Location: ' . $referrer);
        } else {
            /* Save user information into SESSION. */
            $_SESSION['USER_ID'] = $user_id;
            $_SESSION['USER_FIRST_NAME'] = $first_name;
            $_SESSION['USER_LAST_NAME'] = $last_name;
            $_SESSION['USER_EMAIL'] = $user_email;
            $_SESSION['USER_PERMISSION_LEVEL'] = $permission_level;
            $_SESSION['USER_ACTIVATED'] = $activated;

            /* Notify the user that their action was successful and redirect them to the user panel. */
            $_SESSION['NOTICE'] = "Logged in successfully!";
            header('Location: /panel');
        }
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
        <title>Login :: CSU Clicker</title>

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
                            <h2>Login</h2>
                            <h3 class="text-light text-center">Please login to continue</h3>
                            
                            <form id="form-login" class="form-vertical" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" validate>
                                <div class="row clearfix">
                                    <div class="col-xs-12">
                                        <fieldset class="form-group">
                                            <div class="input-group">
                                                <span class="input-group-addon"><i class="material-icons">account_circle</i></span>
                                                <input id="user_id" class="form-control" type="text" name="user_id" title="7-digit CSU ID" pattern="^[0-9]{7}$" placeholder="CSU ID" required autofocus>
                                            </div> <!-- /.input-group -->
                                        </fieldset> <!-- /.form-group -->
                                    </div> <!-- /.col -->
                                </div> <!-- /.row -->

                                <div class="row clearfix">
                                    <div class="col-xs-12">
                                        <fieldset class="form-group">
                                            <div class="input-group">
                                                <span class="input-group-addon"><i class="material-icons">lock</i></span>
                                                <input id="password" class="form-control" type="password" name="password" placeholder="Password" required>
                                            </div> <!-- /.input-group -->
                                        </fieldset> <!-- /.form-group -->
                                    </div> <!-- /.col -->
                                </div> <!-- /.row -->

                                <div class="row clearfix">
                                    <div class="col-xs-12">
                                        <a href="/forgot" class="btn btn-sm btn-link btn-accent">Forgot your password?</a>
                                    </div> <!-- /.col -->
                                </div> <!-- /.row -->

                                <div class="row clearfix">
                                    <div class="col-xs-12">
                                        <div class="text-right">
                                            <button id="submit-login" class="btn btn-raised btn-accent" type="submit">Login</button>
                                            <a href="/register" id="register-login" class="btn btn-default">Register</a>
                                        </div> <!-- /.text-right -->
                                    </div> <!-- /.col -->
                                </div> <!-- /.row -->
                            </form> <!-- /#form-login -->
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