{capture name=path}{$game_name}{/capture}

<h1>{$game_name}</h1>

{if $banned}
    <p class="alert alert-danger">{l s='Sorry, but your account has been banned!' mod='genzo_krona'}</p>
{else}
    {if $nav}
        {include file="./nav.tpl"}
    {/if}
    <div id="krona-home">
        {$description}
    </div>
{/if}
