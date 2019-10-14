<fieldset class="account_creation">
	<div class="form-group">
		<label for="referral_code">{l s='Referral Code' mod='genzo_krona'}</label>
		<input type="text" class="form-control" id="referral_code" name="referral_code" value="{if isset($smarty.post.referral_code)}{$smarty.post.referral_code|escape:'html':'UTF-8'}{/if}" />
	</div>
</fieldset>