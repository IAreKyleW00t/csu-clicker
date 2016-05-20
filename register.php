<?php
    require_once 'inc/session.php';
    require_once 'inc/recaptcha.php';
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
        /* Check if we have a referrer and default to the
            current page if it was not provided. */
        $referrer = $_SERVER['PHP_SELF'];
        if (isset($_POST['referrer'])) {
            $referrer = $_POST['referrer'];
        }
        
        /* Validate all other POST input. These should always be valid since
            they are filled via a form on this page. */
        if (!isset($_POST['first_name']) || !isset($_POST['last_name']) || !isset($_POST['email']) || !isset($_POST['user_id']) || !isset($_POST['password']) || !isset($_POST['password_confirm']) || !isset($_POST['g-recaptcha-response'])) {
            $_SESSION['ERROR'] = "Could not process request.<br>Please try again.";
            header('Location: ' . $referrer); //Redirect to previous page
            exit;
        }

        /* Check to make sure the email address is valid. (Should always be true.) */
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['ERROR'] = "Invalid email address.<br>Please try again.";
            header('Location: ' . $referrer); //Redirect to previous page
            exit;
        }

        /* Check if CSU ID is valid. (Should always be true.) */
        $flags = array(
            'options' => array(
                'min_range' => 0000000,
                'max_range' => 9999999
            )
        );
        $user_id = filter_var($_POST['user_id'], FILTER_SANITIZE_NUMBER_INT);
        if (!filter_var($user_id, FILTER_VALIDATE_INT, $flags)) {
            $_SESSION['ERROR'] = "Invalid CSU ID.<br>Please try again.";
            header('Location: ' . $referrer); //Redirect to previous page
            exit;
        }

        /* Check to make sure the passwords match. */
        if (strcmp($_POST['password'], $_POST['password_confirm']) != 0) {
            $_SESSION['ERROR'] = "Passwords do not match.<br>Please try again.";
            header('Location: ' . $referrer); //Redirect to previous page
            exit;
        }

        /* Validate reCAPTCHA via our `reCAPTCHA()` function. (see: inc/recaptcha.php) */
        if (!reCAPTCHA($_POST['g-recaptcha-response'])) {
            $_SESSION['ERROR'] = "Could not validate reCAPTCHA.<br>Please try again.";
            header('Location: ' . $referrer); //Redirect to previous page
            exit;
        }

        /* Save our POST input. (Sanitizing is not needed because we use prepared statements.) */
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        
        /* Hash the users password. For the best security, we use PASSWORD_DEFAULT
            since it will always used the "best" hashing algorithm at the time it is used.
            For extra security, we rotate the password by 13 characters before hashing. */
        $hash = password_hash(str_rot13($_POST['password']), PASSWORD_DEFAULT);

        /* Check to see if a user with this ID OR email exists already. If either of these
            are true, then we will deny the account creation and notify the user. */
        $query = $sql->prepare('SELECT 1 FROM accounts WHERE id = ? OR email = ? LIMIT 1');
        $query->execute(array(
            $user_id,
            $email
        ));
        
        /* If we do not get EXACTLY zero rows back, then we know a user with that ID or email already exists. */
        if ($query->rowCount() != 0) {
            $_SESSION['ERROR'] = "An account with that ID/email already exists.<br><a href=\"/forgot.php\">Click here to recover your password.</a>";
            header('Location: ' . $referrer); //Redirect to previous page
            exit;
        }

        /* If we reach this point, then we know the information provided is valid, so we can continue with the
            account creation process by adding their information into the database. */
        $query = $sql->prepare('INSERT INTO accounts (id, first_name, last_name, email, hash) VALUES (?, ?, ?, ?, ?)');
        $query->execute(array(
            $user_id,
            $first_name,
            $last_name,
            $email,
            $hash
        ));

        /* Create a unique token for our the user to activate their password with. */
        $token = bin2hex(mcrypt_create_iv(16, MCRYPT_DEV_URANDOM));
        
        /* Create an expiry time for this token. */
        $expires_on = date('Y-m-d H:i:s', strtotime('+30 minutes'));

        /* Insert a new REGISTER token into the database so it can be used later. This token can
            only be used with the account that created/requested it and will automatically expire
            in 30 minutes after being created. If the user needs a new token then they must attempt
            to login and the system will automatically generate a new token for them. */
        $query = $sql->prepare('INSERT INTO account_tokens (user_id, token, type, expires_on) VALUES (?, ?, ?, ?)');
        $query->execute(array(
            $user_id,
            $token,
            'REGISTER',
            $expires_on
        ));

        /* Email-related variables. */
        $from = "no-reply@csuoh.io";
        $subject = 'CSUClicker: Account Activation';
        $message = "Thanks for signing up to use the CSU Clicker app! Click the link below to be finish activating your account.\r\n\r\n"
                 . "Please note this link will expire in 30 minutes! If you need a new activation link, simply login and new one will be sent to you.\r\n\r\n"
                 . "https://clicker.csuoh.io/activate.php?token=" . $token;
        
        /* Send email to recipient via our `sendmail()` function. (see: inc/sendmail.php) */
        sendmail($email, $subject, $message, $from, "no-reply");

        /* Notify the user that their action was successful and redirect them to the login page. */
        $_SESSION['NOTICE'] = "Activation email sent to $email.<br><b>Please be sure to check your Spam/Junk folder!</b>";
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
        <title>Register :: CSU Clicker</title>

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
                            <h2>Register</h2>
                            <h3 class="text-light text-center">Please fill in your information</h3>
                            
                            <form id="form-register" class="form-vertical" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" validate>
                                <div class="row clearfix">
                                    <div class="col-sm-6">
                                        <fieldset class="form-group">
                                            <div class="input-group">
                                                <span class="input-group-addon"><i class="material-icons">person</i></span>
                                                <input id="first_name" class="form-control" type="text" name="first_name" placeholder="First name" required autofocus>
                                            </div> <!-- /.input-group -->
                                        </fieldset> <!-- /.form-group -->
                                    </div> <!-- /.col -->

                                    <div class="col-sm-6">
                                        <fieldset class="form-group">
                                            <input id="last_name" class="form-control" type="text" name="last_name" placeholder="Last name" required>
                                        </fieldset> <!-- /.form-group -->
                                    </div> <!-- /.col -->
                                </div> <!-- /.row -->

                                <div class="row clearfix">
                                    <div class="col-xs-12">
                                        <fieldset class="form-group">
                                            <div class="input-group">
                                                <span class="input-group-addon"><i class="material-icons">account_circle</i></span>
                                                <input id="user_id" class="form-control" type="text" name="user_id" placeholder="CSU ID" title="7-digit CSU ID" pattern="^[0-9]{7}$" required>
                                            </div> <!-- /.input-group -->
                                        </fieldset> <!-- /.form-group -->
                                    </div> <!-- /.col -->
                                </div> <!-- /.row -->

                                <div class="row clearfix">
                                    <div class="col-xs-12">
                                        <fieldset class="form-group">
                                            <div class="input-group">
                                                <span class="input-group-addon"><i class="material-icons">email</i></span>
                                                <input id="email" class="form-control" type="email" name="email" placeholder="Email" title="CSU email address" pattern="^.*(csuohio\.edu)$" required>
                                            </div> <!-- /.input-group -->
                                        </fieldset> <!-- /.form-group -->
                                    </div> <!-- /.col -->
                                </div> <!-- /.row -->

                                <div class="row clearfix">
                                    <div class="col-sm-6">
                                        <fieldset class="form-group">
                                            <div class="input-group">
                                                <span class="input-group-addon"><i class="material-icons">lock</i></span>
                                                <input id="password" class="form-control" type="password" name="password" placeholder="Password" pattern="^.{6,}$" required>
                                            </div> <!-- /.input-group -->
                                        </fieldset> <!-- /.form-group -->
                                    </div> <!-- /.col -->

                                    <div class="col-sm-6">
                                        <fieldset class="form-group">
                                            <input id="password_confirm" class="form-control" type="password" name="password_confirm" placeholder="Confirm password" pattern="^.{6,}$" required>
                                        </fieldset> <!-- /.form-group -->
                                    </div> <!-- /.col -->
                                </div> <!-- /.row -->

                                <div class="row clearfix">
                                    <div class="col-xs-12">
                                        <fieldset class="form-group">
                                            <div class="input-group">
                                                <span class="input-group-addon"><i class="material-icons">verified_user</i></span>
                                                <div class="g-recaptcha" data-sitekey="6LdXPRcTAAAAAKJFAvTEWGB_BMt-1Ln10xFXh4E3"></div>
                                            </div> <!-- /.input-group -->
                                        </fieldset> <!-- /.form-group -->
                                    </div> <!-- /.col -->
                                </div> <!-- /.row -->

                                <div class="row clearfix">
                                    <div class="col-xs-12">
                                        <a href="/login.php" class="btn btn-sm btn-link btn-accent">Already have an account?</a>
                                    </div> <!-- /.col -->
                                </div> <!-- /.row -->

                                <div class="row clearfix">
                                    <div class="col-xs-12">
                                        <div class="text-right">
                                            <button id="submit-register" class="btn btn-raised btn-accent" type="submit">Submit</button>
                                            <a href="javascript:history.back();" id="cancel-register" class="btn btn-default">Cancel</a>
                                        </div> <!-- /.text-right -->
                                    </div> <!-- /.col -->
                                </div> <!-- /.row -->
                            </form> <!-- /#form-register -->
                        </div> <!-- /.panel-body -->
                    </div> <!-- /.panel -->
                </div> <!-- /.col -->
            </section> <!-- /.row -->
        </main> <!-- /.container -->
        
        <?php include 'inc/footer.php'; ?>
        <?php include 'inc/notice.php'; ?>
        <?php include 'inc/error.php'; ?>
        
        <!-- Custom JavaScript -->
        <script>
            /* Set the "Cancel" button to be what the previous page was. */
            $(document).ready(function() {
                $('#cancel-register').attr('href', document.referrer);
            });
        </script>
    </body>
</html>