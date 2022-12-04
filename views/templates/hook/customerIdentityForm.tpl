{* Todo: This file should be edited, if we want to make this feature make available for publicity *}

<div class="form-row">
	<div class="krona-label"><label for="active">{l s='Using' mod='genzo_krona'} {$game_name}</label></div>
	<input type="radio" id="yes" name="active" value="1" {if $player.active==1}checked{/if}>
	<label for="yes">{l s='Yes' mod='genzo_krona'}</label>
	<input style="margin-left: 10px;" type="radio" id="no" name="active" value="0" {if $player.active==0}checked{/if}>
	<label for="no"> {l s='No' mod='genzo_krona'}</label>
</div>
{if $gamification && $pseudonym}
	<div class="form-row">
		<div class="krona-label"><label for="pseudonym">{l s='Pseudonym' mod='genzo_krona'}</label></div>
		<input type="text" class="form-control" id="pseudonym" name="pseudonym" value="{$player.pseudonym}">
	</div>
{/if}
{if $gamification && $avatar}
	<div class="form-row">
		<div class="krona-label"><label for="avatar">{l s='Avatar' mod='genzo_krona'}</label></div>
		<div id="avatar-upload" style="float: left;">
			<input id="avatar" name="avatar" type="file" >
		</div>
		{if $gamification && $avatar}
			<img style="float:right;" id="avatar" src="{$player.avatar_full}">
		{/if}
	</div>
	<div style="clear: both;"></div>
{/if}
