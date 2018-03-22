<p id="krona-loyalty">
    {if $minimum}
        {l s='Minimum amount to collect %s is' sprintf=$loyalty_name mod='genzo_krona'} {$minimum_amount}.
    {else}
        {l s='By checking out this cart, you will collect' mod='genzo_krona'} <b>{$krona_coins_in_cart} {$loyalty_name}</b>.
    {/if}
</p>