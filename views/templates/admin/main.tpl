{include file="./error.tpl"}

<div class="clearfix">
    {include file="./sidebar.tpl"}

    <div class="col-md-10">
        {if $tab == 'Coupon'}
        <div class="panel col-lg-12">
            <div class="panel-heading">Note:</div>
            <div>{l s='Only Cart Rules with starting "KronaTemplate:" are available! Learn more under "Support".' mod='genzo_krona'}</div>
        </div>
        {/if}
        {if $tab == 'Players' AND $import==0 AND $dont==0}
            <div class="panel col-lg-12">
                <div class="panel-heading">{l s='Import existing Users:' mod='genzo_krona'}</div>
                <p>{l s='Do you wanna reward existing user with the account creation reward? Don\'t forget to save the pseudonym settings first.' mod='genzo_krona'}</p>
                <a href="{$action_url}&importCustomers" type="submit" class="btn-default btn">{l s='Import' mod='genzo_krona'}</a>
                <a type="submit" style="float: right;" href="{$action_url}&dontImportCustomers" class="btn-default btn">{l s='Don\'t show this tab' mod='genzo_krona'}</a>
            </div>
            <div class="clearfix"></div>
        {/if}


        {$content}

        {if $tab == 'Settings'}
            {include file="./customer_groups.tpl"}
        {/if}
    </div>

</div>

