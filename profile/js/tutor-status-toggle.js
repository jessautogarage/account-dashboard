jQuery(document).ready(function ($) {
    $('#tutorStatusToggle').on('change', function () {
        const status = $(this).is(':checked') ? 'online' : 'offline';
        const label = $(this).siblings('label');

        $.post(tutorStatusAjax.ajax_url, {
            action: 'toggle_tutor_status',
            nonce: tutorStatusAjax.nonce,
            status: status
        }, function (response) {
            if (response.success) {
                label.text(status.charAt(0).toUpperCase() + status.slice(1));
                label.css('color', status === 'online' ? '#28a745' : '#6c757d');
            } else {
                alert('Status update failed.');
            }
        });
    });
});
