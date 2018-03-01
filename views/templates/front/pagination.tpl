{if $pages > 1}
    {strip}
        <ul id="krona-pagination">
            <li>
                {if $page>1}<a href="?page={$page-1}">{l s='Previous' mod='genzo_krona'}</a>{else}{l s='Previous' mod='genzo_krona'}{/if}
            </li>
            {for $foo=1 to $pages}
                <li>
                    {if $foo!=$page}<a href="?page={$foo}">{$foo}</a>{else}<b>{$foo}</b>{/if}
                </li>
            {/for}
            <li>
                {if $page<$pages}<a href="?page={$page+1}">{l s='Next' mod='genzo_krona'}</a>{else}{l s='Next' mod='genzo_krona'}{/if}
            </li>
        </ul>
    {/strip}
    <div style="clear: both;"> </div>
{/if}