
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
        }
        
        /* Save current/total question information into
            our global variables. */
        current_question = data['current_question'];
        total_questions = data['total_questions'];
        checkButtons();
        
        /* Update question/answers. */
        setQuestion(data['question'], data['answers'], data['total_responses']);
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
function setQuestion(question, answers, responses) {
    /* Create our empty HTML variable. */
    var html = '';
    
    /* Add the question to our HTML variable. */
    html += '<h2>Question #' + current_question + '</h2>\
             <h3 id="question">' + question + '</h3>\
             <hr>\
             <fieldset id="answers">';
        
    /* Add each answer to our HTML variable. */
    var key;
    for (key in answers) {
        var p = (parseInt(answers[key]['responses']) / responses) * 100;
        html += '<div class="row clearfix">\
                    <div class="col-xs-2">' + answers[key]['answer'] + '</div>\
                    <div class="col-xs-10">\
                        <div class="progress">\
                            <div class="progress-bar progress-bar-accent" style="width: ' + p + '%"></div>\
                        </div>\
                    </div>\
                 </div>';
    }
    html += '</div>';
    
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

/* Disable the current session. */
$('#delete').click(function() {
    $.ajax({
        type: 'POST',
        url: '/sess_delete.php',
        data: {'sess_id' : sess_id},
        dataType: 'json',
        async: true
    }).fail(onError);
});

/* Set the current question to the previous one if possible. */
$('#controls').on('click', '#prev', function() {
    if (current_question <= 1) return; //Ignore if we can't go lower
    updateCurrentQuestion(current_question - 1);
});

/* Set the current question to the next one if possible. */
$('#controls').on('click', '#next', function() {
    if (current_question >= total_questions) return; //Ignore if we can't go higher
    updateCurrentQuestion(current_question + 1);
});

/* Function that will send a POST request to the server
    and attempt to change the current question. */
function updateCurrentQuestion(num) {
    $.ajax({
        type: 'POST',
        url: '/sess_update.php',
        data: {'sess_id' : sess_id, 'current_question' : num},
        dataType: 'json',
        async: true
    }).fail(onError);
};

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

/* Check if the previous/next buttons need disabled based on
    the current question we are on. */
function checkButtons() {
    if (current_question > 1) {
        $('#prev').attr('disabled', false);
    } else {
        $('#prev').attr('disabled', true);
    }
    
    if (current_question >= total_questions) {
        $('#next').attr('disabled', true);
    } else {
        $('#next').attr('disabled', false);
    }
};