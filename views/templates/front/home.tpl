{capture name=path}{$game_name}{/capture}

<h1>{$game_name}</h1>

{if $nav}
    {include file="./nav.tpl"}
{/if}

<div id="krona-home">
    {$description}
</div>