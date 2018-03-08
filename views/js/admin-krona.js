$(document).ready(function() {
    // Load Chosen
    $(".chosen").chosen();


    // Action Form: hide execution_max field if execution_type is unlimited
    $('#genzo_krona_action_form').on('change', function() {

        var id_form = 'genzo_krona_action_form';

        if (($("#execution_type").val()) === "unlimited") {
            hideElement(id_form, 'execution_max');
        }
        else {
            showElement(id_form, 'execution_max');
        }

    }).trigger('change');

    // Custom Action Form:
    $('#genzo_krona_custom_action_form').on('change', function() {

        var id_form = 'genzo_krona_custom_action_form';
        var actionType = $("#action_type").val();

        if (actionType === "custom") {
            showElement(id_form, 'title_1');
            showElement(id_form, 'message_1');
            hideElement(id_form, 'id_action');
            hideElement(id_form, 'id_action_order');
            showElement(id_form, 'points_change');
            showElement(id_form, 'coins_change');
        }
        else if (actionType === "action") {
            hideElement(id_form, 'title_1');
            hideElement(id_form, 'message_1');
            showElement(id_form, 'id_action');
            hideElement(id_form, 'id_action_order');
            showElement(id_form, 'points_change');
            hideElement(id_form, 'coins_change');
        }
        else if (actionType === "order"){
            hideElement(id_form, 'title_1');
            hideElement(id_form, 'message_1');
            hideElement(id_form, 'id_action');
            showElement(id_form, 'id_action_order');
            hideElement(id_form, 'points_change');
            showElement(id_form, 'coins_change');
        }

    }).trigger('change');

    // Settings Form:
    $('#genzo_krona_settings_form').on('change', function() {

        var id_form = 'genzo_krona_settings_form';

        if (parseInt($("[name=loyalty_active]:checked").val()) === 0) {
            hideElement(id_form, 'loyalty_total');
        }
        else {
            showElement(id_form, 'loyalty_total');
        }

        if (parseInt($("[name=gamification_active]:checked").val()) === 0) {
            hideElement(id_form, 'gamification_total');
        }
        else {
            showElement(id_form, 'gamification_total');
        }

    }).trigger('change');

    // Level Form:
    $('#genzo_krona_level_form').on('change', function() {

        var id_form = 'genzo_krona_level_form';

        var condition_type = $("#condition_type").val();

        if (condition_type === "action") {
            showElement(id_form, 'id_action');
            showElement(id_form, 'condition_action');
            hideElement(id_form, 'condition_points');
        }
        else {
            hideElement(id_form, 'id_action');
            hideElement(id_form, 'condition_action');
            showElement(id_form, 'condition_points');

            // Refresh if points, coins or lifetime
            var type = $( "#condition_type option:selected" ).text().split(":").pop();
            $("#condition_points").nextAll('span:first').text(type);
        }

        reward_type = $("#reward_type").val();
        if (reward_type === "symbolic") {
            hideElement(id_form, 'id_reward_group');
            hideElement(id_form, 'id_reward_coupon');
            hideElement(id_form, 'id_group');
        }
        else if (reward_type === "coupon") {
            showElement(id_form, 'id_reward_coupon');
            hideElement(id_form, 'id_reward_group');
            hideElement(id_form, 'id_group');
        }
        else if (reward_type === "group") {
            showElement(id_form, 'id_reward_group');
            showElement(id_form, 'id_group');
            hideElement(id_form, 'id_reward_coupon');
        }

    }).trigger('change');

    function hideElement(form, id) {
        $('#'+form+' #'+id).closest('.form-wrapper > .form-group').hide();
    }
    function showElement(form, id) {
        $('#'+form+' #'+id).closest('.form-wrapper > .form-group').show();
    }



    // Sortable for Group Priority in Settings Tab

    var fixHelper = function(e, ui) { ui.children().each(function() { $(this).width($(this).width()); }); return ui; };

    var sortOrder = [];
    var $sortableTable  = $("#sortable tbody");

    $sortableTable.sortable({
        start: function(event, element){
            $.map($('tr [name^=position]', $sortableTable), function(element){
                sortOrder.push(element.value);
            });
        },
        stop: function(event, element) {
            $.each($('tr [name^=position]', $sortableTable), function(index, element){
                element.value = sortOrder[index];
            });
        }
    });

    $sortableTable.disableSelection();

    $('tr [name^=position]', $sortableTable).on('keydown', function(){
        $(this).closest('tr').data()
    });



});
