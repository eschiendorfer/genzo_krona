<div class="panel kpi-container">
    <div class="row">
        {if $loyalty_active}
            <div class="col-xs-6 col-sm-3 box-stats color4">
                <div class="kpi-content">
                    <i class="icon-money"></i>
                    <span class="title">{l s='Outstanding'} {$loyalty_name}</span>
                    <span class="value">{$stats.loyalty}</span>
                </div>
            </div>
        {/if}
    </div>
</div>

