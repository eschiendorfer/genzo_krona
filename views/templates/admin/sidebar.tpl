<div class="sidebar navigation col-md-2">
    <nav class="list-group category-list">
        <a href="{$link->getAdminLink('AdminGenzoKronaActions')}" class="list-group-item  {if $tab=='Actions'}active{/if}" >
            <i class="icon-play-circle"></i> {l s='Actions' mod='genzo_krona'}
        </a>
        <a href="{$link->getAdminLink('AdminGenzoKronaOrders')}" class="list-group-item  {if $tab=='Orders'}active{/if}" >
            <i class="icon-money"></i> {l s='Orders' mod='genzo_krona'}
        </a>
        <a href="{$link->getAdminLink('AdminGenzoKronaPlayers')}" class="list-group-item  {if $tab=='Players'}active{/if}" >
            <i class="icon-user"></i> {l s='Players' mod='genzo_krona'}
        </a>
        <a href="{$link->getAdminLink('AdminGenzoKronaLevels')}" class="list-group-item {if $tab=='Levels'}active{/if}" >
            <i class="icon-trophy"></i> {l s='Levels' mod='genzo_krona'}
        </a>
        <a href="{$link->getAdminLink('AdminGenzoKronaCoupons')}" class="list-group-item {if $tab=='Coupons'}active{/if}" >
            <i class="icon-money"></i> {l s='Coupons' mod='genzo_krona'}
        </a>
        <a href="{$link->getAdminLink('AdminGenzoKronaGroups')}" class="list-group-item {if $tab=='Groups'}active{/if}" >
            <i class="icon-group"></i> {l s='Groups' mod='genzo_krona'}
        </a>
        <a href="{$link->getAdminLink('AdminGenzoKronaSettings')}" class="list-group-item {if $tab=='Settings'}active{/if}" >
            <i class="icon-cogs"></i> {l s='Settings' mod='genzo_krona'}
        </a>
        <a href="{$link->getAdminLink('AdminGenzoKronaSupport')}" class="list-group-item {if $tab=='Support'}active{/if}" >
            <i class="icon-info-circle"></i> {l s='Support' mod='genzo_krona'}
        </a>
    </nav>
</div>