<div id="krona-loyalty">

        {if $player.loyalty > 0 }
            <h3>{$loyalty_name} {l s='Conversion' mod='genzo_krona'}</h3>
            <form method="post" enctype="multipart/form-data">
                <div class="form-row">
                    <input type="text" class="form-control" id="loyalty" name="loyalty" value="{$player.loyalty}"> <button type="submit" name="convertLoyalty" class="krona-button">{l s='Convert and save' mod='genzo_krona'} <span id="coupon-value">0.00</span> {$krona_currency}</button>
                </div>
            </form>
        {/if}
    <p>
        {if $minimum}
            {l s='Minimum amount to collect %s is' sprintf=$loyalty_name mod='genzo_krona'} {$minimum_amount}.
        {else}
            {l s='By checking out this cart, you will collect' mod='genzo_krona'} <b>{$krona_coins_in_cart} {$loyalty_name}</b>.
        {/if}
    </p>
</div>