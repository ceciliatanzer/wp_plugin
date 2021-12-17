/*
GETTING INPUT VALUE, PASSING IT ON TO spricecrm_search.php AND PASSING ON TABLE TO DIV-WRAPPER #output
 */
var timeout;
jQuery(document).ready(function($) {
    if (timeout) {
        clearTimeout(timeout);
    }
    timeout = setTimeout(function () {
        $.ajax({
            type: 'GET',
            url: '../wp-content/plugins/spicecrm_events/spicecrm_search.php',
            data: {input_currentpage: $('#currpage').val(), input_search: $('#search').val()},
            success: function (data) {
                $('#output').html(data);
            },
            error: function () {
                //alert('Fehler!');
            }
        });
    },5000);
});

jQuery(document).ready(function($) {
    $('#search').keyup(function () {
        if (timeout) {
            clearTimeout(timeout);
        }
        timeout = setTimeout(function () {
            $.ajax({
                type: 'GET',
                url: '../wp-content/plugins/spicecrm_events/spicecrm_search.php',
                data: {input_currentpage: $('#currpage').val(), input_search: $('#search').val()},
                success: function (data) {
                    $('#output').html(data);
                },
                error: function () {
                    //alert('Fehler!');
                }
            });
        },5000);
    });
});
