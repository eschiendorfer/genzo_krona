<?php

/**
 * Copyright (C) 2018 Emanuel Schiendorfer
 *
 * @author    Emanuel Schiendorfer <https://github.com/eschiendorfer>
 * @copyright 2018 Emanuel Schiendorfer
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

namespace KronaModule;

require_once _PS_MODULE_DIR_ . 'genzo_krona/autoload.php';

class Player extends \ObjectModel {

    public $id_customer;
    public $pseudonym;
    public $display_name;
    public $avatar;
    public $avatar_full;
    public $active;
    public $banned;
    public $notification;
    public $date_add;
    public $date_upd;

    // Dynamic values
    public $points;
    public $coins;
    public $total;
    public $loyalty;

    /* @var $customer \Customer */
    public $customer;

    public static $definition = array(
        'table' => "genzo_krona_player",
        'primary' => 'id_customer',
        'multilang' => false,
        'fields' => array(
            'id_customer'       => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'pseudonym'         => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
            'avatar'            => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
            'points'            => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'coins'             => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'loyalty'           => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'loyalty_expire'    => array('type' => self::TYPE_DATE),
            'active'            => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'banned'            => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'notification'      => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'date_add'          => array('type' => self::TYPE_DATE, 'validate' =>'isDateFormat'),
            'date_upd'          => array('type' => self::TYPE_DATE, 'validate' =>'isDateFormat'),
        )
    );

    public function __construct($id_customer = null, $customerObj = true) {

        parent::__construct($id_customer);

        if ($id_customer) {

            // Check if customer row exits
            if (!$this->id_customer && \Customer::customerIdExistsStatic($id_customer)) {

                if (!$customerObj instanceof \Customer) {
                    $context = \Context::getContext();
                    $customerObj = ($context->customer instanceof \Customer) ? $context->customer : new \Customer($id_customer);
                }

                $this->id_customer = $id_customer;
                $this->avatar = 'no-avatar.jpg';
                $this->active = (int)\Configuration::get('krona_customer_active', null, $customerObj->id_shop_group, $customerObj->id_shop);
                $this->add();
            }

            // Sometimes we don't want the customerObj at all, then we construct with false, if we have we put it in, if we want to generate it we use true
            if ($customerObj === true || $customerObj instanceof \Customer) {

                if (!$customerObj instanceof \Customer) {
                    $customerObj = new \Customer($id_customer);
                }

                $this->customer = $customerObj;
            }

            // Todo: calculate points, coins, total and loyalty
            if (\Configuration::get('krona_gamification_active', null, $this->customer->id_shop_group, $this->customer->id_shop)) {

                $total_mode_gamification = \Configuration::get('krona_gamification_total', null, $this->customer->id_shop_group, $this->customer->id_shop);

                if ($total_mode_gamification == 'points_coins') {
                    $this->total = $this->points + $this->coins;
                }
                elseif ($total_mode_gamification == 'points') {
                    $this->total = $this->points;
                }
                elseif ($total_mode_gamification == 'coins') {
                    $this->total = $this->coins;
                }

                if (\Configuration::get('krona_pseudonym', null, $this->customer->id_shop_group, $this->customer->id_shop) && $this->pseudonym) {
                    $this->display_name = $this->pseudonym;
                }
                else {
                    $this->display_name = self::getDisplayName($this->id_customer);
                }

                if (\Configuration::get('krona_avatar', null, $this->customer->id_shop_group, $this->customer->id_shop)) {
                    $this->avatar_full = _MODULE_DIR_ . 'genzo_krona/views/img/avatar/' . $this->avatar . '?=' . strtotime($this->date_upd);
                }

            } else {
                $this->total = 0;
            }
        }
    }

    public function delete() {

        parent::delete();

        $histories = PlayerHistory::getHistoryByPlayer($this->id_customer);

        foreach ($histories as $history) {
            $playerHistory = new PlayerHistory($history['id_history'], false);
            $playerHistory->delete();
        }

        \Db::getInstance()->delete('genzo_krona_player_level', 'id_customer='.$this->id_customer);
    }

    public static function getAllPlayers($filters = null, $pagination = null, $order = null) {

        $context = \Context::getContext();

        // Multistore Handling
        (\Shop::isFeatureActive()) ? $ids_shop = \Shop::getContextListShopID() : $ids_shop = null;

        // Gamification Total
        if (\Configuration::get('krona_gamification_active', null, $context->shop->id_shop_group, $context->shop->id)) {
            $total = \Configuration::get('krona_gamification_total', null, $context->shop->id_shop_group, $context->shop->id);
        }
        else {
            $total = null;
        }

        $query = new \DbQuery();
        $query->select('p.*');
        if ($ids_shop) {
            $query->select('c.id_shop');
        }
        if ($total == 'points_coins') {
            $query->select('p.points+p.coins AS total');
        }
        elseif ($total == 'points') {
            $query->select('p.points AS total');
        }
        elseif ($total == 'coins') {
            $query->select('p.coins AS total');
        }

        $query->from(self::$definition['table'], 'p');
        if ($ids_shop) {
            $query->select('CONCAT(c.id_customer, ": ", c.firstname," ", c.lastname) AS option_name');
            $query->innerJoin('customer', 'c', 'p.id_customer = c.id_customer');
            $query->where('c.`id_shop` IN (' . implode(',', array_map('intval', $ids_shop)) . ')');
        }
        if (!empty($filters)) {
            foreach ($filters as $filter) {
                $query->where($filter);
            }
        }

        if ($pagination) {
            $limit = (int) $pagination['limit'];
            $offset = (int)$pagination['offset'];
            $query->limit($limit, $offset);
        }

        if ($order) {
            (!empty($order['alias'])) ? $alias = $order['alias'].'.' : $alias = '';
            $query->orderBy("{$alias}`{$order['order_by']}` {$order['order_way']}");
        }

        $players = \Db::getInstance()->ExecuteS($query);

        foreach ($players as &$player) {
            if (\Configuration::get('krona_pseudonym') AND $player['pseudonym']) {
                $player['display_name'] = $player['pseudonym'];
            }
            else {
                $player['display_name'] = self::getDisplayName($player['id_customer']);
            };
        }

        return $players;

    }

    // Trying to get ride of this function. But probably we need to generate list in adminControllers differently.
    /*public static function getTotalPlayers($filters = null) {

        (\Shop::isFeatureActive()) ? $ids_shop = \Shop::getContextListShopID() :$ids_shop = null;

        $query = new \DbQuery();
        $query->select('Count(*)');
        $query->from(self::$definition['table'], 'p');
        if ($ids_shop) {
            $query->innerJoin('customer', 'c', 'p.id_customer = c.id_customer');
            $query->where('c.`id_shop` IN (' . implode(',', array_map('intval', $ids_shop)) . ')');
        }

        if (!empty($filters)) {
            foreach ($filters as $filter) {
                $query->where($filter);
            }
        }

        return \Db::getInstance()->getValue($query);
    }*/

    public static function importPlayer($id_customer) {

        $customer = new \Customer((int)$id_customer);
        $player = new Player($id_customer, $customer);

        $import_points = (int)\Tools::getValue('import_points');
        $import_orders = (bool)\Tools::getValue('import_orders');

        // Handling Core Loyalty Points
        if ($import_points > 0) {
            if (\Module::isInstalled('loyalty')) {

                include_once _PS_MODULE_DIR_ . 'loyalty/classes/LoyaltyModule.php';
                include_once _PS_MODULE_DIR_ . 'loyalty/classes/LoyaltyStateModule.php';

                $points = \LoyaltyModule\LoyaltyModule::getPointsByCustomer($id_customer);
                $coins_change = ceil($points * $import_points);

                $player->update(0, $coins_change);
            }
        }

        // Handling old orders
        if ($import_orders) {

            $orders = \Order::getCustomerOrders($id_customer);
            $orders = array_reverse($orders);

            if (!empty($orders)) {

                foreach ($orders as $order) {

                    if (!$order['id_order_state']) { break; }
                    $orderState = new \OrderState($order['id_order_state']);

                    if ($orderState->paid) {

                        // Check ActionOrder -> This is basically checking the currency
                        $id_action_order = ActionOrder::getIdActionOrderByCurrency($order['id_currency']);
                        $actionOrder = new ActionOrder($id_action_order);

                        // Get Total amount of the order
                        $order_amount = \Configuration::get('krona_order_amount', null, $customer->id_shop_group, $customer->id_shop);

                        if ($order_amount == 'total_wt') {
                            $total = $order['total_paid']; // Total with taxes
                        } elseif ($order_amount == 'total') {
                            $total = $order['total_paid_tax_excl'];
                        } elseif ($order_amount == 'total_products_wt') {
                            $total = $order['total_products_wt'];
                        } elseif ($order_amount == 'total_products') {
                            $total = $order['total_products'];
                        } else {
                            $total = $order['total_paid']; // Standard if nothing is set
                        }

                        // Check if coupons should be substracted (in total they are already substracted)
                        if (\Configuration::get('krona_order_coupon', null, $customer->id_shop_group, $customer->id_shop)) {
                            if ($order_amount == 'total_products_wt') {
                                $total = $total - $order['total_discounts_tax_incl'];
                            }
                            elseif ($order_amount == 'total_products') {
                                $total = $total - $order['total_discounts_tax_excl'];
                            }
                        }

                        // Check the rounding method -> near is standard
                        $order_rounding = \Configuration::get('krona_order_rounding', null, $customer->id_shop_group, $customer->id_shop);
                        if ($order_rounding == 'down') {
                            $coins_change = floor($total * $actionOrder->coins_change);
                        }
                        elseif ($order_rounding == 'up') {
                            $coins_change = ceil($total * $actionOrder->coins_change);
                        }
                        else {
                            $coins_change = round($total * $actionOrder->coins_change);
                        }

                        $link = new \Link();
                        $history = new PlayerHistory(null, $player);
                        $history->id_customer = $id_customer;
                        $history->id_action_order = $id_action_order;
                        $history->url = $link->getPageLink('history');
                        $history->change_coins = $coins_change;
                        $history->date_add = $order['date_add'];

                        // Handling lang fields for Player History
                        $ids_lang = \Language::getIDs();
                        $title = array();
                        $message = array();

                        foreach ($ids_lang as $id_lang) {

                            $title[$id_lang] = \Configuration::get('krona_order_title', $id_lang, $customer->id_shop_group, $customer->id_shop);
                            $message[$id_lang] = \Configuration::get('krona_order_message', $id_lang, $customer->id_shop_group, $customer->id_shop);

                            // Replace message variables
                            $search = array('{points}', '{reference}', '{amount}');

                            $total_currency = \Tools::displayPrice(\Tools::convertPrice($total, $order['id_currency']));

                            $replace = array($coins_change, $order['reference'], $total_currency);
                            $message[$id_lang] = str_replace($search, $replace, $message[$id_lang]);

                            $history->message[$id_lang] = pSQL($message[$id_lang]);
                            $history->title[$id_lang] = pSQL($title[$id_lang]);
                        }

                        $history->add(false);
                        $player->update(0, $coins_change);
                    }

                }
                PlayerLevel::updatePlayerLevel($player, 'coins', 0);
            }
        }

    }

    private static function shortenWord($string) {
        $words = explode(" ", $string);
        $acronym = "";

        foreach ($words as $w) {
            if (!empty($w[0])) {
                $acronym .= $w[0] . '. ';
            }
        }

        return $acronym;
    }



    // expire type can be today or last_order
    public function getExpireLoyalty($expire_type = 'last_order') {

        $expire_days = Configuration::get('krona_loyalty_expire', null, $this->customer->id_shop_group, $this->customer->id_shop);

        if ($expire_type == 'today') {
            $expire_date = date("Y-m-d 23:59:59", strtotime(" + {$expire_days} days"));
        }
        elseif ($expire_type == 'last_order') {

            $query = new \DbQuery();
            $query->select('MAX(date_add)');
            $query->from('orders');
            $query->where('id_customer = ' . $this->id_customer);
            $query->where('valid = 1');
            $last_order = \Db::getInstance()->getValue($query);

            $expire_date = date("Y-m-d H:i:s", strtotime($last_order." + {$expire_days} days"));
        }
        else {
            $expire_date = date("2030-01-01 23:59:59", strtotime(" + {$expire_days} days"));
        }

        return $expire_date;
    }


    public static function cronExpireLoyalty() {

        foreach (\Shop::getCompleteListOfShopsID() as $id_shop) {
            $id_shop_group = \Shop::getGroupFromShop($id_shop);
            if (\Configuration::get('krona_loyalty_expire', null, $id_shop_group, $id_shop)) {
                $sql = 'UPDATE '._DB_PREFIX_.self::$definition['table'].' AS p
                        INNER JOIN '._DB_PREFIX_.'customer AS c ON c.id_customer=p.id_customer
                        SET p.loyalty = 0
                        WHERE loyalty_expire < NOW() AND c.id_shop='.$id_shop;

                \Db::getInstance()->execute($sql);
            }
        }


    }

    public function getRank() {

        $method = \Configuration::get('krona_gamification_total', null, $this->customer->id_shop_group, $this->id_shop);

        $query = new \DbQuery();
        $query->select('COUNT(*)');
        $query->from(self::$definition['table'], 'p');
        $query->innerJoin('customer', 'c', 'c.id_customer = p.id_customer');
        $query->where('id_shop='.$this->customer->id_shop);

        if ($method == 'points_coins') {
            $query->where('points+coins > ' . $this->total);
        } elseif ($method == 'points') {
            $query->where('points > ' . $this->total);
        } elseif ($method == 'coins') {
            $query->where('coins > ' . $this->total);
        }

        return \Db::getInstance()->getValue($query)+1;
    }

    // Todo: check why this is in revws
    public static function getPseudonym($id_customer) {

        $pseudonym = '';

        $customer = new \Customer($id_customer);

        if (\Configuration::get('krona_pseudonym', null, $customer->id_shop_group, $customer->id_shop)) {
            $query = new \DbQuery();
            $query->select('pseudonym');
            $query->from(self::$definition['table']);
            $query->where('`id_customer` = ' . (int)$id_customer);
            $pseudonym = \Db::getInstance()->getValue($query);
        }

        if (!$pseudonym) {
            $pseudonym = self::getDisplayName($id_customer);
        }
        return $pseudonym;
    }

    public static function getDisplayName($id_customer) {

        $customer = new \Customer($id_customer);

        $display_name =  \Configuration::get('krona_display_name', null, $customer->id_shop_group, $customer->id_shop);


        if ($display_name == 1) {
            $pseudonym = $customer->firstname . ' ' . $customer->lastname; // John Doe
        }
        elseif ($display_name == 2) {
            $pseudonym = $customer->firstname . ' ' . self::shortenWord($customer->lastname); // John D.
        }
        elseif ($display_name == 3) {
            $pseudonym = self::shortenWord($customer->firstname) . ' ' . $customer->lastname; // J. Doe
        }
        elseif ($display_name == 4) {
            $pseudonym = self::shortenWord($customer->firstname . ' ' . $customer->lastname); // J. D.
        }
        elseif ($display_name == 5) {
            $pseudonym = $customer->firstname; // John
        }
        else {
            $pseudonym = 'No name';
        }

        return $pseudonym;

    }

    public static function getPossibleActions($id_customer) {

        $actions = Action::getAllActions(['a.active=1', 'a.points_change>0']);

        foreach ($actions as $key => $action) {
            $actionObj = new Action($action['id_action']);

            // Newsletter
            if ($actionObj->module=='genzo_krona' && $actionObj->key=='newsletter') {
                $context = \Context::getContext();
                $actions[$key]['done'] = ($context->customer->newsletter) ? true : false;
                $actions[$key]['possible'] = ($context->customer->newsletter) ? false : true;
            }
            else {
                $executed = (int)Action::getPlayerExecutionTimes($actionObj, $id_customer);
                if ($executed) {
                    $actions[$key]['done'] = true;
                }
                if (($actionObj->execution_type == 'unlimited') || ($executed < $actionObj->execution_max)) {
                    $actions[$key]['possible'] = true;
                }
            }
        }

        return $actions;

    }

    /* @param Action $action */
    public function checkIfPlayerStilCanExecuteAction($action) {

        if ($action->execution_type == 'unlimited') {
            return true;
        }

        // How many times was the action already executed for the defined time span?

        $endDate = date('Y-m-d 23:59:59');

        if ($action->execution_type == 'per_day') {
            $startDate = date('Y-m-d 00:00:00');
        }
        elseif ($action->execution_type == 'per_month') {
            $startDate = date('Y-m-01 00:00:00');
        }
        elseif ($action->execution_type == 'per_year') {
            $startDate = date('Y-01-01 00:00:00');
        }
        else {
            $startDate = null;
            $endDate = null; // This is max_per_lifetime or unlimited
        }

        $execution_times = (int)PlayerHistory::countActionByPlayer($this->id_customer, $action->id, $startDate, $endDate);

        if ($execution_times < $action->execution_max) {
            return $action->execution_max-$execution_times; // which is basically the nbr an action can still be executed
        }

        return false;
    }

}