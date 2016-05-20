<?php
    require_once 'inc/session.php';
    require_once 'inc/recaptcha.php';
    require_once 'inc/sendmail.php';

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
        if (!isset($_POST['name']) || !isset($_POST['email']) || !isset($_POST['subject']) || !isset($_POST['message']) || !isset($_POST['g-recaptcha-response'])) {
            $_SESSION['ERROR'] = "Could not process request.<br>Please try again.";
            header('Location: ' . $referrer); //Redirect to previous page
            exit;
        }

        /* Check if email is valid. This should always be true since it is
            validated in our form on this page. */
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['ERROR'] = "Invalid email address.<br>Please try again.";
            header('Location: ' . $referrer); //Redirect to previous page
            exit;
        }

        /* Validate reCAPTCHA via our `reCAPTCHA()` function. (see: inc/recaptcha.php) */
        if (!reCAPTCHA($_POST['g-recaptcha-response'])) {
            $_SESSION['ERROR'] = "Could not validate reCAPTCHA.<br>Please try again.";
            header('Location: ' . $referrer); //Redirect to previous page
            exit;
        }

        /* Save our remaining POST input. (Sanitizing is not needed at this point because it will be sent as plaintext.) */
        $name = $_POST['name'];
        $subject = $_POST['subject'];
        $message = $_POST['message'];
                 
        /* Email-related variables. */
        $to = "contact@csuoh.io";
        $from = "clicker@csuoh.io";
        $subject = "CSUClicker: $subject";
        $message = "From: $name <$email>\r\n"
                 . "Date: " . date('D, j M Y g:i:sa') . "\r\n"
                 . "IP: " . $_SERVER['REMOTE_ADDR'] . "\r\n\r\n"
                 . "Subject: $subject\r\n"
                 . "Message: $message\r\n\r\n"
                 . "!!DO NOT REPLY TO THIS EMAIL!!";

        /* Send email to recipient via our `sendmail()` function. (see: inc/sendmail.php) */
        sendmail($to, $subject, $message, $from, $name);

        /* Notify the user that their action was successful and redirect back to previous page. */
        $_SESSION['NOTICE'] = "Thanks for contacting us!<br>We\'ll get back to you as soon as we can.";
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
        <title>Contact :: CSU Clicker</title>

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
                <div class="col-xs-12 col-md-8 col-md-offset-2">
                    <div class="panel">
                        <div class="panel-body">
                            <h2>Contact</h2>
                            <h3 class="text-light text-center">Please use the form below to contact us</h3>
                            
                            <form id="form-contact" class="form-vertical" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" validate>
                                <div class="row clearfix">
                                    <div class="col-xs-12">
                                        <fieldset class="form-group">
                                            <div class="input-group">
                                                <span class="input-group-addon"><i class="material-icons">person</i></span>
                                                <input id="name" class="form-control" type="text" name="name" placeholder="Name" required autofocus>
                                            </div> <!-- /.input-group -->
                                        </fieldset> <!-- /.form-group -->
                                    </div> <!-- /.col -->
                                </div> <!-- /.row -->

                                <div class="row clearfix">
                                    <div class="col-xs-12">
                                        <fieldset class="form-group">
                                            <div class="input-group">
                                                <span class="input-group-addon"><i class="material-icons">email</i></span>
                                                <input id="email" class="form-control" type="email" name="email" placeholder="Email" title="CSU email address" required>
                                            </div> <!-- /.input-group -->
                                        </fieldset> <!-- /.form-group -->
                                    </div> <!-- /.col -->
                                </div> <!-- /.row -->

                                <div class="row clearfix">
                                    <div class="col-xs-12">
                                        <fieldset class="form-group">
                                            <div class="input-group">
                                                <span class="input-group-addon"><i class="material-icons">subject</i></span>
                                                <input id="subject" class="form-control" type="text" name="subject" placeholder="Subject" required>
                                            </div> <!-- /.input-group -->
                                        </fieldset> <!-- /.form-group -->
                                    </div> <!-- /.col -->
                                </div> <!-- /.row -->

                                <div class="row clearfix">
                                    <div class="col-xs-12">
                                        <fieldset class="form-group">
                                            <div class="input-group">
                                                <span class="input-group-addon"><i class="material-icons">message</i></span>
                                                <textarea id="message" class="form-control" name="message" rows="4" placeholder="Your message..." required></textarea>
                                            </div> <!-- /.input-group -->
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
                                        <div class="text-right">
                                            <button id="submit-contact" class="btn btn-raised btn-accent" type="submit">Send</button>
                                            <a href="javascript:history.back();" id="cancel-contact" class="btn btn-default">Cancel</a>
                                        </div> <!-- /.text-right -->
                                    </div> <!-- /.col -->
                                </div> <!-- /.row -->
                            </form> <!-- /#form-contact -->
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
                $('#cancel-contact').attr('href', document.referrer);
            });
        </script>
    </body>
</html>