{capture name=path}<a href="/{$slack}">{$game_name}</a><span class="navigation-pipe"></span>{l s='Timeline' mod='genzo_krona'}{/capture}

{include file="./nav.tpl"}

<div class="krona-box">
    <h1>{l s='Your Timeline' mod='genzo_krona'}</h1>
    <div style="padding-left: 20px;">
        {foreach from=$history item=his}
            <div class="timeline-item {if $his.points_change > 0}green{else}red{/if}" points-is="{$his.points_change|replace:"-":""}">
                <h3>{$his.title}</h3>
                <p>{$his.date_add|date_format:"%d. %b"}: {$his.message} {if $his.url}<a href="{$his.url}"><i class="fa fa-link"></i></a>{/if}</p>
            </div>
        {/foreach}
    </div>
    {include file="./pagination.tpl"}
</div>