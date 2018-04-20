$(document).ready(function() {
    getNotification();
});

setInterval(function() {
    getNotification();
}, 60000);

function getNotification() {
    // id_customer is defined in HookDisplayHeader
    $.ajax({
        type: 'POST',
        url: '/modules/genzo_krona/ajax.php',
        data: {notification: id_customer},
        datatype: 'json',
        success: function (notification) {
            notification = $.parseJSON(notification);
            if (notification > 0) {
                $('#krona-notification').show().text(notification)
            }
            else {
                $('#krona-notification').hide();
            }
            console.log(notification);
        }
    });
}