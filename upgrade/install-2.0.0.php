<?php

/**
 * @var genzo_krona $module
 * @return bool
 * @throws PrestaShopException
 */
function upgrade_module_2_0_0($module) {

    if (!$module->executeSqlScript('install-2.0.0') OR
        !convertPlayerHistoryColumn() OR
        !revertOldCouponConversion($module) OR
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
    $query->select('pl.*, c.id_shop, c.id_shop_group');
    $query->from('genzo_krona_player_history', 'pl');
    $query->innerJoin('customer', 'c', 'pl.id_customer=c.id_customer');
    $query->limit($limit, $done);
    $histories = Db::getInstance()->ExecuteS($query);

    if (empty($histories)) {
        return true;
    }
    else {

        foreach ($histories as $key => &$history) {
            $history['viewed'] = 1;
            if ($id_customer = $history['id_customer']) {
                if ($history['id_action']) {
                    $history['points'] = $history['change'];
                } elseif ($history['id_action_order']) {
                    $history['coins'] = $history['change'];
                } else {
                    // If merchant used a custom playerHistory
                    $total_mode_gamification = Configuration::get('krona_gamification_total', null, $history['id_shop_group'], $history['id_shop']);

                    if ($total_mode_gamification == 'points_coins' || $total_mode_gamification == 'coins') {
                        $history['coins'] = $history['change'];
                    } elseif ($total_mode_gamification == 'points') {
                        $history['points'] = $history['change'];
                    }

                }
                if ($history['loyalty']==0 && Configuration::get('krona_loyalty_active', null, $history['id_shop_group'], $history['id_shop'])) {
                    $total_mode_loyalty = Configuration::get('krona_loyalty_total', null, $history['id_shop_group'], $history['id_shop']);
                    if ($total_mode_loyalty == 'points_coins') {
                        $history['loyalty'] = $history['points'] + $history['coins'];
                    }
                    elseif ($total_mode_loyalty == 'points') {
                        $history['loyalty'] = $history['points'];
                    }
                    elseif ($total_mode_loyalty == 'coins') {
                        $history['loyalty'] = $history['coins'];
                    }
                }
                unset($histories[$key]['id_shop']);
                unset($histories[$key]['id_shop_group']);
            }
        }

        DB::getInstance()->insert('genzo_krona_player_history', $histories, true, true, 3);

        return convertPlayerHistoryColumn($done+$limit);
    }

}

/* @param $module Genzo_Krona */
function revertOldCouponConversion($module) {

    // Transform the coupon conversion into new history way
    $query = new DbQuery();
    $query->select('id_history');
    $query->from('genzo_krona_player_history');
    $query->where('loyalty < 0');
    $old_conversions = Db::getInstance()->ExecuteS($query);

    foreach ($old_conversions as $old_conversion) {
        $playerHistory = new \KronaModule\PlayerHistory($old_conversion['id_history']);
        $module->convertLoyaltyPointsToCoupon($playerHistory->id_customer, abs($playerHistory->loyalty), true);
        $playerHistory->loyalty = 0;
        $playerHistory->update();
    }

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