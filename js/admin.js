jQuery(document).ready(function ($) {
    // Show the modal
    $('#add-subscriber-button').on('click', function () {
        $('#add-subscriber-modal').fadeIn();
    });

    // Hide the modal
    $('#close-modal-button').on('click', function () {
        $('#add-subscriber-modal').fadeOut();
    });

    // Handle form submission
    $('#add-subscriber-form').on('submit', function (e) {
        e.preventDefault();

        const name = $('#subscriber-name').val();
        const email = $('#subscriber-email').val();

        $.ajax({
            url: nwAdminAjax.ajaxUrl,
            method: 'POST',
            data: {
                action: 'nw_add_subscriber',
                security: nwAdminAjax.security,
                name: name,
                email: email,
            },
            success: function (response) {
                if (response.success) {
                    alert(response.data.message);
                    $('#add-subscriber-modal').fadeOut();
                    location.reload(); // Refresh the page to update the table
                } else {
                    alert(response.data.message);
                }
            },
            error: function () {
                alert('An error occurred while adding the subscriber.');
            },
        });
    });
});
