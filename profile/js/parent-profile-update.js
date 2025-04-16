jQuery(document).ready(function ($) {
    let timeout;

    $('#avs_search').on('input', function () {
        clearTimeout(timeout);
        let query = $(this).val();

        if (query.length < 3) {
            $('#avs_suggestions').empty();
            return;
        }

        timeout = setTimeout(function () {
            $.get(parentProfileAjax.ajax_url, {
                action: 'search_avs_child',
                avs: query
            }, function (results) {
                let suggestions = results.map(child => `
                    <a href="#" class="list-group-item list-group-item-action" data-id="${child.id}" data-name="${child.child_first_name} ${child.child_last_name}">
                        ${child.child_first_name} ${child.child_last_name} (${child.avs_number})
                    </a>
                `).join('');
                $('#avs_suggestions').html(suggestions).show();
            });
        }, 300);
    });

    // Select child
    $(document).on('click', '#avs_suggestions a', function (e) {
        e.preventDefault();
        const name = $(this).data('name');
        const id = $(this).data('id');

        $('#avs_search').val(name);
        $('#child_id').val(id);
        $('#avs_suggestions').hide();
    });

    // Hide on click outside
    $(document).on('click', function (e) {
        if (!$(e.target).closest('#avs_search, #avs_suggestions').length) {
            $('#avs_suggestions').hide();
        }
    });
});
