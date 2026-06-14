<div>
    {if !empty($krona_overview_url)}
        <a class="group flex items-center gap-2 py-1 text-gray-800 hover:text-accent no-underline" href="{$krona_overview_url}">
            <i class="icon icon-krona h-5 w-5 bg-gray-500 group-hover:bg-accent"></i>
            <span>{$game_name}</span>
        </a>
    {else}
        <span class="group flex items-center gap-2 py-1 text-gray-800 no-underline">
            <i class="icon icon-krona h-5 w-5 bg-gray-500"></i>
            <span>{$game_name}</span>
        </span>
    {/if}
</div>
