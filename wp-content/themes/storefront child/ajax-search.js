jQuery(document).ready(function($) {
    $('#city-search').on('keyup', function() {
        var searchTerm = $(this).val();

        $.ajax({
            url: ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'city_search',
                search_term: searchTerm
            },
            success: function(response) {
                $('#cities-table tbody').html(response);
            }
        });
    });
});