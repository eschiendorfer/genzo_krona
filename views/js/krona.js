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


    // Loyalty Conversion: Coupon Calculation
    $('#loyalty').on('input', function() {

        var loyalty = parseFloat($(this).val());

        if (loyalty > loyalty_max) {
            $(this).val(loyalty_max) ;
        }

        var coupon = (loyalty * conversion).toFixed(2);

        $('#coupon-value').text(coupon);
    });



    // Show Loyalty Points on Product Page

    var price;
    var coins_change = krona_coins_change;
    var coins_conversion = krona_coins_conversion;
    var coins_in_cart = krona_coins_in_cart;
    var rounding = krona_order_rounding;
    var tax = krona_tax;
    var tax_rate = krona_tax_rate;

    function updateLoyalty() {

        if (typeof window.priceWithDiscountsDisplay !== 'undefined') {
            price = window.priceWithDiscountsDisplay;
        }
        else if (typeof window.productPriceWithoutReduction !== 'undefined') {
            price = window.productPriceWithoutReduction;
        }
        else if (typeof window.productPrice !== 'undefined') {
            price = window.productPrice;
        }
        else {
            return;
        }

        if (!tax) {
            price = price / tax_rate;
        }

        var points = parseInt(price * coins_change, 10);
        var total_points = coins_in_cart + points;

        if (rounding === 'up') {
            total_points = Math.ceil(total_points)
        }
        else {
            total_points = Math.floor(total_points);
        }

        // var voucher = total_points * coins_conversion;

        $('#krona-loyalty-coins').html(points);
        $('#krona-loyalty-coins-total').html(total_points);
    }

    // Catch all attribute changes of the product
    $(document).on('change', '.product_attributes input, .product_attributes select, #attributes select', function () {
        setTimeout(updateLoyalty, 100); // Schedule last
    });

    // Force color "button" to fire event change
    $('#color_to_pick_list').click(function () {
        setTimeout(updateLoyalty, 100); // Schedule last
    });
    updateLoyalty();
});

