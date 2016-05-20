<?php
    require_once 'inc/session.php';
    $sql = include 'inc/sql_connection.php';

    /* Check if user is logged in. If not, silently redirect them
        to the index page. */
    if (!isset($_SESSION['USER_ID'])) {
        header('Location: /');
        exit;
    }

    /* Validate all GET input. If this is invalid then the user did not specify a question set ID in the URL. */
    if (!isset($_GET['id'])) {
        $_SESSION['ERROR'] = "Could not process request.<br>Please try again.";
        header('Location: /');
        exit;
    }

    /* Save our GET input. (Sanitizing is not needed because we use prepared statements.) */
    $set_id = $_GET['id'];

    /* Check if the current user is the owner of the question set. If they are not, then
        deny them permission from viewing the question set. */
    $query = $sql->prepare('SELECT label FROM question_sets WHERE id = ? AND user_id = ? LIMIT 1');
    $query->execute(array(
        $set_id,
        $_SESSION['USER_ID']
    ));

    /* If we do not get EXACTLY one row back, then we know the user is not the owner. */
    if ($query->rowCount() != 1) {
        $_SESSION['ERROR'] = "You do not have permission to access that question set.";
        header('Location: /');
        exit;
    }

    /* Save the data from our search results. */
    $row = $query->fetch(PDO::FETCH_ASSOC); //Save entire row
    $label = $row['label'];


    /* Attempt to select all questions for the current question set and count
        the total number of answers within each question. Questions without any answers will default to 0.
        If no questions are found, then the total rowCount will be 0. */
    $query = $sql->prepare('SELECT q.id, q.question, (SELECT COUNT(1) FROM answers a WHERE a.question_id = q.id) AS total_answers FROM questions q WHERE q.set_id = ?');
    $query->execute(array(
        $set_id
    ));
    
    /* Save the data from our search results. */
    $rows = $query->fetchAll(PDO::FETCH_ASSOC); //Save all rows
    $total_questions = $query->rowCount();
    $count = 1; //Relative question number
?>
<!DOCTYPE html>
<!--[if lt IE 7]><html class="no-js lt-ie9 lt-ie8 lt-ie7" lang="en"><![endif]-->
<!--[if IE 7]><html class="no-js lt-ie9 lt-ie8" lang="en"><![endif]-->
<!--[if IE 8]><html class="no-js lt-ie9" lang="en"><![endif]-->
<!--[if gt IE 8]><!--><html class="no-js" lang="en"><!--<![endif]-->
    <head>
        <?php include 'inc/meta.php'; ?>
        <title>Question Set :: CSU Clicker</title>

        <?php include 'inc/header.php'; ?>
        <style>
            body {
                padding-top: 60px;
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
                            <li class="active"><a href="/panel.php">Panel</a></li>
                            <li><a href="/logout.php">Logout</a></li>
                        </ul> <!-- /.navbar-nav -->
                    </div> <!-- /.navbar-collapse -->
                </div> <!-- /.container -->
            </nav> <!-- /nav -->
        </header> <!-- /header -->

        <main class="container">
            <h1>Question Set</h1>
            <div class="row clearfix">
                <div class="col-md-4">
                    <div class="row clearfix">
                        <div class="col-xs-12">
                            <div class="panel panel-primary">
                                <div class="panel-heading">
                                    <h3 class="panel-header text-primary light">Information</h3>
                                </div> <!-- /.panel-heading -->
                                
                                <div class="panel-body">
                                    <b>Label:</b> <?php echo htmlspecialchars($label); ?><br>
                                    <b>Total Questions:</b> <?php echo $total_questions; ?><br>
                                    <b>Unique ID:</b> <?php echo $set_id; ?>
                                </div> <!-- /.panel-body -->
                            </div> <!-- /.panel -->
                        </div> <!-- /.col -->
                    </div> <!-- /.row -->
                    
                    <div class="row clearfix">
                        <div class="col-xs-12">
                            <div class="panel panel-primary">
                                <div class="panel-heading">
                                    <h3 class="panel-header text-primary light">Edit</h3>
                                </div> <!-- /.panel-heading -->
                                
                                <div class="panel-body">
                                    <form id="form-qs-update" class="form-vertical" action="/qs_update.php" method="post" validate>
                                        <input id="referrer" class="hidden" type="text" name="referrer" value="<?php echo $_SERVER['REQUEST_URI']; ?>" readonly required>
                                        <input id="set_id" class="hidden" type="number" name="set_id" value="<?php echo $set_id; ?>" readonly required>
                                        <fieldset class="form-group label-floating">
                                            <label for="label" class="control-label">Label</label>
                                            <input id="label" class="form-control" type="text" name="label" value="<?php echo htmlspecialchars($label); ?>" required>
                                        </fieldset> <!-- /.form-group -->
                                        
                                        <fieldset>
                                            <button id="update-qs" class="btn btn-raised btn-accent" type="submit">Save</button>
                                            <button id="delete-qs" class="btn btn-danger" type="submit">Delete</button>
                                        </fieldset> <!-- /.form-group -->
                                    </form> <!-- /#form-qs-update -->
                                </div> <!-- /.panel-body -->
                            </div> <!-- /.panel -->
                        </div> <!-- /.col -->
                    </div> <!-- /.row -->
                </div> <!-- /.col -->
                
                <div class="col-md-8">
                    <div class="row clearfix">
                        <div class="col-xs-12">
                            <div class="panel panel-primary">
                                <div class="panel-heading">
                                    <h3 class="panel-header text-primary light">Questions</h3>
                                </div><!-- /.panel-heading -->
                                
                                <div class="panel-body">
                                    <?php if ($total_questions == 0) : ?>
                                    <h3 class="text-center no-margin">
                                        There are no questions within this set.<br>
                                        <small>Click the <i class="material-icons">add</i> icon to add one</small>
                                    </h3>
                                    <?php else : ?>
                                    <table id="questions" class="table table-striped table-hover">
                                        <thead>
                                            <col width="32" />
                                            <col width="999" />
                                            <tr>
                                                <th>#</th>
                                                <th>Question</th>
                                                <th class="text-center">Answers</th>
                                                <th class="text-center"></th>
                                                <th class="text-center"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($rows as $q) : ?>
                                                <tr id="<?php echo $q['id']; ?>" class="<?php echo ($q['total_answers'] == 0 ? "warning" : ""); ?>">
                                                <?php
                                                    echo "<td>" . $count++ . "</td>";
                                                    echo "<td id=\"question\">" . htmlspecialchars($q['question']) . "</td>";
                                                    echo "<td class=\"text-center\">" . $q['total_answers'] . "</td>";
                                                ?>
                                                    <td class="text-center"><button id="edit" class="btn btn-sm btn-raised btn-accent">Edit</button></td>
                                                    <td class="text-center"><button id="delete" class="btn btn-icon btn-danger"><i class="material-icons">delete</i></button></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table> <!-- /#questions -->
                                    <?php endif; ?>
                                </div> <!-- /.panel-body -->
                            </div> <!-- /.panel -->
                        </div> <!-- /.col -->
                    </div> <!-- /.row -->
                </div> <!-- /.col -->
            </div> <!-- /.row -->
            
            <!-- Floating Action Button -->
            <div class="fab">
                <span data-toggle="tooltip" data-placement="left" title="" data-original-title="Add">
                    <a href="javascript:void(0);" class="btn btn-fab btn-accent" data-toggle="modal" data-target="#modal-q-add" role="button"><i class="material-icons">add</i></a>
                </span>
            </div> <!-- /.fab -->
        
            <!-- Add Question Modal -->
            <div id="modal-q-add" class="modal fade" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-body">
                            <h2>Add Question</h2>
                            <h3 class="text-light text-center">
                                Type your question in the box below<br>
                                <small>(You can add answers later)</small>
                            </h3>
                            
                            <form id="form-q-add" class="form-vertical" action="/q_add.php" method="post" validate>
                                <input id="referrer" class="hidden" type="text" name="referrer" value="<?php echo $_SERVER['REQUEST_URI']; ?>" readonly required>
                                <input id="set_id" class="hidden" type="text" name="set_id" value="<?php echo $set_id; ?>" readonly required>
                                <div class="row clearfix">
                                    <div class="col-xs-12">
                                        <fieldset class="form-group">
                                            <div class="input-group">
                                                <span class="input-group-addon"><i class="material-icons">label</i></span>
                                                <textarea id="question" class="form-control" type="text" name="question" placeholder="Question" required></textarea>
                                            </div> <!-- /.input-group -->
                                        </fieldset> <!-- /.form-group -->
                                    </div> <!-- /.col -->
                                </div> <!-- /.row -->

                                <div class="row clearfix">
                                    <div class="col-xs-12">
                                        <div class="text-right">
                                            <button id="submit-q-add" class="btn btn-raised btn-accent" type="submit">Add</button>
                                            <button id="cancel-q-add" class="btn btn-default" type="reset" data-dismiss="modal">Cancel</button>
                                        </div> <!-- /.text-right -->
                                    </div> <!-- /.col -->
                                </div> <!-- /.row -->
                            </form> <!-- /#form-qs-add -->
                        </div> <!-- /.modal-body -->
                    </div> <!-- /.modal-content -->
                </div> <!-- /.modal-dialog -->
            </div> <!-- /.modal -->
        
            <!-- Edit Question Modal -->
            <div id="modal-q-update" class="modal fade" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-body">
                            <h2>Edit Question</h2>
                            <h3 class="text-light text-center">Manage your question and answers below</h3>
                            
                            <form id="form-q-update" class="form-vertical" action="/q_update.php" method="post" validate>
                                <input id="referrer" class="hidden" type="text" name="referrer" value="<?php echo $_SERVER['REQUEST_URI']; ?>" readonly required>
                                <input id="question_id" class="hidden" type="text" name="question_id" readonly required>
                                <div class="row clearfix">
                                    <div class="col-xs-12">
                                        <fieldset class="form-group">
                                            <div class="input-group">
                                                <span class="input-group-addon"><i class="material-icons">label</i></span>
                                                <input id="question" class="form-control" type="text" name="question" required>
                                            </div> <!-- /.input-group -->
                                        </fieldset> <!-- /.form-group -->
                                    </div> <!-- /.col -->
                                </div> <!-- /.row -->
                                
                                <div id="answers" class="row clearfix">
                                </div> <!-- /.row -->
                                
                                <div class="row clearfix">
                                    <div class="col-xs-12">
                                        <fieldset class="form-group">
                                            <div class="input-group">
                                                <span class="input-group-addon"><a href="javascript:void(0);" id="answer-add"><i class="material-icons">add_circle</i></a></span>
                                                <input id="answer-new" class="form-control" type="text" placeholder="New answer">
                                            </div> <!-- /.input-group -->
                                        </fieldset> <!-- /.form-group -->
                                    </div> <!-- /.col -->
                                </div> <!-- /.row -->

                                <div class="row clearfix">
                                    <div class="col-xs-12">
                                        <div class="text-right">
                                            <button id="submit-q-update" class="btn btn-raised btn-accent" type="submit">Update</button>
                                            <button id="cancel-q-update" class="btn btn-default" type="reset" data-dismiss="modal">Cancel</button>
                                        </div> <!-- /.text-right -->
                                    </div> <!-- /.col -->
                                </div> <!-- /.row -->
                            </form> <!-- /#form-qs-add -->
                        </div> <!-- /.modal-body -->
                    </div> <!-- /.modal-content -->
                </div> <!-- /.modal-dialog -->
            </div> <!-- /.modal -->
        </main> <!-- /.container -->

        <?php include 'inc/footer.php'; ?>
        <?php include 'inc/notice.php'; ?>
        <?php include 'inc/error.php'; ?>

        <!-- Custom JavaScript -->
        <script src="js/view.js"></script>
    </body>
</html>
