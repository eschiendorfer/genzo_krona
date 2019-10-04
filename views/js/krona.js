$(document).ready(function() {

    // Avatar Upload -> Trigger the hidden file input
    $('#avatar-button').click(function(){
        $("input#avatar-input").click();
    });
    // Refresh the fake input with the selected image
    $('input#avatar-input').change(function(e){
        var filename = $('input#avatar-input')[0].files[0].name;
        $( 'input#avatar-fake' ).val(filename);
    });

});
