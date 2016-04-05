<?php
    require_once('inc/session.php');
    $sql = include('inc/sql_connection.php');

    /* Check if user is logged in. If not, silently redirect them
        to the index page. */
    if (!isset($_SESSION['USER_ID'])) {
        header('Location: /');
        exit;
    }

    /* Validate all GET input. If this is invalid then the user did not specify a set number in the URL. */
    if (!isset($_GET['id'])) {
        $_SESSION['ERROR'] = "Could not process request.<br>Please try again.";
        header('Location: /');
        exit;
    }

    /* Save our GET input. (Sanitizing is not needed because we use prepared statements.) */
    $id = $_GET['id'];

    /* Check if the current user is the owner of the question set. If they are not, then
        deny them permission from viewing/editing the question set. */
    $query = $sql->prepare('SELECT label FROM question_sets WHERE id = ? AND user_id = ? LIMIT 1');
    $query->execute(array(
        $id,
        $_SESSION['USER_ID']
    ));

    /* If we do not get EXACTLY one row back, then we know the user is not the owner. */
    if ($query->rowCount() != 1) {
        $_SESSION['ERROR'] = "You do not have permission to access that question set.";
        header('Location: /');
        exit;
    }

    /* Save label for us to use later. */
    $label = $query->fetch(PDO::FETCH_ASSOC)['label'];


    /* Attempt to select all questions for the current question set and count
        the total number of questions within the set. Sets without any questions will default to 0.
        If no questions are found, then the total rowCount will be 0. */
    $query = $sql->prepare('SELECT q.id, q.question, (SELECT COUNT(1) FROM answers a WHERE a.question_id = q.id) AS total_answers FROM questions q WHERE q.set_id = ?');
    $query->execute(array(
        $id
    ));
    
    $rows = $query->fetchAll(PDO::FETCH_ASSOC);
    $total_questions = $query->rowCount();
    $count = 1;
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
                            <li class="active"><a href="/panel">Panel</a></li>
                            <li><a href="/logout">Logout</a></li>
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
                                    <b>Label:</b> <?php echo $label; ?><br>
                                    <b>Total Questions:</b> <?php echo $total_questions; ?><br>
                                    <b>Unique ID:</b> <?php echo $id; ?><br><br>
                                    <b>Total Views:</b> N/A<br>
                                    <b>Total Responses:</b> N/A
                                    <div class="progress">
                                        <div class="progress-bar progress-bar-success" style="width:67%"></div>
                                    </div> <!-- /.progress -->
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
                                    <form id="modify-form" class="form-vertical" action="qs_update.php" method="post" validate>
                                        <input id="set_id" class="hidden" type="number" name="set_id" value="<?php echo $id; ?>" readonly required>
                                        <fieldset class="form-group label-floating">
                                            <label for="label" class="control-label">Label</label>
                                            <input id="label" class="form-control" type="text" name="label" value="<?php echo $label; ?>" required>
                                        </fieldset> <!-- /.form-group -->
                                        
                                        <fieldset>
                                            <button id="modify-submit" class="btn btn-raised btn-accent" type="submit" form="modify-form">Save</button>
                                            <button id="modify-delete" class="btn btn-danger" type="submit" form="modify-form">Delete</button>
                                        </fieldset> <!-- /.form-group -->
                                    </form>
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
                                </div>
                                <div class="panel-body">
                                    <?php if ($total_questions == 0) : ?>
                                    <h3 class="text-center">There are no questions within this set.</h3>
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
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($rows as $question) : ?>
                                                <tr id="<?php echo $question['id']; ?>" class="<?php echo ($question['total_answers'] == 0 ? "warning" : ""); ?>">
                                                <?php
                                                    echo "<td>" . $count++ . "</td>";
                                                    echo "<td>" . $question['question'] . "</td>";
                                                    echo "<td class=\"text-center\">" . $question['total_answers'] . "</td>";
                                                ?>
                                                    <td class="text-center"><button id="edit" class="btn btn-sm btn-raised btn-accent">Edit</button></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div> <!-- /.col -->
                </div>
            </div>
            
            <!-- Floating Action Button -->
            <div class="fab">
                <span data-toggle="tooltip" data-placement="left" title="" data-original-title="Add">
                    <a href="javascript:void(0);" class="btn btn-fab btn-accent" data-toggle="modal" data-target="#modal-qs-add" role="button"><i class="material-icons">add</i></a>
                </span>
            </div> <!-- /.fab -->
        
            <!-- Add Question Modal -->
            <div id="modal-qs-add" class="modal fade" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-body">
                            <h2>Add Question</h2>
                            <h3 class="text-light text-center">
                                Type your question in the box below<br>
                                <small>(You can add answers later)</small>
                            </h3>
                            
                            <form id="form-qs-add" class="form-vertical" action="/qs_add.php" method="post" validate>
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
                                            <button id="submit-qs-add" class="btn btn-raised btn-accent" type="submit">Add</button>
                                            <button id="cancel-qs-add" class="btn btn-default" type="reset" data-dismiss="modal">Cancel</button>
                                        </div> <!-- /.text-right -->
                                    </div> <!-- /.col -->
                                </div> <!-- /.row -->
                            </form> <!-- /#form-qs-add -->
                        </div> <!-- /.modal-body -->
                    </div> <!-- /.modal-content -->
                </div> <!-- /.modal-dialog -->
            </div> <!-- /.modal -->
        </main>
        
        <!-- Edit Question Modal -->
        <div id="qs-edit-modal" class="modal fade" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title">Edit Question</h2>
                    </div> <!-- /.modal-header -->
                    
                    <div class="modal-body">
                        <p class="text-medium">WORK IN PROGRESS</p>
                        <ul class="answers"></ul>
                    </div>

                    <div class="modal-footer">
                        <button id="qs-edit-submit" class="btn btn-accent" type="submit" form="qs-edit-form">Save</button>
                        <button id="qs-edit-close" class="btn btn-default" type="reset" form="qs-edit-form" data-dismiss="modal">Close</button>
                    </div> <!-- /.modal-footer -->
                </div> <!-- /.modal-content -->
            </div> <!-- /.modal-dialog -->
        </div> <!-- /.modal -->

        <?php include 'inc/footer.php'; ?>
        <?php include 'inc/notice.php'; ?>
        <?php include 'inc/error.php'; ?>

        <!-- Question Set Modify Validation -->
        <script type="text/javascript">
            $(document).ready(function() {
                var deleteTrigger = false;
                $('#modify-delete').click(function() {
                    deleteTrigger = true;
                    $('#modify-form').attr('action', '/qs_delete.php');
                });

                $('#modify-form').submit(function() {
                    if (!deleteTrigger || confirm('Are you sure you want to delete this question set? This cannot be undone.')) {
                        return true;
                    }
                    return false;
                });
                
                $('button[id^="edit"]').click(function() {
                    $('.answers').empty();
                    var id = $(this).closest('tr').attr('id');
                    $.post('/qs_read.php', {qs_id : id})
                        .done(function(data) {
                            var json = $.parseJSON(data);
                            
                            if (json['valid'] == true) {
                                for (var key in json) {
                                    if (json.hasOwnProperty(key) && key != 'valid') {
                                        $('.answers').append('<li>' + json[key].answer + '</li>');
                                    }
                                }
                            }
                        });
                    
                    $('#qs-edit-modal').modal('show');
                });
            });
        </script>
    </body>
</html>
