{capture name=path}
    <a href="{$link->getPageLink('my-account')}">{l s='My Account' mod='genzo_krona'}</a><span class="navigation-pipe"></span>
    <a href="{$link->getModuleLink('genzo_krona', 'home')}">{$game_name}</a><span class="navigation-pipe"></span>{$loyalty_name}
{/capture}

{include file="./nav.tpl"}

{include file="./error.tpl"}

<div class="krona-box">
    <h1>{$loyalty_name}: {$player.loyalty}</h1>
    <p>{l s='Convert your %s now into a coupon and save money on your next order!' sprintf=$loyalty_name mod='genzo_krona'}</p>
    <form method="post" enctype="multipart/form-data">

            <div class="form-row">
                <div class="krona-label"><label for="loyalty"><b>{l s='Loyalty Points to convert:' mod='genzo_krona'}</b></label></div>
                <input type="text" class="form-control" id="loyalty" name="loyalty" placeholder="0">
            </div>

            <div class="form-row">
                <div id="coupon"><b>{l s='Value of Coupon:' mod='genzo_krona'}</b> <span id="coupon-value">0.00</span> {$krona_currency}</div>
            </div>

        <button type="submit" name="convertLoyalty" class="krona-button">{l s='Convert' mod='genzo_krona'}</button>
    </form>
</div>
<a style="float: right;" href="{$link->getPageLink('discount')}" class="krona-button">{l s='See all my coupons' mod='genzo_krona'}</a>

{include file="./footer.tpl"}