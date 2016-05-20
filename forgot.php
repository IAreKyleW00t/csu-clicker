<?php
    require_once 'inc/session.php';
    require_once 'inc/sendmail.php';
    $sql = include 'inc/sql_connection.php';

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
        if (!isset($_POST['email']) || !isset($_POST['user_id'])) {
            $_SESSION['ERROR'] = "Could not process request.<br>Please try again.";
            header('Location: ' . $referrer); //Redirect to previous page
            exit;
        }

        /* Save our POST input. (Sanitizing is not needed at this point because we use prepared statements.) */
        $email = $_POST['email'];
        $id = $_POST['user_id'];

        /* Attempt to select the current user from the database. This will automatically validate the
            users input by checking if that ID-email pair exists. */
        $query = $sql->prepare('SELECT 1 FROM accounts WHERE id = ? AND email = ? LIMIT 1');
        $query->execute(array(
            $id,
            $email
        ));
        
        /* If we do not get EXACTLY one result back, then we know the ID-email pair is invalid. */
        if ($query->rowCount() != 1) {
            $_SESSION['ERROR'] = "Invalid account information.<br>Please try again.";
            header('Location: ' . $referrer); //Redirect to previous page
            exit;
        }

        /* Create a unique token for our the user to recover their password with. */
        $token = bin2hex(mcrypt_create_iv(16, MCRYPT_DEV_URANDOM));
        
        /* Create an expiry time for this token. */
        $expires_on = date('Y-m-d H:i:s', strtotime('+30 minutes'));

        /* Insert a new RECOVERY token into the database so it can be used later. This token can
            only be used with the account that created/requested it and will automatically expire
            in 30 minutes after being created. If the user needs a new token then they must go through
            this process again. */
        $query = $sql->prepare('INSERT INTO account_tokens (user_id, token, type, expires_on) VALUES (?, ?, ?, ?)');
        $query->execute(array(
            $id,
            $token,
            'RECOVERY',
            $expires_on
        ));

        /* Email-related variables. */
        $from = "no-reply@csuoh.io";
        $subject = 'CSUClicker: Account Recovery';
        $message = "Looks like you forgot your password... No worries! Click the link below to be taken to a page where you can recover it. If you did not request to change your password, ignore this email and move on with life.\r\n\r\n"
                 . "Please note this link will expire in 30 minutes!\r\n\r\n"
                 . "https://clicker.csuoh.io/recover.php?token=" . $token;

        /* Send email to recipient via our `sendmail()` function. (see: inc/sendmail.php) */
        sendmail($email, $subject, $message, $from, "no-reply");

        /* Notify the user that their action was successful and redirect them to the login page. */
        $_SESSION['NOTICE'] = "Recovery email sent to $email.<br><b>Please be sure to check your Spam/Junk folder!</b>";
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
        <title>Forgot Password :: CSU Clicker</title>

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
                            <h2>Forgot</h2>
                            <h3 class="text-light text-center">Please enter your CSU ID and email</h3>
                            
                            <form id="form-forgot" class="form-vertical" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" validate>
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
                                                <span class="input-group-addon"><i class="material-icons">email</i></span>
                                                <input id="email" class="form-control" type="email" name="email" placeholder="Email" title="CSU email address" pattern="^.*(csuohio\.edu)$" required>
                                            </div> <!-- /.input-group -->
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
                                            <button id="submit-forgot" class="btn btn-raised btn-accent" type="submit">Submit</button>
                                            <a href="javascript:history.back();" id="cancel-forgot" class="btn btn-default">Cancel</a>
                                        </div> <!-- /.text-right -->
                                    </div> <!-- /.col -->
                                </div> <!-- /.row -->
                            </form> <!-- /#form-forgot -->
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
                $('#cancel-forgot').attr('href', document.referrer);
            });
        </script>
    </body>
</html>