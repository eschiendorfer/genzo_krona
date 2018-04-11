{capture name=path}
    <a href="{$link->getPageLink('my-account')}">{l s='My Account' mod='genzo_krona'}</a><span class="navigation-pipe"></span>
    <a href="{$link->getModuleLink('genzo_krona', 'home')}">{$game_name}</a><span class="navigation-pipe"></span>{l s='Levels' mod='genzo_krona'}
{/capture}

<h1>{l s='Your achieved levels' mod='genzo_krona'}</h1>

{include file="./nav.tpl"}

<div id="levels">
    {foreach from=$levels item=level}
        {include file="./level.tpl" next=false}
    {/foreach}
    {if $next_level}
        <div class="clear clearfix"></div>
        <h3 style="margin-bottom: 10px;">{l s='Next level' mod='genzo_krona'}:</h3>
        {include file="./level.tpl" level=$next_level next=true}
    {/if}
</div>

{include file="./footer.tpl"}
