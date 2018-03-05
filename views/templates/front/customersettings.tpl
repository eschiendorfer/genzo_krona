{capture name=path}<a href="{$link->getModuleLink('genzo_krona', 'home')}">{$game_name}</a><span class="navigation-pipe"></span>{l s='Settings' mod='genzo_krona'}{/capture}

{include file="./nav.tpl"}

{include file="./error.tpl"}

<div class="krona-box">
    <h1>{l s='Your Settings' mod='genzo_krona'}</h1>
    <form method="post" enctype="multipart/form-data">

        <div class="form-row">
            <img id="avatar" src="{$avatar}">
            <div class="label"><label for="active">{l s='Using' mod='genzo_krona'} {$game_name}</label></div>
            <input type="radio" id="yes" name="active" value="1" {if $player.active==1}checked{/if}>
            <label for="yes">{l s='Yes' mod='genzo_krona'}</label>
            <input style="margin-left: 10px;" type="radio" id="no" name="active" value="0" {if $player.active==0}checked{/if}>
            <label for="no"> {l s='No' mod='genzo_krona'}</label>
        </div>
        <div class="form-row">
            <div class="label"><label for="pseudonym">{l s='Pseudonym' mod='genzo_krona'}</label></div>
            <input type="text" class="form-control" id="pseudonym" name="pseudonym" value="{$player.pseudonym}">
        </div>
        <div class="form-row">
            <div class="label"><label for="avatar-fake">{l s='Avatar' mod='genzo_krona'}</label></div>
            <div id="avatar-upload">
                <input type="text" id="avatar-fake" value="">
                <span id="avatar-button">{l s='Select Avatar' mod='genzo_krona'}</span>
                <input id="avatar-input" name="avatar" type="file" >
            </div>
        </div>

        <button type="submit" name="saveCustomerSettings" class="krona-button">{l s='Save' mod='genzo_krona'}</button>
    </form>
</div>