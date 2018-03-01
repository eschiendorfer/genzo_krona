<ul id="krona-nav">
    <li {if $active=='Overview'}class="active"{/if}>
        <a href="/{$slack}/overview">{l s='Overview' mod='genzo_krona'}</a>
    </li>
    <li {if $active=='Timeline'}class="active"{/if}>
        <a href="/{$slack}/timeline">{l s='Timeline' mod='genzo_krona'}</a>
    </li>
    <li {if $active=='Levels'}class="active"{/if}>
        <a href="/{$slack}/levels">{l s='Levels' mod='genzo_krona'}</a>
    </li>
    <li {if $active=='Settings'}class="active"{/if}>
        <a href="/{$slack}/settings">{l s='Settings' mod='genzo_krona'}</a>
    </li>
</ul>