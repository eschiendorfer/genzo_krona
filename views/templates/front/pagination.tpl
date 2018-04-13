{if $pages > 1}
    {strip}
        <ul id="krona-pagination">
            <li>
                 {if $page>1}<a href="?page={$page-1}">{l s='Previous' mod='genzo_krona'}</a>{else}{l s='Previous' mod='genzo_krona'}{/if}
            </li>

            {if $pages >= 4 AND $page >= 4}
                <li><a href="?page=1">1</a></li>
                {if $page > 4}
                    <li>...</li>
                {/if}
            {/if}

            {for $foo=1 to $pages}
                {if $foo >= $page-2 AND $foo <= $page+2}
                <li>
                    {if $foo!=$page}<a href="?page={$foo}">{$foo}</a>{else}<b>{$foo}</b>{/if}
                </li>
                {/if}
            {/for}

            {if $pages >= 4 AND $page < $pages-2}
                {if $page < $pages-3}
                    <li>...</li>
                {/if}
                <li><a href="?page={$pages}">{$pages}</a></li>
            {/if}

            <li>
                {if $page<$pages}<a href="?page={$page+1}">{l s='Next' mod='genzo_krona'}</a>{else}{l s='Next' mod='genzo_krona'}{/if}
            </li>
        </ul>
    {/strip}
    <div style="clear: both;"> </div>
{/if}