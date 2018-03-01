{capture name=path}<a href="/{$slack}">{$game_name}</a><span class="navigation-pipe"></span>{l s='Overview' mod='genzo_krona'}{/capture}

<h1>{$game_name}</h1>

{include file="./nav.tpl"}

<div class="krona-box">
    <ul id="overview">
        <li>
            <b>{l s='Display Name' mod='genzo_krona'}</b><br>
            {$player.pseudonym}
        </li>
        <li>
            <b>{$points_name}</b><br>
            {$player.points}
        </li>
        <li>
            <b>{l s='Rank' mod='genzo_krona'}</b><br>
            {$rank}
        </li>
        <li>
            <b>{l s='Last Level' mod='genzo_krona'}</b><br>
            {if !empty($level)}{$level.0.name}{else}-{/if}
        </li>
        <li>
            <img src="{$avatar}">
        </li>
    </ul>
</div>

<div class="krona-box">
    <h2>{l s='Your last 5 actions' mod='genzo_krona'}</h2>
    <div style="padding-left: 20px;">
        {foreach from=$history item=his}
            <div class="timeline-item {if $his.points_change > 0}green{else}red{/if}" points-is="{$his.points_change|replace:"-":""}">
                <h3>{$his.title}</h3>
                <p>{$his.date_add|date_format:"%d. %b"}: {$his.message} {if $his.url}<a href="{$his.url}"><i class="fa fa-link"></i></a>{/if}</p>
            </div>
        {/foreach}
    </div>
</div>