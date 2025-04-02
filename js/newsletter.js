jQuery(document).ready(function ($) {
    $('#nw-newsletter-form').on('submit', function (e) {
        e.preventDefault();

        let name = $('#nw-name').val();
        let email = $('#nw-email').val();
        let signupUrl = window.location.href;

        $.ajax({
            url: nwAjax.ajaxUrl,
            type: 'POST',
            data: {
                action: 'nw_newsletter_signup',
                security: nwAjax.security,
                name: name,
                email: email,
                url: signupUrl,
            },
            success: function (response) {
                if (response.success) {
                    $('#nw-response-message').text(response.data.message).css('color', 'green');
                    $('#nw-newsletter-form')[0].reset();
                } else {
                    $('#nw-response-message').text(response.data.message).css('color', 'red');
                }
            },
            error: function () {
                $('#nw-response-message').text('An error occurred. Please try again.').css('color', 'red');
            },
        });
    });
});
