window.addEventListener('DOMContentLoaded', function(event) {
    // Ajax Page Visit
    // id_customer is defined in HookDisplayHeader
    var request = new XMLHttpRequest();
    request.open('POST', '/modules/genzo_krona/ajax.php', true);
    request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    request.send('page_visit='+parseInt(id_customer));

    request.onload = function() {
        if (this.status >= 200 && this.status < 400) {

        }
    }
});