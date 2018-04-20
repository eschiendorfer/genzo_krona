{capture name=path}
    <a href="{$link->getPageLink('my-account')}">{l s='My Account' mod='genzo_krona'}</a><span class="navigation-pipe"></span>
    <a href="{$link->getModuleLink('genzo_krona', 'home')}">{$game_name}</a><span class="navigation-pipe"></span>{l s='Timeline' mod='genzo_krona'}
{/capture}

{include file="./nav.tpl"}

<div class="krona-box">
    <h1>{l s='Your Timeline' mod='genzo_krona'}</h1>
    <div style="padding-left: 20px;">
        {foreach from=$history item=his}
            <div class="timeline-item {if $his.change > 0}green{elseif $his.change==0}grey{else}red{/if}" points-is="{$his.change|replace:"-":""}">
                <h3>{$his.title}</h3>
                <p>{if $his.date_add|date_format:"%Y" == 'Y'|date}
                        {$his.date_add|date_format:"%d. %b"}:
                    {else}
                        {$his.date_add|date_format:"%d. %b %Y"}:
                    {/if}
                    {$his.message} {if $his.url}<a href="{$his.url}"><i class="fa fa-link"></i></a>{/if}
                </p>
            </div>
        {/foreach}
    </div>
    {include file="./pagination.tpl"}
</div>

{include file="./footer.tpl"}