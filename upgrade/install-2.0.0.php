<?php

/**
 * @var genzo_krona $module
 * @return bool
 * @throws PrestaShopException
 */
function upgrade_module_2_0_0($module) {

    if (!$module->executeSqlScript('install-2.0.0') OR
        !convertPlayerHistoryColumn() OR
        !saveDefaultConfiguration() OR
        !$module->executeSqlScript('install-2.0.0-after') OR
        !$module->uninstallAdminMenus() OR
        !$module->installAdminMenus()
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
            $historyObj->points = $history['change'];
        }
        elseif ($historyObj->id_action_order) {
            $historyObj->coins = $history['change'];
        }
        else {
            // If merchant used a custom playerHistory
            $customer = new Customer($history['id_customer']);
            $total_mode_gamification = \Configuration::get('krona_gamification_total', null, $customer->id_shop_group, $customer->id_shop);

            if ($total_mode_gamification == 'points_coins' || $total_mode_gamification == 'coins') {
                $historyObj->coins = $history['change'];
            }
            elseif ($total_mode_gamification == 'points') {
                $historyObj->points = $history['change'];
            }

        }
        $historyObj->update();
    }

    DB::getInstance()->update('genzo_krona_player_history', ['viewed' => 1]);

    return true;
}

function saveDefaultConfiguration() {
    $ids_shop = Shop::getCompleteListOfShopsID();

    foreach ($ids_shop as $id_shop) {
        $id_shop_group = Shop::getGroupFromShop($id_shop);
        Configuration::updateValue('krona_loyalty_expire_method', 'none', false, $id_shop_group, $id_shop);
        Configuration::updateValue('krona_loyalty_expire_days', 365, false, $id_shop_group, $id_shop);
    }

    return true;
}