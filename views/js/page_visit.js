window.addEventListener('DOMContentLoaded', function () {
    if (!kronaPlayerActive) {
        return;
    }

    var request = new XMLHttpRequest();
    request.open('POST', '/modules/genzo_krona/ajax.php', true);
    request.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
    request.send('page_visit=1');
});
