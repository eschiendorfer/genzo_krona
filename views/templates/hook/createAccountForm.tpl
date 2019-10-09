<fieldset class="account_creation">
	<h3>{l s='Referral Program' mod='genzo_krona'}</h3>
	<p class="text">
		<label for="referral_code">{l s='Referral Code' mod='genzo_krona'}</label>
		<input type="text" size="52" maxlength="128" id="referral_code" name="referral_code" value="{if isset($smarty.post.referral_code)}{$smarty.post.referral_code|escape:'html':'UTF-8'}{/if}" />
	</p>
</fieldset>
