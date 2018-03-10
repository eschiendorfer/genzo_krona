{capture name=path}<a href="{$link->getModuleLink('genzo_krona', 'home')}">{$game_name}</a><span class="navigation-pipe"></span>{l s='Levels' mod='genzo_krona'}{/capture}

<h1>{l s='Your achieved levels' mod='genzo_krona'}</h1>

{include file="./nav.tpl"}

<div id="levels">
    {foreach from=$levels item=level}
        <div class="level krona-box">
            <div class="icon"><img src="{$modules_dir}genzo_krona/views/img/icon/{$level.icon}"></div>
            <h3>{$level.name}</h3>
            <div style="clear:both;"></div>
            <div class="level-row">
                <div class="headline">
                    {if $level.condition_type=='points_coins'}
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
            <div class="level-row">
                <div class="headline">{l s='Achieved at' mod='genzo_krona'}</div>
                <div>{$level.achieved_last|date_format:"%e. %B %Y"}</div>
            </div>
            <div class="level-row">
                <div class="headline">{l s='Active until' mod='genzo_krona'}</div>
                <div>{if $level.active_until > 1}{$level.active_until|date_format:"%e. %B %Y"}{else}{l s='Unlimited' mod='genzo_krona'}{/if}</div>
            </div>
        </div>
    {/foreach}
</div>

{include file="./footer.tpl"}
