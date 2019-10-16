<?php

/**
 * @var genzo_krona $module
 * @return bool
 * @throws PrestaShopException
 */
function upgrade_module_2_0_0($module) {

    if (!$module->executeSqlScript('install-2.0.0') OR
        !saveDefaultConfiguration() OR
        !convertPlayerHistoryColumn() OR
        !reconstructIdOrders() OR
        !revertOldCouponConversion($module) OR
        !cleanPlayers() OR
        !$module->moveImageFiles() OR
        !$module->registerHook('displayCustomerAccountForm') OR
        !$module->registerHook('actionRegisterGenzoCrmEmail') OR
        !$module->registerHook('actionOrderEdited') OR
        !$module->registerHook('actionObjectCustomerDeleteAfter') OR
        !$module->registerHook('actionOrderStatusPostUpdate') OR
        !$module->unregisterHook('actionOrderStatusUpdate') OR
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
        $playerHistory->force_display = $playerHistory->loyalty;
        $playerHistory->loyalty = 0;
        $playerHistory->update();
    }

    return true;
}

function reconstructIdOrders() {

    // Get all histories created by orders
    $query = new DbQuery();
    $query->select('*');
    $query->from('genzo_krona_player_history');
    $query->where('coins != 0');
    $histories = Db::getInstance()->ExecuteS($query);

    foreach ($histories as $key => &$history) {

        $query = new DbQuery();
        $query->select('o.id_order');
        $query->from('orders', 'o');
        $query->innerJoin('order_history', 'oh', 'oh.id_order=o.id_order');
        $query->where('id_customer = ' . $history['id_customer']);
        $date_start = date("Y-m-d H:i:s", (strtotime($history['date_add'])-30));
        $date_end = date("Y-m-d H:i:s", (strtotime($history['date_add'])+30));
        $query->where("oh.date_add BETWEEN '{$date_start}' AND '{$date_end}' OR o.date_add BETWEEN '{$date_start}' AND '{$date_end}'");
        $id_order = (int)Db::getInstance()->getValue($query);
        if ($id_order) {
            $history['id_order'] = $id_order ?: 1;
        }
        else {
            $playerHistory = new \KronaModule\PlayerHistory($history['id_history']);
            $playerHistory->delete();
            unset($histories[$key]);
        }
    }

    DB::getInstance()->insert('genzo_krona_player_history', $histories, true, true, 3);

    return true;

}

function saveDefaultConfiguration() {
    $ids_shop = Shop::getCompleteListOfShopsID();

    foreach (Language::getIDs() as $id_lang) {
        $referral_title_referrer[$id_lang] = 'New referral order'; // Just as an example
        $referral_text_referrer[$id_lang] = 'Your friend {buyer_name} placed an order, which brought you {coins} loyalty points. Note, that they will expire on {loyalty_expire_date}.'; // Just as an example
        $loyalty_expire_title[$id_lang] = 'Loyalty Points expired'; // Just as an example
        $loyalty_expire_message[$id_lang] = 'Unfortunately today expired {loyalty_points} of your loyalty points.'; // Just as an example
    }

    foreach ($ids_shop as $id_shop) {
        $id_shop_group = Shop::getGroupFromShop($id_shop);
        Configuration::updateValue('krona_loyalty_expire_method', 'none', false, $id_shop_group, $id_shop);
        Configuration::updateValue('krona_loyalty_expire_days', 365, false, $id_shop_group, $id_shop);
        Configuration::updateValue('krona_referral_active', 1, false, $id_shop_group, $id_shop);
        Configuration::updateValue('krona_referral_order_nbr', 1, false, $id_shop_group, $id_shop);
        Configuration::updateValue('krona_referral_title_referrer', $referral_title_referrer, false, $id_shop_group, $id_shop);
        Configuration::updateValue('krona_referral_text_referrer', $referral_text_referrer, false, $id_shop_group, $id_shop);
        Configuration::updateValue('krona_loyalty_expire_title', $loyalty_expire_title, false, $id_shop_group, $id_shop);
        Configuration::updateValue('krona_loyalty_expire_message', $loyalty_expire_message, false, $id_shop_group, $id_shop);
    }

    return true;
}

function cleanPlayers() {

    $query = new \DbQuery();
    $query->select('*');
    $query->from('genzo_krona_player');
    $players = \Db::getInstance()->ExecuteS($query);

    foreach ($players as $key => &$player) {
        if(!Customer::customerIdExistsStatic($player['id_customer'])) {
            $playerObj = new \KronaModule\Player($player['id_customer']);
            $playerObj->delete();
            unset($players[$key]);
        }
        else {
            $player['referral_code'] = \KronaModule\Player::generateReferralCode();
        }
    }

    DB::getInstance()->insert('genzo_krona_player', $players, true, true, 3);

    return true;
}