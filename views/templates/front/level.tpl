<div class="level {if $grid}grid{else}list{/if} krona-box">
    <div class="icon">
        {if $grid}
            <img src="{$base_dir}upload/genzo_krona/img/icon/{$level.icon}_small.png">
        {else}
            <img src="{$base_dir}upload/genzo_krona/img/icon/{$level.icon}_big.png">
        {/if}
    </div>

    <h3>{$level.name}</h3>

    {if $grid}<div style="clear:both;"></div>{/if}

    <div class="level-row">
        <div class="headline">
            {if $level.condition_type=='total'}
                {$total_name}
            {elseif $level.condition_type=='coins'}
                {$total_name} {l s='by orders' mod='genzo_krona'}
            {elseif $level.condition_type=='points'}
                {$total_name} {l s='by actions' mod='genzo_krona'}
            {elseif $level.condition_type=='order'}
                {$total_name} {l s='by orders' mod='genzo_krona'}
            {else}
                {l s='Action executed' mod='genzo_krona'}
            {/if}
        </div>
        <div>
             {$level.condition} {if $level.condition_type=='action'}{l s='Times' mod='genzo_krona'}{/if}
        </div>
    </div>
    {if !$next}
        <div class="level-row">
            <div class="headline">{l s='Achieved at' mod='genzo_krona'}</div>
            <div>{$level.achieved_last|date_format:"%e. %B %Y"}</div>
        </div>
    {/if}
    {if !$next}
        <div class="level-row">
            <div class="headline">{l s='Active until' mod='genzo_krona'}</div>
            <div>{if $level.active_until > 1}{$level.active_until|date_format:"%e. %B %Y"}{else}{l s='Unlimited' mod='genzo_krona'}{/if}</div>
        </div>
    {/if}
</div>