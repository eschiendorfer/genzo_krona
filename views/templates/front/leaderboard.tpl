{capture name=path}<a href="/{$slack}">{$game_name}</a><span class="navigation-pipe"></span>{l s='Leaderboard' mod='genzo_krona'}{/capture}

<h1>{l s='Leaderboard' mod='genzo_krona'}</h1>

{foreach from=$players item=player}
    {$player.id_customer} - {$player.pseudonym} - {$player.points}<br>
{/foreach}