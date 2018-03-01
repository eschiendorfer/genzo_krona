<h3>Collect Crowns</h3>
<ul class="action-boxes">
    {foreach from=$actions item=action}
    	<li>
            <h3>{$action.type}&nbsp;&nbsp;<span class="plus">{$action.points_change}</span></h3>
            
            {if $action.type == "Newsletter"}
            	<p>{l s='Subscribe to our newsletter and receive %1$d crowns.' sprintf=$action.points_change mod='genzo_krona'}</p>
                <form method="post">
                    <button class="button-standard" type="submit" name="submitNewsletter">Newsletter registrieren</button>
                </form>
            {/if}
           	{if $action.type == "Avatar"}
            	<p>{l s='Upload an avatar and receive %1$d crowns.' sprintf=$action.points_change mod='genzo_krona'}</p>
                <a id="avatar" class="button-standard">{l s='Add an avatar' mod='genzo_krona'}</a>
                {literal}
					<script type="text/javascript">
                        $( "#avatar" ).click(function() {
                            $("input[name='image']").click();
                        });
                    </script>
                {/literal}
            {/if}
        
    	</li>
    {/foreach}
</ul>