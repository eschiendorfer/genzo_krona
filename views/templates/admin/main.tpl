{include file="./error.tpl"}

<div id="genzo_krona" class="clearfix">

    {include file="./sidebar.tpl"}

    <div class="col-md-10">

        {if $tab == 'Coupon'}
        <div class="panel col-lg-12">
            <div class="panel-heading">Note:</div>
            <div>{l s='Only Cart Rules with starting "KronaTemplate:" are available! Learn more under "Support".' mod='genzo_krona'}</div>
        </div>
        {/if}

        {if $tab == 'Players' AND !$import AND $dont==0}
            {include file="./import.tpl"}
        {/if}

        {$content}

        {if $tab == 'Players'}
            {include file="./delete_players.tpl"}
        {/if}

    </div>

</div>

