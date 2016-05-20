/* Check if the user is updating or deleting their
 * question set. If they press the "Delete" button, then
 * set the deletion flag to true, otherwise set it to false. */
var deleteTrigger = false;
$('#delete-qs').click(function() {
    deleteTrigger = true;
    $('#form-qs-update').attr('action', '/qs_delete.php');
});
$('#update-qs').click(function() {
    deleteTrigger = false;
    $('#form-qs-update').attr('action', '/qs_update.php');
});

/* Have the user validate their deletion request if the delete
 * flag is true. Otherwise we will update the question set normally. */
$('#form-qs-update').submit(function() {
    if (!deleteTrigger || confirm('Are you sure you want to delete this question set? This cannot be undone.')) {
        return true;
    }
    return false;
});

/* Fetch the data for the question we are editing
 * and load it into the modal. */
$('#questions tbody').on('click', '#edit', function() {
    var row = $(this).closest('tr');
    var id = row.attr('id');
    var question = $('#question', row).text();
    var form = $('#form-q-update');
    
    $('#question_id', form).attr('value', id);
    $('#question', form).attr('value', question);
    
    $('#answers', form).empty();
    $.post('/q_read.php', {question_id : id})
        .done(function(json) {            
            if (json['success'] == true) {
                for (var key in json) {
                    if (json.hasOwnProperty(key) && key != 'success') {
                        addAnswer(json[key].answer, json[key].answer_id);
                    }
                }
            }
        })
        .fail(function(data) {
            $.snackbar({content: '<b>ERROR:</b> Could not process request.<br>Please try again.', timeout: 5000, htmlAllowed: true});
        });
    
    $('#modal-q-update').modal('show');
});

/* Have the user confirm their delete action for every question they
    want to delete. */
$('#questions tbody').on('click', '#delete', function() {
    if (!confirm('Are you sure you want to delete this question? This cannot be undone.')) return;
    
    var $tr = $(this).closest('tr');
    var id = $tr.attr('id');
    
    $.post('/q_delete.php', {question_id : id})
        .done(function(json) {
            if (json['success'] == true) {
                $tr.remove();
                $.snackbar({content: "Question deleted successfuly!", timeout: 5000, htmlAllowed: true});
            } else {
                $.snackbar({content: '<b>ERROR:</b> Could not process request.<br>Please try again.', timeout: 5000, htmlAllowed: true});
            }
        })
        .fail(function(data) {
            $.snackbar({content: '<b>ERROR:</b> Could not process request.<br>Please try again.', timeout: 5000, htmlAllowed: true});
        });
});

/* Add an answer to the page. See addAnswer() */
$('#form-q-update').on('click', '#answer-add', function() {
    var answer = $('#answer-new').val();
    
    /* Ignore empty or null answers. */
    if (answer == '' || answer == null) return;
    else addAnswer(answer, -1);
    
    /* Clear the old answer field after it has been added. */
    $('#answer-new').val('');
});

/* Delete an answer from our list of answers for the question
    that is shown. This will be fully removed later. */
$('#form-q-update #answers').on('click', '#answer-del', function() {
    var $answer = $(this).closest('.col-xs-12');
    $answer.remove();
});

/* Append our new answer to the list of answers for that
    question. These will be save in the database later. */
function addAnswer(answer, id) {
    var $form = $('#form-q-update');
    $('#answers', $form).append('\
        <div class="col-xs-12">\
            <input class="hidden" type="text" name="answer_id[]" value="' + id + '" readonly required>\
            <fieldset class="form-group">\
                <div class="input-group">\
                    <span class="input-group-addon"><a href="javascript:void(0);" id="answer-del"><i class="material-icons">remove_circle_outline</i></a></span>\
                    <input class="form-control" type="text" name="answer[]" value="' + answer + '">\
                </div>\
            </fieldset>\
        </div>\
    ');
}