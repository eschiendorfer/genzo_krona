<?php

/**
 * @var genzo_krona $module
 * @return bool
 * @throws PrestaShopException
 */
function upgrade_module_2_0_0($module) {

    if (!$module->executeSqlScript('install-2.0.0') OR
        !convertPlayerHistoryColumn() OR
        !$module->executeSqlScript('install-2.0.0-after')
        ) {
        return false;
    }

    return true;
}

function convertPlayerHistoryColumn() {

    $query = new DbQuery();
    $query->select('*');
    $query->from('genzo_krona_player_history');
    $histories = Db::getInstance()->ExecuteS($query);

    foreach ($histories as $history) {
        $historyObj = new \KronaModule\PlayerHistory($history['id_history']);
        if ($historyObj->id_action) {
            $historyObj->change_points = $historyObj->change;
        }
        elseif ($historyObj->id_action_order) {
            $historyObj->change_coins = $historyObj->change;
        }
        $historyObj->update();
    }

    return true;
}