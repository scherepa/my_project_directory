$(document).ready(function() {
        // Show flash messages and fade out after 10 seconds
        $('.flash-message').each(function() {
            $(this).fadeIn('slow').delay(5000).animate({ left: '+=200', opacity: 0 }, 2000);
        });
    });