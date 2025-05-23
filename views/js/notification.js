// Initial wait time in milliseconds (1 minute)
let waitTime = 60000;
// Maximum wait time in milliseconds (1 hour)
const MAX_WAIT_TIME = 3600000;

document.addEventListener("DOMContentLoaded", function () {
    getNotification();
});

// Function to schedule the next notification check
function scheduleNextNotification() {
    setTimeout(function() {
        getNotification();
        // Double the wait time for the next call
        // The idea: the visitor is probably not active on the site -> lower server usage
        waitTime = Math.min(waitTime * 2, MAX_WAIT_TIME);
        // Schedule the next notification check
        scheduleNextNotification();
    }, waitTime);
}

// Start the notification scheduling
scheduleNextNotification();

function getNotification() {
    var request = new XMLHttpRequest();
    request.open('GET', '/modules/genzo_krona/ajax.php?notification=1', true);
    request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    request.send();

    // Set up a function to be called when the response is received
    request.onload = function () {
        if (request.status >= 200 && request.status < 300) {
            // Parse the JSON response
            var notification = JSON.parse(request.responseText);
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
}
