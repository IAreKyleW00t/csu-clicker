$(document).ready(function() {
    $.material.init(); //Initialize material elements
    $('[data-toggle="tooltip"]').tooltip(); //Enable tooltips

    /* Cookie Consent options. */
    window.cookieconsent_options = {
        'message' : 'This website uses cookies to ensure you get the best experience.',
        'dismiss' : 'Got it!',
        'learnMore' : 'More info',
        'link' : null,
        'theme' : 'dark-bottom'
    };
    
    /* Automatically show FAB when the page loads. */
    popupFab(true);
});

/* Autoclose mobile navbar when clicking a link
    or clicking outside of the navbar. */
$(document).click(function(e) {
    var clickover = $(e.target);
    var navbar = $('.navbar-collapse');
    if (navbar.hasClass('in') === true && !clickover.hasClass('navbar-toggle')) {
        navbar.collapse('hide');
    }
});

/* Customer data-toggle to switch between modals. */
$('[data-toggle="switch"]').click(function() {
    var target = $(this).data('target');
    $(this).closest('.modal').modal('hide');
    $(target).modal('show');
});

/* Autofocus first input when a modal is shown.
    (Does not use the autofocus attribute.) */
$('.modal').on('shown.bs.modal', function() {
    $('input:visible:first', this).focus(); //Focus first text input
});

/* Fix the modal style bug that is caused when a scrollbar
    is visible while opening a modal. */
$('.modal').on('hidden.bs.modal', function() {
    $('body').removeAttr('style');
});

/* Automatically hide all fab buttons when scrolling down
    and reshow them when scrolling up. */
var $window = $(window);
var pos = $window.scrollTop();
var up = false;
var scroll;
$window.scroll(function () {
    scroll = $window.scrollTop();
    if (scroll > pos && !up) {
        popupFab(false);
        up = !up;
    } else if (scroll < pos && up) {
        popupFab(true);
        up = !up;
    }
    
    pos = scroll;
});

/* Helper function to control whether the FAB is
    displayed on the page or not. */
function popupFab(toggle) {
    if (toggle == true) {
        $('.fab').stop().animate({
            bottom: '20px'
        }, 400);
    } else if (toggle == false) {
        $('.fab').stop().animate({
            bottom: '-60px'
        }, 400);
    }
}