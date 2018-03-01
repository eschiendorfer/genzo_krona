$(document).ready(function() {

    // Ajax Page Visit
    // id_customer is defined in HookDisplayHeader
    $.ajax({
        type: 'POST',
        url: '/modules/genzo_krona/ajax.php',
        data: {page_visit: id_customer},
        datatype: 'json',
        success: function (response) {
            response = $.parseJSON(response);
            console.log(response);
        }
    });

});
