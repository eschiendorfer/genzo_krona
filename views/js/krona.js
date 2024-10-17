window.addEventListener('DOMContentLoaded', function(event) {

    /*var avatar_input = document.getElementById('avatar-input');

    // Avatar Upload -> Trigger the hidden file input
    document.getElementById('avatar-button').addEventListener('click', function (event) {
        avatar_input.click();
    });

    // Refresh the fake input with the selected image
    // Todo: check why this fake stuff is even needed. Maybe just for styling reasons!?
    avatar_input.addEventListener('change', function (event) {
        document.getElementById('avatar-fake').value = avatar_input.value;
    });*/

});

var communityMembers = [];

function loadCommunityMembers() {
    return new Promise(function(resolve, reject) {
        if (communityMembers.length > 0) {
            resolve(true); // already loaded
        } else {
            var request = new XMLHttpRequest();
            request.open('GET', '/modules/genzo_krona/ajax.php?loadCommunityMembers', true);
            request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            request.send();

            request.onload = function() {
                if (this.status >= 200 && this.status < 400) {
                    communityMembers = JSON.parse(this.response);
                    resolve(true); // Successfully loaded
                } else {
                    reject("Failed to load community members");
                }
            };
        }
    });
}

