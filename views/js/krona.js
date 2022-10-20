window.addEventListener('DOMContentLoaded', function(event) {

    var avatar_input = document.getElementById('avatar-input');

    // Avatar Upload -> Trigger the hidden file input
    document.getElementById('avatar-button').addEventListener('click', function (event) {
        avatar_input.click();
    });

    // Refresh the fake input with the selected image
    // Todo: check why this fake stuff is even needed. Maybe just for styling reasons!?
    avatar_input.addEventListener('change', function (event) {
        document.getElementById('avatar-fake').value = avatar_input.value;
    });

});

