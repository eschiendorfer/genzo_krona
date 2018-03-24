$(document).ready(function() {

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

