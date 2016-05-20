/* Override the default submit action for the join form.
    We do this because it's faster for us to just parse the
    input here and redirect the user to the session page. That
    page will handle invalid inputs. */
$('#form-session').submit(function() {
    var token = $('#token', this).val();
    window.location.replace('/session.php?token=' + token);
});

/* Function that will create a new session based on which
    button a user clicked in the question set list. Upon
    success, they will be redirected to the session page. */
$('#question-sets').on('click', '#session', function() {
    var $tr = $(this).closest('tr');
    var id = $tr.attr('id');
    
    $.post('/sess_create.php', {set_id : id})
        .done(function(json) {
            if (json['success'] == true) {
                window.location.replace('/session.php?token=' + json['token']);
            } else {
                $.snackbar({content: '<b>ERROR:</b> Could not process request.<br>Please try again.', timeout: 5000, htmlAllowed: true});
            }
        })
        .fail(function(data) {
            $.snackbar({content: '<b>ERROR:</b> Could not process request.<br>Please try again.', timeout: 5000, htmlAllowed: true});
        });
});
