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
        !$module->registerHook('actionRegisterGenzoCrmEmail') OR
        !$module->executeSqlScript('install-2.0.0-after') OR
        !$module->uninstallAdminMenus() OR
        !$module->installAdminMenus()
    ) {
        return false;
    }

    return true;
}

function convertPlayerHistoryColumn($done = 0) {

    $limit = 1000;

    $query = new DbQuery();
    $query->select('*');
    $query->from('genzo_krona_player_history');
    $query->limit($limit, $done);
    $histories = Db::getInstance()->ExecuteS($query);

    if (empty($histories)) {
        return true;
    }
    else {

        foreach ($histories as &$history) {
            $history['viewed'] = 1;
            if ($id_customer = $history['id_customer']) {
                //$historyObj = new \KronaModule\PlayerHistory($history['id_history']);
                if ($history['id_action']) {
                    $history['points'] = $history['change'];
                } elseif ($history['id_action_order']) {
                    $history['coins'] = $history['change'];
                } else {
                    // If merchant used a custom playerHistory
                    $total_mode_gamification = \Configuration::get('krona_gamification_total');

                    if ($total_mode_gamification == 'points_coins' || $total_mode_gamification == 'coins') {
                        $history['coins'] = $history['change'];
                    } elseif ($total_mode_gamification == 'points') {
                        $history['points'] = $history['change'];
                    }

                }
            }
        }

        // DB::getInstance()->execute('TRUNCATE TABLE '._DB_PREFIX_.'genzo_krona_player_history');

        DB::getInstance()->insert('genzo_krona_player_history', $histories, true, true, 3);

        return convertPlayerHistoryColumn($done+$limit);
    }

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