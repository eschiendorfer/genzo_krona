<div class="panel col-lg-12">
    <div class="panel-heading">{l s='Import existing Users:' mod='genzo_krona'}</div>
    <p>{l s='Do you wanna reward existing user with the account creation reward? Don\'t forget to save the display name settings first.' mod='genzo_krona'}</p>

    <form method="post">

        <div class="form-group">
            <label class="control-label col-lg-3">
                {l s='Import Core Loyality Points' mod='genzo_krona'}
            </label>
            <div class="col-lg-9">
                <div class="input-group fixed-width-md">
                    <input type="text" name="import_points" value="" class="">
                    <span class="input-group-addon">{$points_name}</span>
                </div>
                <p class="help-block">{l s='1 old Point will be X new Points. Leave it empty, if you dont want to import it.' mod='genzo_krona'}</p>
            </div>
        </div>

        <button name="importCustomers" type="submit" class="btn-default btn">{l s='Import' mod='genzo_krona'}</button>
        <a type="submit" style="float: right;" href="{$action_url}&dontImportCustomers" class="btn-default btn">{l s='Don\'t show this tab' mod='genzo_krona'}</a>
    </form>

</div>
<div class="clearfix"></div>

