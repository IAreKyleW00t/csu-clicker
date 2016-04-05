<?php
    /* Load SESSION and SQL Connection */
    require_once 'inc/session.php';
    $sql = include 'inc/sql_connection.php';

    /* Check if user is logged in */
    if (!isset($_SESSION['USER_ID']) || $_SESSION['USER_PERMISSION_LEVEL'] < 1) {
        header('Location: /');
        exit;
    }
    
    /* Check if request method is GET */
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        /* Validate POST input */
        if (!isset($_GET['token'])) {
            $_SESSION['ERROR'] = "Invalid GET request.<br>Please try again.";
            header('Location: /student.php'); //Redirect to student panel
            exit;
        }
        
        /* Save the GET input. (Sanitizing is not needed because we use prepared statements.) */
        $token = $_GET['token'];

        /* Select token from account_tokens table */
        $query = $sql->prepare('SELECT id, question_set, current_question FROM sessions WHERE token = ? LIMIT 1');
        $query->execute(array($token));

        /* If we do not get exactly 1 result back then this token is invalid */
        if ($query->rowCount() != 1) {
            $_SESSION['ERROR'] = "Invalid session token.<br>Please try again.";
            header('Location: /student.php'); //Redirect to student panel
            exit;
        }

        /* Save entire row */
        $token = $query->fetch(PDO::FETCH_ASSOC);
    }
?>
<!DOCTYPE html>
<!--[if lt IE 7]><html class="no-js lt-ie9 lt-ie8 lt-ie7" lang="en"><![endif]-->
<!--[if IE 7]><html class="no-js lt-ie9 lt-ie8" lang="en"><![endif]-->
<!--[if IE 8]><html class="no-js lt-ie9" lang="en"><![endif]-->
<!--[if gt IE 8]><!--><html class="no-js" lang="en"><!--<![endif]-->
    <head>
        <?php include 'inc/meta.php'; ?>
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
            <nav class="navbar navbar-primary navbar-fixed-top background-primary text-primary light">
                <div class="container">
                    <div class="navbar-header">
                        <button class="navbar-toggle collapsed" type="button" data-toggle="collapse" data-target=".navbar-collapse" aria-expanded="false">
                            <span class="icon-bar"></span>
                            <span class="icon-bar"></span>
                            <span class="icon-bar"></span>
                        </button>
                        <a class="navbar-brand" href="/#home">
                            <img class="img-responsive" src="img/logo.png" alt="CSU Clicker">
                        </a> <!-- /.navbar-brand -->
                    </div> <!-- /.navbar-header -->

                    <div class="collapse navbar-collapse">
                        <ul class="nav navbar-nav navbar-right">
                            <li><a href="/#about">About</a></li>
                            <li><a href="/#features">Features</a></li>
                            <li><a href="/#reviews">Reviews</a></li>
                            <li><a href="/#contact">Contact</a></li>

                            <li class="dropdown navbar-gap">
                                <a href="javascript:void(0);" class="dropdown-toggle text-normal" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Hi, <?php echo htmlspecialchars($_SESSION['USER_FIRST_NAME']); ?> <span class="caret"></span></a>
                                <ul class="dropdown-menu z-1">
                                    <?php if ($_SESSION['USER_PERMISSION_LEVEL'] == 255) : ?>
                                        <li><a href="/student.php">Student Panel</a></li>
                                        <li><a href="/faculty.php">Faculty Panel</a></li>
                                        <li><a href="/admin.php">Admin Panel</a></li>
                                    <?php elseif ($_SESSION['USER_PERMISSION_LEVEL'] == 2) : ?>
                                        <li><a href="/faculty.php">Faculty Panel</a></li>
                                    <?php else : ?>
                                        <li><a href="/student.php">Student Panel</a></li>
                                    <?php endif; ?>

                                    <li class="divider" role="separator"></li>
                                    <li><a href="/logout.php">Logout</a></li>
                                </ul> <!-- /.dropdown-menu -->
                            </li> <!-- /.dropdown -->
                        </ul> <!-- /.navbar-nav -->
                    </div> <!-- /.navbar-collapse -->
                </div> <!-- /.container -->
            </nav> <!-- /nav -->
        </header> <!-- /header -->

        <main class="container">
            <div class="row clearfix">
                <div class="col-xs-12">
                    <div id="question" class="well">
                        <div class="text-center">
                            <h2>Loading Question Set...<br><small>Please wait</small></h2>
                            <span class="loader">
                                <div class="dot"></div>
                                <div class="dot"></div>
                                <div class="dot"></div>
                                <div class="dot"></div>
                            </span>
                        </div>
                    </div>
                </div>
            </div> <!-- end row -->
        </main> <!-- end container -->

        <?php include 'inc/footer.php'; ?>
        <?php include 'inc/notice.php'; ?>
        <?php include 'inc/error.php'; ?>
        <script>
            $(document).ready(function() {
                var session_id = <?php echo $token['id']; ?>;
                
            });
        </script>
    </body>
</html>