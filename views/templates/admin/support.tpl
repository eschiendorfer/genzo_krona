{include file="./error.tpl"}

<div class="clearfix">
    {include file="./sidebar.tpl"}

    <div class="col-md-10">
        <div class="panel col-lg-12">
            <div class="panel-heading">{l s='Bug Reporting' mod='genzo_krona'}</div>
            <p><b>Github:</b> <a target="_blank" href="https://github.com/eschiendorfer/genzo_krona">https://github.com/eschiendorfer/genzo_krona</a></p>
            <p><b>Forum:</b> <a target="_blank" href="https://forum.thirtybees.com/topic/1505/planned-free-module-loyalty-points">https://forum.thirtybees.com/topic/1505/planned-free-module-loyalty-points</a></p>
        </div>
        <div class="panel col-lg-12">
            <div class="panel-heading">{l s='Coupons' mod='genzo_krona'}</div>
            <p>{l s='This module is using the core cart_rules as templates. That way, you will have all possible functions! How to use ist?' mod='genzo_krona'}</p>
            <ol>
                <li>{l s='Create a cart rule, as you want the coupon to be. The name has to begin with' mod='genzo_krona'} <b>"KronaTemplate:"</b>. {l s='Example:' mod='genzo_krona'} "KronaTemplate: 10% Coupon".</li>
                <li>{l s='Deactivate the just created cart_rule, since it\'s just a helper. Your customer will get individual and active coupons with the same conditions.' mod='genzo_krona'}</li>
                <li>{l s='Go to coupons tab in this module and check if the just created coupon shows up.' mod='genzo_krona'}</li>
                <li>{l s='From now on, you can select this coupon as a reward in levels.' mod='genzo_krona'}</li>
            </ol>
            <p style="color:red;"><b>{l s='Warning:' mod='genzo_krona'}</b> {l s='Never delete any core cart_rule, which is used as a template. In other words: Never delete a cart_rule beginning with "KronaTemplate:"!' mod='genzo_krona'}</p>
            <p>{l s='Note: The customer will see the coupon name without the "KronaTemplate:" part. In the example above he would see' mod='genzo_krona'} "10% Coupon".</p>
        </div>

        <div class="panel col-lg-12">
            <div class="panel-heading">{l s='Do you like this module?' mod='genzo_krona'}</div>
            <div style="margin-right: 3%;" class="support-box">
                <p><b>{l s='Option 1: I like pizza and beer ;)' mod='genzo_krona'}</b></p>
                <p>{l s='If you want to make a donation to me for this module. Here is my paypal Account:' mod='genzo_krona'}</p>
                <a href="https://www.paypal.me/ESchiendorfer" target="_blank">paypal.me/ESchiendorfer</a>
            </div>
            <div style="margin-right: 3%;" class="support-box">
                <p><b>{l s='Option 2: My store likes links' mod='genzo_krona'}</b></p>
                <p>{l s='I am a merchant myself. If you could link my store spielezar.ch, it would help me a lot!' mod='genzo_krona'}</b></p>
                <a href="https://www.spielezar.ch" target="_blank">https://www.spielezar.ch</a>
            </div>
            <div class="support-box">
                <p><b>{l s='Option 3: thirty bees likes Patreons' mod='genzo_krona'}</b></p>
                <p>{l s='Thirty bees is a wonderful open source project. It will become even more powerful, if you support it as a Patreon!' mod='genzo_krona'}</b></p>
                <a href="https://www.patreon.com/thirtybees">{l s='Become a Patreon!' mod='genzo_krona'}</a>
            </div>
        </div>
    </div>
</div>