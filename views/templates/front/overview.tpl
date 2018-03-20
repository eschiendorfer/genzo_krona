{capture name=path}
    <a href="{$link->getPageLink('my-account')}">{l s='Account' mod='genzo_krona'}</a><span class="navigation-pipe"></span>
    <a href="{$link->getModuleLink('genzo_krona', 'home')}">{$game_name}</a><span class="navigation-pipe"></span>{l s='Overview' mod='genzo_krona'}
{/capture}

<h1>{$game_name}</h1>

{include file="./nav.tpl"}
<div id="overview">
    {if $loyalty}
        <div id="loyalty" class="krona-box" {if !$gamification}style="width: 100%"{/if}>
            <ul class="overview" >
                <li>
                    <b>{l s='Loyalty Points' mod='genzo_krona'}</b><br>
                    {$player.loyalty}
                </li>
            </ul>
        </div>
    {/if}
    {if $gamification}
        <div id="gamification" class="krona-box">
            <ul class="overview">
                <li>
                    <b>{l s='Display Name' mod='genzo_krona'}</b><br>
                    {$player.display_name}
                </li>
                <li>
                    <b>{$total_name}</b><br>
                    {$player.total}
                </li>
                <li>
                    <b>{l s='Rank' mod='genzo_krona'}</b><br>
                    <a style="text-decoration: underline;" href="{$link->getModuleLink('genzo_krona', 'leaderboard')}">{$rank}</a>
                </li>
                <li>
                    <b>{l s='Last Level' mod='genzo_krona'}</b><br>
                    {if $level}{$level->name}{else}-{/if}
                </li>
                <li class="avatar">
                    <img src="{$player.avatar_full}" style="float: right;">
                </li>
            </ul>
        </div>
    {/if}
    <div style="clear: both;"></div>
</div>

<div class="krona-box">
    <h2>{l s='Your last 5 actions' mod='genzo_krona'}</h2>

    <div style="padding-left: 20px;">
        {foreach from=$history item=his}
            <div class="timeline-item {if $his.change > 0}green{else}red{/if}" points-is="{$his.change|replace:"-":""}">
                <h3>{$his.title}</h3>
                <p>{$his.date_add|date_format:"%d. %b"}: {$his.message} {if $his.url}<a href="{$his.url}"><i class="fa fa-link"></i></a>{/if}</p>
            </div>
        {/foreach}
    </div>
</div>

{include file="./footer.tpl"}