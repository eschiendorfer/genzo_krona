$(document).ready(function() {

    // Check if all vars are set for loyalty points of a product
    if (
        typeof krona_coins_change !== 'undefined' &&
        typeof krona_coins_change_max !== 'undefined' &&
        typeof krona_coins_conversion !== 'undefined' &&
        typeof krona_coins_in_cart !== 'undefined' &&
        typeof krona_order_rounding !== 'undefined' &&
        typeof krona_tax !== 'undefined' &&
        typeof krona_tax_rate !== 'undefined'
    ) {
        // Catch all attribute changes of the product
        $(document).on('change', '.product_attributes input, .product_attributes select, #attributes select', function () {
            setTimeout(updateLoyaltyActionValue(), 100); // Schedule last
        });

        // Force color "button" to fire event change
        $('#color_to_pick_list').click(function () {
            setTimeout(updateLoyaltyActionValue, 100); // Schedule last
        });
        updateLoyaltyActionValue();
    }

    if (
        typeof loyalty_max !== 'undefined' &&
        typeof conversion !== 'undefined'
    ) {
        // Loyalty Conversion: Coupon Calculation
        calculateLoyaltyValue();

        $('#loyalty').on('input', function() {
            calculateLoyaltyValue();
        });
    }

});


function updateLoyaltyActionValue() {

    // Show Loyalty Points on Product Page
    var price;
    var coins_change = krona_coins_change;
    var coins_max = krona_coins_change_max;
    var coins_conversion = krona_coins_conversion;
    var coins_in_cart = krona_coins_in_cart;
    var rounding = krona_order_rounding;
    var tax = krona_tax;
    var tax_rate = krona_tax_rate;

    if (typeof window.priceWithDiscountsDisplay !== 'undefined') {
        price = window.priceWithDiscountsDisplay;
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

    var points = price * coins_change;

    if (rounding === 'up') {
        total_points = Math.ceil(coins_in_cart + points);
        points = Math.ceil(price * coins_change);
    }
    else if (rounding === 'down') {
        total_points = Math.floor(coins_in_cart + points);
        points = Math.floor(price * coins_change);
    }
    else {
        total_points = Math.round(coins_in_cart + points);
        points = Math.round(price * coins_change);
    }

    if (coins_max > 0) {
        points = Math.min(points, coins_max);
    }

    // var voucher = total_points * coins_conversion;

    $('#krona-loyalty-coins').html(points);
    $('#krona-loyalty-coins-total').html(total_points);
}


function calculateLoyaltyValue() {
    var loyalty = parseFloat($('#loyalty').val());

    if (loyalty > loyalty_max) {
        $('#loyalty').val(loyalty_max) ;
        loyalty = loyalty_max;
    }

    var coupon = (loyalty * conversion).toFixed(2);

    $('#coupon-value').text(coupon);
}

