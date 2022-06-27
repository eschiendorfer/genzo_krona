<?php

/**
 * Copyright (C) 2019 Emanuel Schiendorfer
 *
 * @author    Emanuel Schiendorfer <https://github.com/eschiendorfer>
 * @copyright 2019 Emanuel Schiendorfer
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

namespace KronaModule;

require_once _PS_MODULE_DIR_ . 'genzo_krona/autoload.php';

class Player extends \ObjectModel {

    public $id_customer;
    public $id_customer_referrer; // Who brought the customer to our store
    public $referral_code;
    public $avatar;
    public $avatar_full;
    public $active;
    public $banned;
    public $date_add;
    public $date_upd;

    // Dynamic values
    public $points;
    public $coins;
    public $total;
    public $loyalty;
    public $expire_points; // How many points will expire next?
    public $expire_date;

    public $pseudonym;
    public $display_name;
    public $firstname;
    public $lastname;

    public static $definition = array(
        'table' => "genzo_krona_player",
        'primary' => 'id_customer',
        'multilang' => false,
        'fields' => array(
            'id_customer'           => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_customer_referrer'  => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'referral_code'         => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
            'pseudonym'             => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
            'avatar'                => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
            'active'                => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'banned'                => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'date_add'              => array('type' => self::TYPE_DATE, 'validate' =>'isDateFormat'),
            'date_upd'              => array('type' => self::TYPE_DATE, 'validate' =>'isDateFormat'),
        )
    );

    public function __construct($id_customer = null) {

        parent::__construct($id_customer);

        if ($id_customer) {

            // Check if customer row exits
            if (!$this->id_customer && \Customer::customerIdExistsStatic($id_customer)) {

                $this->id_customer = $id_customer;
                $this->avatar = 'no-avatar.jpg';
                $this->active = \Configuration::get('krona_customer_active');
                $this->referral_code = self::generateReferralCode();
                $this->add();
            }

            if ($this->id_customer) {
                // calculate points, coins, total and loyalty
                $this->setDynamicValues();

                if (\Configuration::get('krona_gamification_active')) {

                    $names = self::getDisplayNames($this->id_customer);

                    $this->firstname = $names['firstname'];
                    $this->lastname = $names['lastname'];
                    $this->display_name = (\Configuration::get('krona_pseudonym') && $this->pseudonym) ? $this->pseudonym : $names['display_name'];

                    if (\Configuration::get('krona_avatar')) {
                        $this->avatar_full = '/upload/genzo_krona/img/avatar/' . $this->avatar . '?=' . strtotime($this->date_upd);
                    }

                }
            }
        }
    }

    private function setDynamicValues() {

        $query = new \DbQuery();
        $query->select('SUM(`points`) as points, SUM(`coins`) as coins, SUM(IF(loyalty>0,loyalty,0)-loyalty_used-loyalty_expired) AS loyalty'); // This IF(loyalty>0,loyalty,0) is needed for upgrade to 2.0.0 as we have old (negative) values in there
        $query->from('genzo_krona_player_history');
        $query->where('id_customer = ' . $this->id_customer);
        $player = \Db::getInstance()->getRow($query);

        $this->points = (int)$player['points'];
        $this->coins = (int)$player['coins'];
        $this->total = 0;
        $this->loyalty = 0;

        // Override total value if gamification is active
        if (\Configuration::get('krona_gamification_active')) {

            $total_mode_gamification = \Configuration::get('krona_gamification_total');

            if ($total_mode_gamification == 'points_coins') {
                $this->total = $this->points + $this->coins;
            } elseif ($total_mode_gamification == 'points') {
                $this->total = $this->points;
            } elseif ($total_mode_gamification == 'coins') {
                $this->total = $this->coins;
            }
        }

        // Override loyalty value if loyalty is active
        if (\Configuration::get('krona_loyalty_active')) {
            $this->loyalty = (int)$player['loyalty'];
        }

        // Expiring points
        $this->expire_points = 0;
        $this->expire_date = null;

        if (\Configuration::get('krona_loyalty_active') && \Configuration::get('krona_loyalty_expire_method')!='none') {
            $query = new \DbQuery();
            $query->select('SUM(loyalty-loyalty_used-loyalty_expired) AS expire_points, DATE(loyalty_expire_date) AS expire_date');
            $query->from('genzo_krona_player_history');
            $query->where('id_customer = ' . $this->id_customer);
            $query->where('(loyalty-loyalty_used-loyalty_expired) > 0 ');
            $query->groupBy('expire_date');
            $query->orderBy('expire_date');
            $expire = \Db::getInstance()->getRow($query);

            $this->expire_points = (int)$expire['expire_points'];
            $this->expire_date = $expire['expire_date'];

        }
    }

    public function add($autoDate = true, $nullValues = false) {

        $object = parent::add($autoDate, $nullValues);

        $hook = array(
            'module_name' => 'genzo_krona',
            'action_name' => 'account_creation',
            'id_customer' => $this->id_customer,
        );

        \Hook::exec('ActionExecuteKronaAction', $hook);

        return $object;
    }

    public function delete() {

        if ($this->id_customer) {
            $histories = PlayerHistory::getHistoryByPlayer($this->id_customer); // As there is _lang table, we use the foreach

            foreach ($histories as $history) {
                $playerHistory = new PlayerHistory($history['id_history']);
                $playerHistory->delete();
            }

            \Db::getInstance()->delete('genzo_krona_player_level', 'id_customer='.$this->id_customer);

            parent::delete();
        }

    }

    // Database
    public static function getAllPlayers($filters = null, $pagination = null, $order = null) {

        // Multistore Handling
        $ids_shop = (\Shop::isFeatureActive()) ? \Shop::getContextListShopID() : null;

        // Gamification Total
        if (\Configuration::get('krona_gamification_active')) {
            $total = \Configuration::get('krona_gamification_total');
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
            $query->select('SUM(ph.`points`+ph.`coins`) AS total');
        }
        elseif ($total == 'points') {
            $query->select('SUM(ph.points) AS total');
        }
        elseif ($total == 'coins') {
            $query->select('SUM(ph.coins) AS total');
        }

        $query->from(self::$definition['table'], 'p');
        $query->select('CONCAT(c.id_customer, ": ", c.firstname," ", c.lastname) AS option_name, c.firstname, c.lastname, c.newsletter');
        $query->innerJoin('customer', 'c', 'p.id_customer = c.id_customer');
        $query->innerJoin('genzo_krona_player_history', 'ph', 'p.id_customer = ph.id_customer');
        $query->groupBy('p.id_customer');

        if ($ids_shop) {
            $query->where('c.`id_shop` IN (' . implode(',', $ids_shop) . ')');
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
                $player['display_name'] = self::getDisplayNames($player['id_customer'])['display_name'];
            };
        }

        return $players;

    }

    public static function getTotalPlayers($filters = null) {

        $ids_shop = (\Shop::isFeatureActive()) ? \Shop::getContextListShopID() : null;

        $query = new \DbQuery();
        $query->select('Count(*)');
        $query->from(self::$definition['table'], 'p');
        if ($ids_shop) {
            $query->innerJoin('customer', 'c', 'p.id_customer = c.id_customer');
            $query->where('c.`id_shop` IN (' . implode(',', $ids_shop) . ')');
        }

        if (!empty($filters)) {
            foreach ($filters as $filter) {
                $query->where($filter);
            }
        }

        return \Db::getInstance()->getValue($query);
    }

    public static function getIdByReferralCode($referral_code) {
        $query = new \DbQuery();
        $query->select('id_customer');
        $query->from(self::$definition['table']);
        $query->where("referral_code = '{$referral_code}'");
        return \Db::getInstance()->getValue($query);
    }

    // Import
    public static function importPlayer($id_customer) {

        $customer = new \Customer($id_customer);
        $player = new Player($id_customer); // Make sure that the player is created, if not existed already

        $import_points = (INT)\Tools::getValue('import_points');
        $import_orders = (BOOL)\Tools::getValue('import_orders');

        // Handling Core Loyalty Points
        if ($import_points > 0) {
            if (\Module::isInstalled('loyalty')) {

                include_once _PS_MODULE_DIR_ . 'loyalty/classes/LoyaltyModule.php';
                include_once _PS_MODULE_DIR_ . 'loyalty/classes/LoyaltyStateModule.php';

                $points = \LoyaltyModule\LoyaltyModule::getPointsByCustomer($id_customer);
                $coins_change = ceil($points * $import_points);

                $playerHistory = new PlayerHistory();
                $playerHistory->id_customer = $id_customer;
                $playerHistory->coins = $coins_change;
                $playerHistory->viewable = false;
                $playerHistory->add();
            }
        }

        // Handling old orders
        if ($import_orders) {

            $orders = \Order::getCustomerOrders($id_customer);
            $orders = array_reverse($orders);

            if (!empty($orders)) {
                $krona = new \Genzo_Krona();
                foreach ($orders as $order) {
                    $orderObj = new \Order($order['id_order']);
                    $krona->processOrder($orderObj, $orderObj->current_state);
                }
            }
        }

    }

    // Helper
    public static function updatePlayerLevels($id_customer) {

        $results = array();
        $levels = Level::getLevels(); // Todo: we can make things more efficient, if we use filters here

        foreach ($levels as $level) {
            $results[$level['id_level']] = $level;
        }

        $player_levels = PlayerLevel::getAllPlayerLevels($id_customer);

        foreach ($player_levels as $key => $player_level) {

            // We dont want any info about inactive levels
            if (isset($results[$player_level['id_level']])) {
                unset($player_levels[$key]['active']);// otherwise the active from the level is overwritten
                $results[$player_level['id_level']] = $player_levels[$key];
            }
        }

        // The check begins
        foreach ($results as $result) {

            if (!isset($result['active']) || !$result['active']) {
                continue;
            }

            // Check if the player still has the right to achieve this level
            if (!isset($result['achieved']) || $result['achieve_max'] == 0 || ($result['achieve_max'] > $result['achieved'])) {

                // Get the relevant time span
                $dateStart = null;

                if ($result['condition_time'] || isset($result['achieve_last'])) {

                    if ($result['condition_time']) {
                        $dateStart = date('Y-m-d 00:00:00', strtotime("-{$result['condition_time']} days"));
                    }

                    // If a player has achieved a level, he has to achieve it again from scratch
                    if (isset($result['achieve_last']) && $result['achieved_last'] > $dateStart) {
                        $dateStart = $result['achieved_last'];
                    }
                }

                // What condition needs to be fulfilled?
                $condition = 0;

                if ($result['id_action'] > 0) {

                    // Levels that ask for something like: at least 3 reviews
                    if ($result['condition_type'] == 'action') {
                        $condition = PlayerHistory::countActionByPlayer($id_customer, $result['id_action'], $dateStart);
                    }
                    elseif ($result['condition_type'] == 'order') {
                        $condition = PlayerHistory::countOrderByPlayer($id_customer, $result['id_action'], 'order', $dateStart);
                    }
                    elseif ($result['condition_type'] == 'has_referred') {
                        $condition = PlayerHistory::countOrderByPlayer($id_customer, $result['id_action'], 'ref_referrer', $dateStart);
                    }
                    elseif ($result['condition_type'] == 'was_referred') {
                        $condition = PlayerHistory::countOrderByPlayer($id_customer, $result['id_action'], 'ref_buyer', $dateStart);
                    }
                }
                else {
                    $condition = PlayerHistory::sumActionPointsByPlayer($id_customer, $result['condition_type'], $dateStart);
                }

                // Check if the customer has fulfilled the condition
                if ($condition >= $result['condition']) {

                    // Save the level info
                    $id_player_level = (isset($result['id_player_level'])) ? $result['id_player_level'] : null;

                    $playerLevel = new PlayerLevel($id_player_level);
                    $playerLevel->id_customer = $id_customer;
                    $playerLevel->id_level = $result['id_level'];
                    $playerLevel->active = 1;
                    $adjustment = $result['duration']-1;
                    $playerLevel->active_until = ($result['duration'] > 0) ? date('Y-m-d 23:59:59', strtotime("+{$adjustment} days")) : '0000-00-00 00:00:00'; // If duration is not set -> unlimited
                    $playerLevel->achieved++;
                    $playerLevel->achieved_last = date("Y-m-d H:i:s", strtotime("+1 second")); // This is securing, that the last done action, wont be taken again into account
                    $playerLevel->save();

                    // Give the reward
                    if ($result['reward_type'] == 'coupon') {

                        $id_cart_rule = $result['id_reward'];
                        $coupon = new \CartRule($id_cart_rule);

                        // Clone the cart rule and override some values
                        $coupon->id_customer = $id_customer;

                        // Merchant can set date in cart rule, we need the difference between the dates
                        if ($coupon->date_from && $coupon->date_to) {
                            $validity = strtotime($coupon->date_to) - strtotime($coupon->date_from);
                            $coupon->date_to = date("Y-m-d 23:59:59", strtotime("+{$validity} seconds"));
                        }
                        else {
                            $coupon->date_to = date("Y-m-d 23:59:59", strtotime("+{$result['validity']} days"));
                        }
                        $coupon->date_from = date("Y-m-d H:i:s");


                        foreach (\Language::getIDs() as $id_lang) {
                            $coupon->name[$id_lang] = Coupon::getCouponName($coupon->name[$id_lang]);
                        }

                        $prefix = \Configuration::get('krona_coupon_prefix');
                        $code = strtoupper(\Tools::passwdGen(6));

                        $coupon->code = ($prefix) ? $prefix.'-'.$code : $code;
                        $coupon->active = true;
                        $coupon->add();

                        \CartRule::copyConditions($id_cart_rule, $coupon->id);
                    }
                    elseif ($result['reward_type'] == 'group') {

                        $id_group = $result['id_reward'];
                        $customer = new \Customer($id_customer);
                        $customer->addGroups([$id_group]);

                        // Smaller means higher priority
                        if (PlayerLevel::getPriorityOfGroup($id_group) < PlayerLevel::getPriorityOfGroup($customer->id_default_group)) {
                            $customer->id_default_group = $id_group;
                            $customer->update();
                        }
                    }

                    // Send emails Todo: implement conseqs module
                    if (\Module::isEnabled('genzo_crm')) {

                        // We only wanna send an email when he gets a coupon -> this way we prevent an email on Bauer level
                        if ($result['reward_type'] == 'coupon') {

                            $reward = 'Als Dankeschön haben Sie einen Gutschein erhalten! Der Code ist in Ihrem Konto ersichtlich.';

                            $nextLevel = PlayerLevel::getNextPlayerLevel($id_customer);

                            $args = array(
                                'module' => 'genzo_krona',
                                'key' => 'new_level_achieved',
                                'id_customer' => $id_customer,
                                'shortcodes' => array(
                                    'level' => $result['name'],
                                    'next_level' => $nextLevel->name,
                                    'reward' => $reward,
                                ),
                            );

                            \Hook::exec('actionSendEmail', $args, null, false, false);
                        }
                    }
                }
            }
        }
    }

    public static function getPossibleActions($id_customer) {

        $context = \Context::getContext();

        $actions = Action::getAllActions(['a.active=1', 'a.points_change>0']);

        foreach ($actions as $key => $action) {
            // $actionObj = new Action($action['id_action']);

            // Newsletter
            if ($action['module']=='genzo_krona' && $action['key']=='newsletter') {
                $context = \Context::getContext();
                $actions[$key]['done'] = ($context->customer->newsletter) ? true : false;
                $actions[$key]['possible'] = ($context->customer->newsletter) ? false : true;
            }
            else {

                $params = array(
                    'module_name' => $action['module'],
                    'action_name' => $action['key'],
                    'id_customer' => $id_customer,
                );

                $hook = \Hook::exec('displayKronaActionPoints', $params, null, true, false);

                if ($hook['genzo_krona']['executions_done']) {
                    $actions[$key]['done'] = true;
                }

                if (($action['execution_type'] == 'unlimited') || ($hook['genzo_krona']['executions_possible'])) {
                    $actions[$key]['possible'] = true;
                }
            }
        }

        $action_orders = ActionOrder::getAllActionOrder(['o.active=1', 'o.id_currency='.$context->currency->id]);

        if (isset($action_orders[0])) {
            $actions[] = array(
                'title' => \Configuration::get('krona_order_title', $context->language->id),
                'done' => \Order::getCustomerNbOrders($id_customer) ? true : false,
                'coins_change' => $action_orders[0]['coins_change'],
                'currency' => $action_orders[0]['name'],
            );
        }

        return $actions;

    }

    /* @param Action $action */
    public static function checkIfPlayerStillCanExecuteAction($id_customer, $action, $getInfo = false) {

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

        $execution_times = (int)PlayerHistory::countActionByPlayer($id_customer, $action->id, $startDate, $endDate);
        $executions_left = (int)$action->execution_max-$execution_times;

        if ($action->execution_type == 'unlimited' || $executions_left) {
            if ($getInfo) {
                return array(
                    'executions_possible' => true,
                    'executions_done' => $execution_times,
                );
            }
            return true;
        }
        else {
            if ($getInfo) {
                return array(
                    'executions_possible' => false,
                    'executions_done' => $execution_times,
                );
            }
            return false;
        }
    }

    public static function getDisplayNames($id_customer) {

        $customer = new \Customer($id_customer);

        $display_name =  \Configuration::get('krona_display_name', null, $customer->id_shop_group, $customer->id_shop);

        if ($display_name == 1) {
            $name = $customer->firstname . ' ' . $customer->lastname; // John Doe
        }
        elseif ($display_name == 2) {
            $name = $customer->firstname . ' ' . self::shortenWord($customer->lastname); // John D.
        }
        elseif ($display_name == 3) {
            $name = self::shortenWord($customer->firstname) . ' ' . $customer->lastname; // J. Doe
        }
        elseif ($display_name == 4) {
            $name = self::shortenWord($customer->firstname . ' ' . $customer->lastname); // J. D.
        }
        elseif ($display_name == 5) {
            $name = $customer->firstname; // John
        }
        else {
            $name = 'No name';
        }

        $names = array(
            'display_name' => $name,
            'firstname' => $customer->firstname,
            'lastname'  => $customer->lastname,
        );

        return $names;

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

    public function getRank() {

        $context = \Context::getContext();

        $gamification_total = \Configuration::get('krona_gamification_total', null, $context->shop->id_shop_group, $context->shop->id_shop);

        if ($gamification_total == 'points_coins') {
            $having = 'points+coins > ' . $this->total;
        } elseif ($gamification_total == 'points') {
            $having = 'points > ' . $this->total;
        } elseif ($gamification_total == 'coins') {
            $having = 'coins > ' . $this->total;
        }

        $sql = '
            SELECT COUNT(*)
            FROM '._DB_PREFIX_.'genzo_krona_player AS p
            INNER JOIN (
                SELECT id_customer, SUM(points) AS points, SUM(coins) AS coins
                FROM '._DB_PREFIX_.'genzo_krona_player_history
                GROUP BY id_customer
                HAVING '.$having.'
            ) AS ph ON p.id_customer=ph.id_customer
            INNER JOIN '._DB_PREFIX_.'customer AS c ON p.id_customer=c.id_customer AND c.id_shop='.$context->shop->id;

        return \Db::getInstance()->getValue($sql)+1;
    }

    public static function generateReferralCode() {

        $referral_code = '';
        $exists = true;

        while ($exists) {
            $referral_code = strtoupper(\Tools::passwdGen(6));

            $query = new \DbQuery();
            $query->select('COUNT(*)');
            $query->from(self::$definition['table']);
            $query->where("referral_code = '{$referral_code}' ");
            $exists = \Db::getInstance()->getValue($query);
        }

        return $referral_code;
    }

    public static function getNbrOfOrders($id_customer, $only_valid = true) {

        $query = new \DbQuery();
        $query->select('COUNT(*)');
        $query->from('orders');
        $query->where('id_customer = ' . $id_customer);

        if ($only_valid) {
            $context = \Context::getContext();
            if ($in_states = \Configuration::get('krona_order_state', null, $context->shop->id_shop_group, $context->shop->id_shop)) {
                $query->where("current_state IN ({$in_states})");
            }
        }

        return \Db::getInstance()->getValue($query);
    }

    // CronJob
    public static function cronExpireLoyalty() {

        $players = Player::getAllPlayers();

        foreach (\Shop::getCompleteListOfShopsID() as $id_shop) {

            $id_shop_group = \Shop::getGroupFromShop($id_shop);

            if (\Configuration::get('krona_loyalty_expire_method', null, $id_shop_group, $id_shop)!='none') {

                foreach ($players as $player) {
                    $query = new \DbQuery();
                    $query->select('ph.id_history, ph.id_customer, (ph.loyalty-ph.loyalty_used-ph.loyalty_expired) AS expire, c.id_shop, c.id_shop_group');
                    $query->from('genzo_krona_player_history', 'ph');
                    $query->innerJoin('customer', 'c', 'c.id_customer=ph.id_customer AND c.id_shop='.$id_shop);
                    $query->where('ph.loyalty_expire_date IS NOT NULL AND ph.loyalty_expire_date < NOW()');
                    $query->where('(ph.loyalty-ph.loyalty_used-ph.loyalty_expired) > 0');
                    $query->where('ph.id_customer='.$player['id_customer']);
                    $histories = \Db::getInstance()->ExecuteS($query);

                    $expire_total = 0;

                    foreach ($histories as $history) {
                        $playerHistory = new PlayerHistory($history['id_history']);
                        $playerHistory->loyalty_expired = $history['expire'];
                        $playerHistory->update();

                        $expire_total += $history['expire'];
                    }

                    if ($expire_total) {

                        $expiredHistory = new PlayerHistory();
                        $expiredHistory->id_customer = $player['id_customer'];
                        $expiredHistory->force_display = -$expire_total;

                        foreach (\Language::getIDs() as $id_lang) {

                            $title[$id_lang] = \Configuration::get('krona_loyalty_expire_title', $id_lang, $player['id_shop_group'], $player['id_shop']);
                            $message[$id_lang] = \Configuration::get('krona_loyalty_expire_message', $id_lang, $player['id_shop_group'], $player['id_shop']);

                            // Replace message variables
                            $search = array('{loyalty_points}');
                            $replace = array($expire_total);

                            $message[$id_lang] = str_replace($search, $replace, $message[$id_lang]);

                            $expiredHistory->message[$id_lang] = pSQL($message[$id_lang]);
                            $expiredHistory->title[$id_lang] = pSQL($title[$id_lang]);
                        }

                        $expiredHistory->add();
                    }
                }
            }
        }
    }

}