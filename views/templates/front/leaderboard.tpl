{capture name=path}
    <a href="{$link->getPageLink('my-account')}">{l s='My Account' mod='genzo_krona'}</a><span class="navigation-pipe"></span>
    <a href="{$link->getModuleLink('genzo_krona', 'home')}">{$game_name}</a><span class="navigation-pipe"></span>{l s='Leaderboard' mod='genzo_krona'}
{/capture}

{include file="./nav.tpl"}

{include file="./error.tpl"}

<div class="krona-box">
    <h1>{l s='Leaderboard' mod='genzo_krona'}{if $title} - {$title}{/if}</h1>
    <div id="leaderboard">
        {foreach from=$players item=player}
            <div class="player">
                <img src="{$base_dir}upload/genzo_krona/img/avatar/{$player.avatar}">
                <h3>{$player.display_name}</h3>
                <div>{$total_name}: {$player.total}</div>
                <div style="clear: both;"></div>
            </div>

        {/foreach}
    </div>
    {include file="./pagination.tpl"}
</div>

{include file="./footer.tpl"}

{* Todo: When you look at the overview, you have a Rank, if you click on it it takes you to the top of the leaderboard. It would be nice if it were take you to yourself, in the list.  So you click on it, and maybe it takes you to page 23, which you are listed on.  Then you can easily see who is around you on the rank list.  Also, it would be nice if you could search for players in the rank list*}