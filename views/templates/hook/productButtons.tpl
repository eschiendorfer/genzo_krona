<div id="krona-loyalty">
    {l s='If you buy this product, you will receive' mod='genzo_krona'} ~<b><span id="krona-loyalty-coins"></span> {$loyalty_name}</b>.
    {if $krona_coins_in_cart > 0}
        {l s='In your cart are already products. The total value of your order will be' mod='genzo_krona'} ~<b><span id="krona-loyalty-coins-total"></span> {$loyalty_name}</b>.
    {/if}
</div>