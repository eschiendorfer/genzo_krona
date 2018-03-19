<div class="panel col-lg-12">
    <div class="panel-heading">{l s='Import existing Users:' mod='genzo_krona'}</div>
    <p>{l s='Do you wanna reward existing user with the account creation reward? Don\'t forget to save the display name settings first.' mod='genzo_krona'}</p>

    <form method="post">
        <div class="form-group">
            <label class="control-label col-lg-3">
                {l s='Import Core Loyality Points' mod='genzo_krona'}
            </label>
            <div class="col-lg-9">
                <div class="input-group fixed-width-lg">
                    <input type="text" name="import_points" value="0" class="">
                    <span class="input-group-addon">{$loyalty_name}</span>
                </div>
                <p class="help-block">{l s='1 old Point will be X new Points. If the value is 0, no import will happen. If you want to import, value equal 1 is recommended.' mod='genzo_krona'}</p>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">
                {l s='Points for old orders' mod='genzo_krona'}
            </label>
            <div class="col-lg-9">
                <span class="switch prestashop-switch fixed-width-lg">
                    <input type="radio" name="import_orders" id="type_switch_on" value="1">
                    <label for="type_switch_on">{l s='Yes' mod='genzo_krona'}</label>
                    <input type="radio" name="import_orders" id="type_switch_off" value="0" checked="checked">
                    <label for="type_switch_off">{l s='No' mod='genzo_krona'}</label>
                    <a class="slide-button btn"></a>
				</span>
                <p class="help-block">{l s='Make sure you have set up all the order actions! It will import all oders, which are on a paid status.' mod='genzo_krona'}</p>
            </div>
        </div>
        <button name="importCustomers" type="submit" class="btn-default btn">{l s='Import' mod='genzo_krona'}</button>
        <button name="dontImportCustomers" type="submit" style="float: right;" class="btn-default btn">{l s='Don\'t show this tab' mod='genzo_krona'}</button>
    </form>
</div>
<div class="clearfix"></div>

