<ul id="krona-nav">
    <li {if $active=='Overview'}class="active"{/if}>
        <a href="{$link->getModuleLink('genzo_krona', 'overview')}">{l s='Overview' mod='genzo_krona'}</a>
    </li>
    <li {if $active=='Timeline'}class="active"{/if}>
        <a href="{$link->getModuleLink('genzo_krona', 'timeline')}">{l s='Timeline' mod='genzo_krona'}</a>
    </li>
    <li {if $active=='Levels'}class="active"{/if}>
        <a href="{$link->getModuleLink('genzo_krona', 'levels')}">{l s='Levels' mod='genzo_krona'}</a>
    </li>
    {if $loyalty}
        <li {if $active=='Loyalty'}class="active"{/if}>
            <a href="{$link->getModuleLink('genzo_krona', 'loyalty')}">{$loyalty_name}</a>
        </li>
    {/if}
    <li {if $active=='Settings'}class="active"{/if}>
        <a href="{$link->getModuleLink('genzo_krona', 'customersettings')}">{l s='Settings' mod='genzo_krona'}</a>
    </li>
</ul>