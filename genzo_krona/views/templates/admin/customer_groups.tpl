<div class="panel">
    <div class="panel-heading">{l s='CronJob' mod='genzo_krona'}</div>
    <p>{$cronJob}</p>
</div>

<div class="bootstrap panel">
    <form method="post">
        <div class="panel-heading">{l s='Customer Group Priority' mod='genzo_krona'}</div>
        <p class="alert alert-info">{l s='What to do, if a customer reaches multiple levels related to customer groups? The module will only change the default customer group, if the new customer group is better.' mod='genzo_krona'}</p>
        <table id="sortable" class="table" border="0">
            <thead>
            <tr>
                <th scope="col" width="120">Priority</th>
                <th scope="col">Name</th>
            </tr>
            </thead>
            <tbody>
            {foreach from=$groups item=group}
                <tr>
                    <td class="pointer dragHandle">
                        <div class="dragGroup">
                            <div class="positions">
                                <input name="position_{$group.id_group}" value="{counter}">
                            </div>
                        </div>
                    </td>
                    <td>{$group.name}</td>
                </tr>
            {/foreach}
            </tbody>
        </table>
        <div class="panel-footer">
            <button type="submit" value="1" id="configuration_form_submit_btn" name="saveGroupsPriority" class="btn btn-default pull-right">
                <i class="process-icon-save"></i> {l s='Save Group Priority' mod='genzo_krona'}
            </button>
        </div>
    </form>
</div>