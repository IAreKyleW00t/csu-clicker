<?php
    require_once 'inc/session.php';
    $sql = include 'inc/sql_connection.php';

    /* Check if user is logged in. If not, silently redirect them
        to the index page. */
    if (!isset($_SESSION['USER_ID'])) {
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
        $token = strtolower($_GET['token']);
        
        /* Attempt to select and check if the session token is valid and if so, parse the information
            from it so we can begin our continual reading process. */
        $query = $sql->prepare('SELECT s.id AS sess_id, s.set_id, s.current_question, (SELECT COUNT(1) FROM questions q WHERE q.set_id = s.set_id) AS total_questions FROM sessions s WHERE token = ? LIMIT 1');
        $query->execute(array(
            $token
        ));

        /* If we do not get EXACTLY one row back, then we know the token is invalid. */
        if ($query->rowCount() != 1) {
            $_SESSION['ERROR'] = "Invalid session token.<br>Please try again.";
            header('Location: /'); //Redirect to index
            exit;
        }

        /* Save the data from our search results. */
        $row = $query->fetch(PDO::FETCH_ASSOC); //Save entire row
        $sess_id = $row['sess_id'];
        $set_id = $row['set_id'];
        $current_question = $row['current_question'];
        $total_questions = $row['total_questions'];
        
        /* Check to see if the current user is the owner of the session. If so, we can give them
            extra features on this page to allow them to control the session itself. */
        $query = $sql->prepare('SELECT 1 FROM sessions s JOIN question_sets qs ON (s.set_id = qs.id) JOIN accounts a ON (qs.user_id = a.id) WHERE a.id = ? AND s.id = ? LIMIT 1');
        $query->execute(array(
            $_SESSION['USER_ID'],
            $sess_id
        ));
        
        /* Set the default ownership flag. */
        $owner = false;

        /* If we get a row back, then we know the user is the owner, so
            we will adjust the flag accordingly. */
        if ($query->rowCount() == 1) $owner = true;
        
        /* Check if the session is active. To save space (and time),
            we can check if the current question is -1 which will be our
            way of setting a session as "inactive". HOWEVER, if the owner
            is the one who is making this request, we will allow them through. */
        if ($owner == false && $current_question == null) {
            $_SESSION['ERROR'] = "That session has ended.<br>Please contact your instructor.";
            header('Location: /'); //Redirect to index
            exit;
        }
    }
?>
<!DOCTYPE html>
<!--[if lt IE 7]><html class="no-js lt-ie9 lt-ie8 lt-ie7" lang="en"><![endif]-->
<!--[if IE 7]><html class="no-js lt-ie9 lt-ie8" lang="en"><![endif]-->
<!--[if IE 8]><html class="no-js lt-ie9" lang="en"><![endif]-->
<!--[if gt IE 8]><!--><html class="no-js" lang="en"><!--<![endif]-->
    <head>
        <?php include 'inc/meta.php'; ?>
        <meta name="session" content="<?php echo $sess_id; ?>">
        <title>Session :: CSU Clicker</title>

        <?php include 'inc/header.php'; ?>
        <style>
            body {
                padding-top: 90px;
            }
        </style>
    </head>
    <body>
        <header>
            <nav class="navbar navbar-primary navbar-fixed-top text-primary light">
                <div class="container-fluid">
                    <div class="navbar-header">
                        <button class="navbar-toggle collapsed" type="button" data-toggle="collapse" data-target=".navbar-collapse">
                            <i class="material-icons">menu</i>
                        </button>
                        <a class="navbar-brand" href="/">
                            <img class="img-responsive" src="img/logo.png" alt="CSU Clicker">
                        </a> <!-- /.navbar-brand -->
                    </div> <!-- /.navbar-header -->

                    <div class="collapse navbar-collapse">
                        <ul class="nav navbar-nav navbar-right">
                            <li><a href="/panel.php">Panel</a></li>
                            <li><a href="/logout.php">Logout</a></li>
                        </ul> <!-- /.navbar-nav -->
                    </div> <!-- /.navbar-collapse -->
                </div> <!-- /.container -->
            </nav> <!-- /nav -->
        </header> <!-- /header -->

        <main class="container">
            <?php if ($owner === true) : ?>
            <div class="row clearfix">
                <div class="col-xs-12">
                    <div class="alert alert-success">
                        <h3 class="no-margin">Your session token is: <b style="font-family:monospace;"><?php echo $token; ?></b></h3>
                        <p>Give this token to others so they can join your session! Don't worry, you'll be the only one who can control the session itself.</p>
                    </div> <!-- /.alert -->
                </div> <!-- /.col -->
            </div> <!-- /.row -->
            
            <div class="row clearfix">
                <div class="col-xs-12">
                    <div class="panel">
                        <div id="content" class="panel-body">
                            <div class="text-center">
                                <h2>Loading session...<br><small>Please wait</small></h2>
                                <span class="loader">
                                    <div class="dot"></div>
                                    <div class="dot"></div>
                                    <div class="dot"></div>
                                    <div class="dot"></div>
                                </span> <!-- /.loader -->
                            </div> <!-- /.text-center -->
                        </div> <!-- /.panel-body -->
                    </div> <!-- /.panel -->
                </div> <!-- /.col -->
            </div> <!-- /.row -->
            
            <div id="controls" class="row clearfix">
                <div class="col-xs-6 col-sm-4">
                    <button id="prev" class="btn btn-block btn-raised btn-primary" disabled>Previous</button>
                </div> <!-- /.col -->
                
                <div class="col-xs-6 col-sm-4 col-sm-offset-4">
                    <button id="next" class="btn btn-block btn-raised btn-primary" disabled>Next</button>
                </div> <!-- /.col -->
            </div> <!-- /.row -->
            
            <!-- Floating Action Button -->
            <div class="fab">
                <span data-toggle="tooltip" data-placement="left" title="" data-original-title="Close">
                    <a href="javascript:void(0);" id="delete" class="btn btn-fab btn-accent" role="button"><i class="material-icons">close</i></a>
                </span>
            </div> <!-- /.fab -->
            <?php else : ?>
            <div class="row clearfix">
                <div class="col-xs-12">
                    <div class="panel">
                        <div id="content" class="panel-body">
                            <div class="text-center">
                                <h2>Loading session...<br><small>Please wait</small></h2>
                                <span class="loader">
                                    <div class="dot"></div>
                                    <div class="dot"></div>
                                    <div class="dot"></div>
                                    <div class="dot"></div>
                                </span> <!-- /.loader -->
                            </div> <!-- /.text-center -->
                        </div> <!-- /.panel-body -->
                    </div> <!-- /.panel -->
                </div> <!-- /.col -->
            </div> <!-- /.row -->
            <?php endif; ?>
        </main> <!-- /.container -->

        <?php include 'inc/footer.php'; ?>
        <?php include 'inc/notice.php'; ?>
        <?php include 'inc/error.php'; ?>
        
        <!-- Custom JavaScript -->
        <?php if ($owner == true) : ?>
        <script src="js/sess_owner.js"></script>
        <?php else : ?>
        <script src="js/sess_user.js"></script>
        <?php endif; ?>
    </body>
</html>