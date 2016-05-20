/* Global variables. */
const sess_id = $('meta[name=session]').attr('content');
var current_question = -1,
    total_questions = -1
    error = false;

/* When the document is ready start constantly
    checking for updates in our session. */
$(function() {
    setInterval(function() {
        update(sess_id);
    }, 1000);
});

/* Update function that will be called every second.
    This function will send a POST request to the server
    in order to get the latest information from it. */
function update(sess_id) {
    $.ajax({
        type: 'POST',
        url: '/sess_read.php',
        data: {'sess_id' : sess_id},
        dataType: 'json',
        async: true
    }).done(onUpdateSuccess)
      .fail(onError);
};

/* Function that is called when our update function
    is successful. */
function onUpdateSuccess(data) {
    if (data['success'] === true) { //Successful response
        /* Check if our session has updated or ended. */
        if (data['current_question'] == -1) { //Ended
            window.location.replace('/panel.php');
            return;
        } else if (current_question == data['current_question']) return; //No change
        
        /* Save current/total question information into
            our global variables. */
        current_question = data['current_question'];
        total_questions = data['total_questions'];
        
        /* Update question/answers. */
        setQuestion(data['question'], data['answers']);
    } else {
        setLoading('Loading next question...'); //Unsuccessful response
        error = true;
    }
};

/* Function that is called when an AJAX call fails.
    (Eg: Bad response, invalid data, etc.) */
function onError(data) {
    if (error === true) return; //Skip if we already got an error.
    $.snackbar({content: '<b>ERROR:</b> Could not process request.<br>Please try again.', timeout: 5000, htmlAllowed: true});
    console.log(data);
};

/* Function that will update the HTML on the page
    to display the current question and the answers related
    to that question. */
function setQuestion(question, answers) {
    /* Create our empty HTML variable. */
    var html = '';
    
    /* Add the question to our HTML variable. */
    html += '<h2>Question #' + current_question + '</h2>\
             <h3 id="question">' + question + '</h3>\
             <hr>\
             <fieldset id="answers" class="form-group no-margin">';
        
    /* Add each answer to our HTML variable. */
    var key;
    for (key in answers) {
        html += '<div class="radio radio-accent">\
                    <label><input type="radio" name="answer" value="' + parseInt(answers[key]['id']) + '"> ' + answers[key]['answer'] + '</label>\
                 </div>';
    }
    html += '</fieldset>';
    
    html += '<div class="row clearfix">\
                <div class="col-xs-12">\
                    <div class="text-right">\
                        <button id="answer-submit" class="btn btn-raised btn-accent">Submit</button>\
                        <button id="answer-cancel" class="btn btn-default">Cancel</button>\
                    </div>\
                </div>\
             </div>';
    
    /* Set our content's HTML to our HTML variable and reload
        some material features. */
    $('#content').html(html);
    $.material.radio();
    $.material.ripples();
};

/* Function that will set the content area to be "loading".
    This is used after a user has answered a question or if
    we are waiting for a response. */
function setLoading(header) {
    if (error === true) return; //Skip if there is an error.
    $('#content').html('\
        <div class="text-center">\
            <h2>' + header + '<br><small>Please wait</small></h2>\
            <span class="loader">\
                <div class="dot"></div>\
                <div class="dot"></div>\
                <div class="dot"></div>\
                <div class="dot"></div>\
            </span>\
        </div>');
};

$('#content').on('click', '#answer-submit', function() {
    var $answer = $('#answers input[name=answer]:checked');
    if ($answer.length == 0) {
        alert('You must select an answer.');
        return false;
    }
    
    submitAnswer($answer.val());
});

function submitAnswer(id) {
    $.ajax({
        type: 'POST',
        url: '/sess_submit.php',
        data: {'sess_id' : sess_id, 'ans_id' : id},
        dataType: 'json',
        async: true
    }).fail(onError)
      .done(function(data) {
        if (data['success'] === true) {
            setLoading('Waiting for next question...');
            $.snackbar({content: 'Answer submit successfully!<br>Please wait for the next question.', timeout: 5000, htmlAllowed: true});
        } else {
            onError(data); //Unsuccessful response
            error = true;
        }
      });
};