{if isset($confirmation)}
    <p class='alert alert-success'>{$confirmation}</p>
{/if}
{if !empty($errors)}
    <p class='alert alert-danger'>
        {foreach from=$errors item=error}
            {$error}<br>
        {/foreach}
    </p>
{/if}