document.addEventListener("DOMContentLoaded", function () {
    getNotification();
});

setInterval(function() {
    getNotification();
}, 60000);

function getNotification() {
    // id_customer is defined in HookDisplayHeader
    var xhr = new XMLHttpRequest();
    var url = '/modules/genzo_krona/ajax.php';
    var params = 'notification=' + id_customer;
    xhr.open('POST', url, true);

    // Send the proper header information along with the request
    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

    // Set up a function to be called when the response is received
    xhr.onload = function () {
        if (xhr.status >= 200 && xhr.status < 300) {
            // Parse the JSON response
            var notification = JSON.parse(xhr.responseText);
            var container = document.getElementById('krona-notification');

            // Check the notification value
            if (container) {
                if (notification > 0) {
                    container.style.display = 'block';
                    container.innerText = notification;
                } else {
                    container.style.display = 'none';
                }
            }
        }
    };

    // Send the request
    xhr.send(params);
}