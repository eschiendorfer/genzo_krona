<?php

/**
 * Copyright (C) 2018 Emanuel Schiendorfer
 *
 * @author    Emanuel Schiendorfer <https://github.com/eschiendorfer>
 * @copyright 2018 Emanuel Schiendorfer
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

if (!defined('_PS_VERSION_'))
	exit;

require_once _PS_MODULE_DIR_ . 'genzo_krona/classes/Action.php';
require_once _PS_MODULE_DIR_ . 'genzo_krona/classes/ActionOrder.php';
require_once _PS_MODULE_DIR_ . 'genzo_krona/classes/Player.php';
require_once _PS_MODULE_DIR_ . 'genzo_krona/classes/PlayerHistory.php';
require_once _PS_MODULE_DIR_ . 'genzo_krona/classes/Level.php';
require_once _PS_MODULE_DIR_ . 'genzo_krona/classes/PlayerLevel.php';
require_once _PS_MODULE_DIR_ . 'genzo_krona/classes/Coupon.php';

require_once _PS_MODULE_DIR_ . 'genzo_krona/classes/helper/Zebra_Image.php';

use KronaModule\Action;
use KronaModule\ActionOrder;
use KronaModule\Player;
use KronaModule\PlayerHistory;
use KronaModule\Level;
use KronaModule\PlayerLevel;
use KronaModule\Coupon;
use KronaModule\Zebra_Image;

class Genzo_Krona extends Module
{
    public $errors;
    public $confirmation;
    public $table_name;
	public $is_multishop;
	public $id_shop_group;
	public $id_shop;
	public $is_loyalty;
	public $is_gamification;
	public $loyalty_total;
	public $gamification_total;
    public $total_name;
    public $loyalty_name;

	function __construct() {
		$this->name = 'genzo_krona';
		$this->tab = 'front_office_features';
		$this->version = '1.0';
		$this->author = 'Emanuel Schiendorfer';
		$this->need_instance = 0;

		$this->bootstrap = true;

		$this->controllers = array('home', 'overview', 'customersettings', 'timeline', 'levels', 'leaderboard', 'loyalty');

	 	parent::__construct();

		$this->displayName = $this->l('Krona Loyalty Points');
		$this->description = $this->l('Build up a community with a points system');
		$this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

		// Get Variables
		$this->table_name = $this->name;

	}

	public function install() {
		if (!parent::install() OR
			!$this->executeSqlScript('install') OR
            !$this->registerHook('displayBackOfficeHeader') OR
            !$this->registerHook('displayHeader') OR
            !$this->registerHook('displayCustomerAccount') OR
            !$this->registerHook('actionExecuteKronaAction') OR
            !$this->registerHook('actionCustomerAccountAdd') OR
            !$this->registerHook('actionOrderStatusUpdate') OR
			!$this->registerHook('ModuleRoutes') OR
            !$this->registerInbuiltActions()
        )
			return false;
		return true;
	}

	public function uninstall() {
		if (!parent::uninstall() OR
			  !$this->executeSqlScript('uninstall')
			)
			return false;
		return true;
	}

    private function executeSqlScript($script) {
        $file = dirname(__FILE__) . '/sql/' . $script . '.sql';
        if (! file_exists($file)) {
            return false;
        }
        $sql = file_get_contents($file);
        if (! $sql) {
            return false;
        }
        $sql = str_replace(['PREFIX_', 'ENGINE_TYPE', 'CHARSET_TYPE'], [_DB_PREFIX_, _MYSQL_ENGINE_, 'utf8'], $sql);
        $sql = preg_split("/;\s*[\r\n]+/", $sql);
        foreach ($sql as $statement) {
            $stmt = trim($statement);
            if ($stmt) {
                if (!Db::getInstance()->execute($stmt)) {
                    return false;
                }
            }
        }
        return true;
    }

	private function registerInbuiltActions() {

	    // Order Actions
	    $currencies = Currency::getCurrencies(false, false, true);

	    foreach ($currencies as $currency) {
            $actionOrder = new ActionOrder();
            $actionOrder->id_currency = $currency['id_currency'];
            $actionOrder->coins_change = 1;
            $actionOrder->minimum_amount = 0;
            $actionOrder->active = 1;
            $actionOrder->add();
        }

        // Inbuilt Actions
        $actions = array('account_creation', 'page_visit', 'avatar_upload', 'newsletter');

        foreach ($actions as $action) {
            $this->registerAction($this->name, $action);
        }

        $this->updateInbuiltActions();

        return true;

    }

    private function updateInbuiltActions() {

	    $ids_lang = Language::getIDs();

	    // Order Action
        $id_order = Action::getIdAction($this->name, 'order');
        $order = new Action($id_order);
        $order->execution_type = 'unlimited';
        $order->execution_max = 0;
        $order->points_change = 100;
        foreach ($ids_lang as $id_lang) {
            $order->title[$id_lang] = 'New Order';
            $order->message[$id_lang] = 'You received {points} Points for the order ({reference}) with the amount {amount}.';
        }
        $order->update();


	    // Account Creation
	    $id_account = Action::getIdAction($this->name, 'account_creation');
	    $account = new Action($id_account);
	    $account->execution_type = 'per_lifetime';
	    $account->execution_max = 1;
	    $account->points_change = 100;
	    foreach ($ids_lang as $id_lang) {
	        $account->title[$id_lang] = 'Account Creation';
	        $account->message[$id_lang] = 'You received {points} Points for creating an account.';
        }
	    $account->update();

	    // Page Visit
	    $id_visit = Action::getIdAction($this->name, 'page_visit');
	    $visit = new Action($id_visit);
	    $visit->execution_type = 'per_day';
	    $visit->execution_max = 1;
	    $visit->points_change = 5;
        foreach ($ids_lang as $id_lang) {
            $visit->title[$id_lang] = 'Page Visit';
            $visit->message[$id_lang] = 'You received {points} Points for visiting our website today.';
        }
	    $visit->update();

	    // Avatar Upload
        $id_avatar = Action::getIdAction($this->name, 'avatar_upload');
        $avatar = new Action($id_avatar);
        $avatar->execution_type = 'per_lifetime';
        $avatar->execution_max = 1;
        $avatar->points_change = 50;
        foreach ($ids_lang as $id_lang) {
            $avatar->title[$id_lang] = 'Avatar Upload';
            $avatar->message[$id_lang] = 'You received {points} Points for uploading an avatar.';
        }
        $avatar->update();

	    // Newsletter
        $id_newsletter = Action::getIdAction($this->name, 'newsletter');
        $newsletter = new Action($id_newsletter);
        $newsletter->execution_type = 'per_month';
        $newsletter->execution_max = 1;
        $newsletter->points_change = 20;
        foreach ($ids_lang as $id_lang) {
            $newsletter->title[$id_lang] = 'Newsletter Subscription';
            $newsletter->message[$id_lang] = 'You received {points} because you are subscribed to our newsletter.';
        }
        $newsletter->update();


        // Save Settings
        // Lang fields
        $game_names = array();
        $total_names = array();
        $loyalty_names = array();

        foreach ($ids_lang as $id_lang) {
            $game_names[$id_lang] = 'Loyalty Reward Program'; // Just as an example
            $total_names[$id_lang] = 'Lifetime Points'; // Just as an example
            $loyalty_names[$id_lang] = 'Loyalty Points'; // Just as an example
        }

        foreach (Shop::getShops() as $shop) {

            $id_shop_group = $shop['id_shop_group'];
            $id_shop = $shop['id_shop'];

            // Lang fields
            if (!Configuration::get('krona_game_name', null, $id_shop_group, $id_shop)) {
                Configuration::updateValue('krona_game_name', $game_names, false, $id_shop_group, $id_shop);
            }
            if (!Configuration::get('krona_total_name', null, $id_shop_group, $id_shop)) {
                Configuration::updateValue('krona_total_name', $total_names, false, $id_shop_group, $id_shop);
            }
            if (!Configuration::get('krona_loyalty_name', null, $id_shop_group, $id_shop)) {
                Configuration::updateValue('krona_loyalty_name', $loyalty_names, false, $id_shop_group, $id_shop);
            }

            // Basic Fields
            if (!Configuration::get('krona_loyalty_active', null, $id_shop_group, $id_shop)) {
                Configuration::updateValue('krona_loyalty_active', 0, false, $id_shop_group, $id_shop);
            }
            if (!Configuration::get('krona_loyalty_total', null, $id_shop_group, $id_shop)) {
                Configuration::updateValue('krona_loyalty_total', 'points_coins', false, $id_shop_group, $id_shop);
            }
            if (!Configuration::get('krona_gamification_active', null, $id_shop_group, $id_shop)) {
                Configuration::updateValue('krona_gamification_active', 0, false, $id_shop_group, $id_shop);
            }
            if (!Configuration::get('krona_gamification_total', null, $id_shop_group, $id_shop)) {
                Configuration::updateValue('krona_gamification_total', 'points_coins', false, $id_shop_group, $id_shop);
            }
            if (!Configuration::get('krona_url', null, $id_shop_group, $id_shop)) {
                Configuration::updateValue('krona_url', 'krona', false, $id_shop_group, $id_shop);
            }
            if (!Configuration::get('krona_customer_active', null, $id_shop_group, $id_shop)) {
                Configuration::updateValue('krona_customer_active', 1, false, $id_shop_group, $id_shop);
            }
            if (!Configuration::get('krona_display_name', null, $id_shop_group, $id_shop)) {
                Configuration::updateValue('krona_display_name', 1, false, $id_shop_group, $id_shop);
            }
            if (!Configuration::get('krona_pseudonym', null, $id_shop_group, $id_shop)) {
                Configuration::updateValue('krona_pseudonym', 1, false, $id_shop_group, $id_shop);
            }
            if (!Configuration::get('krona_avatar', null, $id_shop_group, $id_shop)) {
                Configuration::updateValue('krona_avatar', 1, false, $id_shop_group, $id_shop);
            }
            if (!Configuration::get('krona_order_amount', null, $id_shop_group, $id_shop)) {
                Configuration::updateValue('krona_order_amount', 'total_wt', false, $id_shop_group, $id_shop);
            }
            if (!Configuration::get('krona_order_rounding', null, $id_shop_group, $id_shop)) {
                Configuration::updateValue('krona_order_rounding', 'up', false, $id_shop_group, $id_shop);
            }
            if (!Configuration::get('krona_coupon_prefix', null, $id_shop_group, $id_shop)) {
                Configuration::updateValue('krona_coupon_prefix', 'KR', false, $id_shop_group, $id_shop);
            }

            Configuration::updateValue('krona_import_customer', 0, false, $id_shop_group, $id_shop);
            Configuration::updateValue('krona_dont_import_customer', 0, false, $id_shop_group, $id_shop);
        }

        Configuration::updateGlobalValue('krona_import_customer', 0);
        Configuration::updateGlobalValue('krona_dont_import_customer', 0);

        return true;
    }

	// Backoffice

    public function getContent() {

        // Context
        $id_lang = $this->context->language->id;
        $this->is_multishop = Shop::isFeatureActive();
        $this->id_shop = $this->context->shop->id;
        $this->id_shop_group = $this->context->shop->id_shop_group;

        $this->is_loyalty = Configuration::get('krona_loyalty_active', null, $this->id_shop_group, $this->id_shop);
        $this->is_gamification = Configuration::get('krona_gamification_active', null, $this->id_shop_group, $this->id_shop);
        $this->loyalty_total = Configuration::get('krona_loyalty_total', null, $this->id_shop_group, $this->id_shop);
        $this->gamification_total = Configuration::get('krona_gamification_total', null, $this->id_shop_group, $this->id_shop);

        $this->total_name = Configuration::get('krona_total_name', $id_lang, $this->id_shop_group, $this->id_shop);
        $this->loyalty_name = Configuration::get('krona_loyalty_name', $id_lang, $this->id_shop_group, $this->id_shop);

        // Content
        $tab = null;
        $content = null;
        $data = null;
        $template = null;

        if (Tools::getValue('content')) {
           $content = Tools::getValue('content');
        }

        // Actions
        if (Tools::isSubmit('updateActionInbuilt') OR Tools::isSubmit('updateActionExternal')) {
            $content = 'FormAction';
        }
        elseif (Tools::isSubmit('saveAction')) {
            $this->saveAction();
            $content = 'ListActions';
        }
        elseif (Tools::isSubmit('saveActionOrder')) {
            $this->saveActionOrder();
            $content = 'ListOrders';
        }
        elseif (Tools::isSubmit('toggleActiveActionOrder')) {
            $this->saveToggle('genzo_krona_action_order', 'id_action_order', 'active');
            $content = 'ListActions';
        }
        elseif (Tools::isSubmit('toggleActiveActionInbuilt') OR Tools::isSubmit('toggleActiveActionExternal')) {
            $this->saveToggle('genzo_krona_action', 'id_action', 'active');
            $content = 'ListActions';
        }

        // Orders
        if (Tools::isSubmit('updateOrder')) {
            $content = 'FormOrder';
        }

        // Players
        if (Tools::isSubmit('updatePlayer')) {
            $content = 'FormPlayer';
        }
        elseif (Tools::isSubmit('toggleActivePlayer')) {
            $this->saveToggle('genzo_krona_player', 'id_customer', 'active');
            $content = 'ListPlayers';
        }
        elseif (Tools::isSubmit('toggleBannedPlayer')) {
            $this->saveToggle('genzo_krona_player', 'id_customer', 'banned');
            $content = 'ListPlayers';
        }
        elseif (Tools::isSubmit('deletePlayerLevel')) {
            $this->deletePlayerLevel();
            $content = 'FormPlayer';
        }
        elseif (Tools::isSubmit('deletePlayerHistory')) {
            $this->deletePlayerHistory();
            $content = 'FormPlayer';
        }
        elseif (Tools::isSubmit('savePlayer')) {
            $this->savePlayer();
            $content = 'FormPlayer';
        }
        elseif (Tools::isSubmit('saveCustomAction')) {
            $customAction = $this->saveCustomAction();
            if (is_a($customAction, 'KronaModule\PlayerHistory')) {
                $content = 'FormCustomAction';
                $data = $customAction;
            }
            else {
                $content = 'FormPlayer';
            }
        }
        elseif (Tools::isSubmit('importCustomers')) {

            $customers = Customer::getCustomers(true);

            foreach ($customers as $customer) {
                Player::importPlayer($customer['id_customer']);
            }
            // No multistore handling
                foreach (Shop::getContextListShopID() as $id_shop) {
                Configuration::updateValue('krona_import_customer', 1, false, $this->id_shop_group, $id_shop);
            }

            $this->confirmation = $this->l('Player were sucessfully imported.');
        }
        elseif (Tools::isSubmit('dontImportCustomers')) {
            // No multistore handling
            foreach (Shop::getShops() as $shop) {
                Configuration::updateValue('krona_dont_import_customer', 1, false, $shop['id_shop_group'], $shop['id_shop']);
            }
            Configuration::updateGlobalValue('krona_dont_import_customer', 1);
        }

        // Levelsyou
        if (Tools::isSubmit('saveLevel')) {
            $level = $this->saveLevel();
            if (is_a($level, 'KronaModule\Level')) {
                $content = 'FormLevel';
                $data = $level;
            }
            else {
                $content = 'ListLevels';
            }
        }
        elseif (Tools::isSubmit('updateLevel_total') OR
                Tools::isSubmit('updateLevel_points') OR
                Tools::isSubmit('updateLevel_coins') OR
                Tools::isSubmit('updateLevel_action')
        ) {
            $content = 'FormLevel';
        }
        elseif (Tools::isSubmit('deleteLevel_total') OR
                Tools::isSubmit('deleteLevel_points') OR
                Tools::isSubmit('deleteLevel_coins') OR
                Tools::isSubmit('deleteLevel_action')
        ) {
            $this->deleteLevel();
            $content = 'ListLevels';
        }
        elseif (Tools::isSubmit('toggleActiveLevel_total') OR
                Tools::isSubmit('toggleActiveLevel_coins') OR
                Tools::isSubmit('toggleActiveLevel_points') OR
                Tools::isSubmit('toggleActiveLevel_points')
        ) {
            $this->saveToggle('genzo_krona_level', 'id_level', 'active');
            $content = 'ListLevels';
        }


        // Coupons
        if (Tools::isSubmit('updateCoupon')) {
            $id_cart_rule = (int)Tools::getValue('id_cart_rule');
            $url = $this->context->link->getAdminLink('AdminCartRules', true) . '&updatecart_rule&id_cart_rule=' . $id_cart_rule;
            Tools::redirectAdmin($url);
        }

        // Settings
        if (Tools::isSubmit('saveSettings')) {
            $this->saveSettings();
            $content = 'FormSettings';
        }
        elseif (Tools::isSubmit('saveGroupsPriority')) {
            $this->saveGroupsPriority();
            $content = 'FormSettings';
        }
        // Check if group priorities have all entries
        $this->checkGroups();

        // Register all Actions from (external) Modules
        if ($content == 'ListActions') {

            $modules = Hook::exec('actionRegisterKronaAction', [], null, true, false);

            if(!empty($modules)) {
                foreach ($modules as $module_name => $actions) {
                    if (!empty($actions)) {
                        foreach ($actions as $action_name) {
                            $this->registerAction($module_name, $action_name);
                        }
                    }
                }
                $url = $this->context->link->getAdminLink('AdminModules', true) . '&configure=' . $this->name . '&module_name=' . $this->name;
                Tools::redirectAdmin($url);
            }
        }

        if (!$content) {
            $content = 'ListActions';
        }

        switch ($content) {
            case 'ListActions' :
                $content = $this->generateListActions(true) . $this->generateListActions(false);
                $tab = 'Actions';
                break;
            case 'FormAction' :
                $content = $this->generateFormAction();
                $tab = 'Actions';
                break;
            case 'ListOrders' :
                $content = $this->generateListOrders();
                $tab = 'Orders';
                break;
            case 'FormOrder' :
                $content = $this->generateFormOrder();
                $tab = 'Orders';
                break;
            case 'ListPlayers' :
                $content = $this->generateListPlayers();
                $tab = 'Players';
                break;
            case 'FormPlayer' :
                $content = $this->generateFormPlayer() . $this->generateListPlayerLevels() . $this->generateListPlayerHistory();
                $tab = 'Players';
                break;
            case 'FormSettings' :
                ($this->checkContextMultistore()) ? $content = $this->checkContextMultistore() : $content = $this->generateFormSettings();
                $tab = 'Settings';
                break;
            case 'FormCustomAction' :
                $content = $this->generateFormCustomAction($data);
                $tab = 'Actions';
                break;
            case 'FormLevel' :
                $content = $this->generateFormLevel($data);
                $tab = 'Levels';
                break;
            case 'ListLevels' :
                $content =  $this->generateListLevels('total') . $this->generateListLevels('points') .
                            $this->generateListLevels('coins') . $this->generateListLevels('action');
                $tab = 'Levels';
                break;
            case 'ListCoupons' :
                $content = $this->generateListCoupons();
                $tab = 'Coupons';
                break;
            case 'Support' :
                $tab = 'Support';
                break;
        }

        // Individual Smarty
        if ($tab == 'Settings') {
            $this->context->smarty->assign(array(
                'groups'   => $this->getGroupsPriority(),
                'cronJob'  => $this->getCronJobUrl(),
            ));
        }
        elseif ($tab == 'Players') {
            $this->context->smarty->assign(array(
                'import'  => Configuration::get('krona_import_customer', null, $this->id_shop_group, $this->id_shop),
                'dont'    => Configuration::get('krona_dont_import_customer', null, $this->id_shop_group, $this->id_shop),
            ));
        }

        // Global Smarty
        $this->context->smarty->assign(array(
            'errors'        => $this->errors,
            'confirmation'  => $this->confirmation,
            'content'       => $content,
            'tab'           => $tab,
            'action_url'    => $this->context->link->getAdminLink('AdminModules', true) . '&configure=' . $this->name . '&module_name=' . $this->name,
            'total_name'    => $this->total_name,
            'loyalty_name'    => $this->loyalty_name,
        ));


        // Special Designs needs to be down here, so that every smarty variable is assigned before
        if ($tab == 'Support') {
            $template = $this->display(__FILE__, 'views/templates/admin/support.tpl');
        }
        elseif ($tab == 'Actions') {
            $this->checkCurrencies();
        }
        else if (_PS_VERSION_!='1.6.1.999') {
            $template = $this->display(__FILE__, 'views/templates/admin/prestashop.tpl');
        }

        return ($template) ? $template : $this->display(__FILE__, 'views/templates/admin/main.tpl');
    }


    // Lists
    private function generateListActions($inbuilt_actions = null) {
        $fields_list = array(
            'id_action' => array(
                'title' => 'ID',
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'alias' => 'a',
                'filter_type' => 'int',
            ),
            'module' => array(
                'title' => 'Module',
                'align' => 'left',
            ),
            'key' => array(
                'title' => 'Key',
                'align' => 'left',
            ),
            'title' => array(
                'title' => $this->l('Title'),
                'align' => 'left',
            ),
            'points_change' => array(
                'title' => $this->l('Points Change'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'filter_type' => 'int'
            ),
            'execution_type' => array(
                'title' => $this->l('Execution Type'),
                'align' => 'left',
            ),
            'execution_max' => array(
                'title' => $this->l('Execution Max'),
                'align' => 'center',
                'filter_type' => 'int',
                'class' => 'fixed-width-xs',
            ),
            'active' => array(
                'title' => $this->l('Active'),
                'active' => 'toggleActive',
                'class' => 'fixed-width-xs',
                'align' => 'center',
                'type'  => 'bool',
                'filter_type' => 'int',
            )
        );

        $helper = new HelperList();
        $helper->shopLinkType = '';
        $helper->actions = array('edit');
        $helper->identifier = 'id_action';
        $helper->table = ($inbuilt_actions) ? 'ActionInbuilt' : 'ActionExternal';
        $helper->_pagination = [2,20,50,100,300];

        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $index = '&configure=' . $this->name . '&module_name=' . $this->name . '&content=ListActions';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . $index;

        // Filter, Pagination and Oder_By -> get Situation
        (Tools::isSubmit('submitFilter')) ? $filter_used = true : $filter_used = false;
        (Tools::isSubmit('submitFilter'.$helper->table)) ? $pagination_used = true : $pagination_used = false;
        (Tools::getValue($helper->table.'Orderby')) ? $order_by_used = true : $order_by_used = false;

        // Filter
        $filters = null;
        if ($filter_used OR $pagination_used) {
            $filters = $this->getFiltersFromList($fields_list, $helper->table);
        }

        if (Tools::isSubmit('submitReset'.$helper->table)) {
            foreach ($fields_list as $fieldName => $field) {
                unset($_POST[$helper->table.'Filter_'.$fieldName]);
                unset($_GET[$helper->table.'Filter_'.$fieldName]);
            }
            $filters = null;
        }

        // Pagination
        if ($pagination_used) {
            $pagination = $this->getPagination($helper->table);
            $this->context->cookie->{$helper->table.'pagination'};
        }
        elseif ($this->context->cookie->{$helper->table.'pagination'}) {
            $pagination['limit'] = $this->context->cookie->{$helper->table.'pagination'};
            $pagination['offset'] = 0;
        }
        elseif ($helper->_default_pagination) {
            $pagination['limit'] = $helper->_default_pagination;
            $pagination['offset'] = 0;
        }
        else {
            $pagination = null;
        }

        // OrderBy
        $order = array();
        if ($order_by_used AND Tools::getValue($helper->table.'Orderway')) {

            $order_by = Tools::getValue($helper->table.'Orderby');
            $order_way = Tools::getValue($helper->table.'Orderway');

            $order['order_by']  = $order_by;
            $order['order_way'] = $order_way;

            $this->context->cookie->{$helper->table.'Orderby'}  = $order_by;
            $this->context->cookie->{$helper->table.'Orderway'} = $order_way;

            // Handle Alias
            if (!empty($fields_list[$order_by]['alias'])) {
                $order['alias'] = $fields_list[$order_by]['alias'];
                $this->context->cookie->{$helper->table.'alias'}  = $fields_list[$order_by]['alias'];
            }
            else {
                unset($this->context->cookie->{$helper->table.'alias'});
            }

        }
        elseif (!empty($this->context->cookie->{$helper->table.'Orderway'})) {
            $order['order_by']  = $this->context->cookie->{$helper->table.'Orderby'};
            $order['order_way'] = $this->context->cookie->{$helper->table.'Orderway'};
            $order['alias'] = $this->context->cookie->{$helper->table.'alias'};
        }

        // Set Final Settings
        $helper->listTotal = Action::getTotalActions($filters, $inbuilt_actions);
        $title = ($inbuilt_actions) ? $this->l('All inbuilt Actions') : $this->l('All external Actions');
        $helper->title = $title . " ({$helper->listTotal})";


        $values = Action::getAllActions($filters, $pagination, $order, $inbuilt_actions);

        return $helper->generateList($values, $fields_list);
    }

    private function generateListOrders() {
        $fields_list = array(
            'id_action_order' => array(
                'title' => 'ID',
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'filter_type' => 'int',
            ),
            'name' => array(
                'title' => $this->l('Currency'),
                'align' => 'left',
            ),
            'coins_change' => array(
                'title' => $this->l('Coins Change'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'filter_type' => 'int'
            ),
            'minimum_amount' => array(
                'title' => $this->l('Minimum Amount'),
                'align' => 'left',
            ),
            'active' => array(
                'title' => $this->l('Active'),
                'active' => 'toggleActive',
                'class' => 'fixed-width-xs',
                'align' => 'center',
                'type'  => 'bool',
                'filter_type' => 'int',
            )
        );

        $helper = new HelperList();
        $helper->shopLinkType = '';
        $helper->actions = array('edit');
        $helper->identifier = 'id_action_order';
        $helper->table = 'Order';
        $helper->_pagination = [2,20,50,100,300];

        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $index = '&configure=' . $this->name . '&module_name=' . $this->name . '&content=ListOrders';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . $index;

        // Filter, Pagination and Oder_By -> get Situation
        (Tools::isSubmit('submitFilter')) ? $filter_used = true : $filter_used = false;
        (Tools::isSubmit('submitFilter'.$helper->table)) ? $pagination_used = true : $pagination_used = false;
        (Tools::getValue($helper->table.'Orderby')) ? $order_by_used = true : $order_by_used = false;

        // Filter
        $filters = null;
        if ($filter_used OR $pagination_used) {
            $filters = $this->getFiltersFromList($fields_list, $helper->table);
        }

        if (Tools::isSubmit('submitReset'.$helper->table)) {
            foreach ($fields_list as $fieldName => $field) {
                unset($_POST[$helper->table.'Filter_'.$fieldName]);
                unset($_GET[$helper->table.'Filter_'.$fieldName]);
            }
            $filters = null;
        }

        // Pagination
        if ($pagination_used) {
            $pagination = $this->getPagination($helper->table);
            $this->context->cookie->{$helper->table.'pagination'};
        }
        elseif ($this->context->cookie->{$helper->table.'pagination'}) {
            $pagination['limit'] = $this->context->cookie->{$helper->table.'pagination'};
            $pagination['offset'] = 0;
        }
        elseif ($helper->_default_pagination) {
            $pagination['limit'] = $helper->_default_pagination;
            $pagination['offset'] = 0;
        }
        else {
            $pagination = null;
        }

        // OrderBy
        $order = array();
        if ($order_by_used AND Tools::getValue($helper->table.'Orderway')) {

            $order_by = Tools::getValue($helper->table.'Orderby');
            $order_way = Tools::getValue($helper->table.'Orderway');

            $order['order_by']  = $order_by;
            $order['order_way'] = $order_way;

            $this->context->cookie->{$helper->table.'Orderby'}  = $order_by;
            $this->context->cookie->{$helper->table.'Orderway'} = $order_way;

            // Handle Alias
            if (!empty($fields_list[$order_by]['alias'])) {
                $order['alias'] = $fields_list[$order_by]['alias'];
                $this->context->cookie->{$helper->table.'alias'}  = $fields_list[$order_by]['alias'];
            }
            else {
                unset($this->context->cookie->{$helper->table.'alias'});
            }

        }
        elseif (!empty($this->context->cookie->{$helper->table.'Orderway'})) {
            $order['order_by']  = $this->context->cookie->{$helper->table.'Orderby'};
            $order['order_way'] = $this->context->cookie->{$helper->table.'Orderway'};
            $order['alias'] = $this->context->cookie->{$helper->table.'alias'};
        }

        // Set Final Settings
        $helper->listTotal = ActionOrder::getTotalActionOrder($filters);
        $title = $this->l('Order Actions') ;
        $helper->title = $title . " ({$helper->listTotal})";

        $values = ActionOrder::getAllActionOrder($filters, $pagination, $order);

        return $helper->generateList($values, $fields_list);
    }

    private function generateListPlayerHistory() {

	    $id_customer = Tools::getValue('id_customer');

        $fields_list = array(
            'id_history' => array(
                'title' => 'ID',
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'alias' => 'h',
                'filter_type' => 'int',
            ),
            'title' => array(
                'title' => $this->l('Action'),
                'align' => 'left',
            ),
            'message' => array(
                'title' => $this->l('Message'),
                'align' => 'left',
            ),
            'change' => array(
                'title' => $this->l('Change'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'filter_type' => 'int'
            ),
            'url' => array(
                'title' => 'Url',
                'align' => 'left',
                'remove_onclick' => true,
            ),
        );

        $helper = new HelperList();
        $helper->table = 'PlayerHistory';
        $helper->shopLinkType = '';
        $helper->actions = array('delete');
        $helper->identifier = 'id_history';
        $helper->_pagination = [20,50,100];
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->toolbar_btn = array(
            'new' =>
                array(
                    'desc' => $this->l('New Entry'),
                    'href' => $this->context->link->getAdminLink('AdminModules', true) . '&configure=' . $this->name . '&module_name=' .
                                $this->name . '&content=FormCustomAction' . '&id_customer='.$id_customer,
                ),
        );

        // Index is very important for filtering on a sublist. Here we can set paramaters which can be used later with getValue
        $index = '&configure=' . $this->name . '&module_name=' . $this->name . '&id_customer='. $id_customer .'&content=FormPlayer';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . $index;

        // Filter, Pagination and Oder_By -> get Situation
        (Tools::isSubmit('filter').$helper->table) ? $filter_used = true : $filter_used = false;
        (Tools::isSubmit('submitFilter'.$helper->table)) ? $pagination_used = true : $pagination_used = false;
        (Tools::getValue($helper->table.'Orderby')) ? $order_by_used = true : $order_by_used = false;

        // Filter
        $filters = null;
        if ($filter_used OR $pagination_used) {
            $filters = $this->getFiltersFromList($fields_list, $helper->table);
        }

        if (Tools::isSubmit('submitReset'.$helper->table)) {
            foreach ($fields_list as $fieldName => $field) {
                unset($_POST[$helper->table.'Filter_'.$fieldName]);
                unset($_GET[$helper->table.'Filter_'.$fieldName]);
            }
            $filters = null;
        }

        // Pagination
        if ($pagination_used) {
            $pagination = $this->getPagination($helper->table);
        }
        elseif ($helper->_default_pagination) {
            $pagination['limit'] = $helper->_default_pagination;
            $pagination['offset'] = 0;
        }
        else {
            $pagination = null;
        }

        // OrderBy
        $order = array();
        if ($order_by_used AND Tools::getValue($helper->table.'Orderway')) {
            $order_by = Tools::getValue($helper->table.'Orderby');

            $order['order_by']  = $order_by;
            $order['order_way'] = Tools::getValue($helper->table.'Orderway');
            if (!empty($fields_list[$order_by]['alias'])) {
                $order['alias'] = $fields_list[$order_by]['alias'];
            }
        }

        // Set Final Settings
        $helper->listTotal = PlayerHistory::getTotalHistoryByPlayer($id_customer, $filters);
        $helper->title = $this->l('Player History');

        $values = PlayerHistory::getHistoryByPlayer($id_customer, $filters, $pagination, $order);

        return $helper->generateList($values, $fields_list);
    }

    private function generateListPlayerLevels() {

	    $id_customer = Tools::getValue('id_customer');

        $fields_list = array(
            'id' => array(
                'title' => 'ID',
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'alias' => 'h',
                'filter_type' => 'int',
            ),
            'name' => array(
                'title' => $this->l('Level'),
                'align' => 'left',
            ),
            'active_until' => array(
                'title' => $this->l('Active until'),
                'align' => 'left',
            ),
            'achieved_last' => array(
                'title' => $this->l('Last achieved'),
                'align' => 'left',
            ),
            'active' => array(
                'title' => $this->l('Active'),
                'align' => 'left',
            ),
        );

        $helper = new HelperList();
        $helper->table = 'PlayerLevel';
        $helper->shopLinkType = '';
        $helper->actions = array('delete');
        $helper->identifier = 'id';
        $helper->_pagination = [20,50,100];
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        // Index is very important for filtering on a sublist. Here we can set paramaters which can be used later with getValue
        $index = '&configure=' . $this->name . '&module_name=' . $this->name . '&id_customer='. $id_customer .'&content=FormPlayer';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . $index;

        // Filter, Pagination and Oder_By -> get Situation
        (Tools::isSubmit('filter').$helper->table) ? $filter_used = true : $filter_used = false;
        (Tools::isSubmit('submitFilter'.$helper->table)) ? $pagination_used = true : $pagination_used = false;
        (Tools::getValue($helper->table.'Orderby')) ? $order_by_used = true : $order_by_used = false;

        // Filter
        $filters = null;
        if ($filter_used OR $pagination_used) {
            $filters = $this->getFiltersFromList($fields_list, $helper->table);
        }

        if (Tools::isSubmit('submitReset'.$helper->table)) {
            foreach ($fields_list as $fieldName => $field) {
                unset($_POST[$helper->table.'Filter_'.$fieldName]);
                unset($_GET[$helper->table.'Filter_'.$fieldName]);
            }
            $filters = null;
        }

        // Pagination
        if ($pagination_used) {
            $pagination = $this->getPagination($helper->table);
        }
        elseif ($helper->_default_pagination) {
            $pagination['limit'] = $helper->_default_pagination;
            $pagination['offset'] = 0;
        }
        else {
            $pagination = null;
        }

        // OrderBy
        $order = array();
        if ($order_by_used AND Tools::getValue($helper->table.'Orderway')) {
            $order_by = Tools::getValue($helper->table.'Orderby');

            $order['order_by']  = $order_by;
            $order['order_way'] = Tools::getValue($helper->table.'Orderway');
            if (!empty($fields_list[$order_by]['alias'])) {
                $order['alias'] = $fields_list[$order_by]['alias'];
            }
        }

        // Set Final Settings
        $helper->listTotal = PlayerLevel::getAllPlayerLevelsTotal($id_customer, $filters);
        $helper->title = $this->l('Achieved Levels');

        $values = PlayerLevel::getAllPlayerLevels($id_customer, $filters, $pagination, $order);

        return $helper->generateList($values, $fields_list);
    }

    private function generateListPlayers() {

        $fields_list['id_customer'] = array(
            'title' => 'ID',
            'align' => 'center',
            'class' => 'fixed-width-xs',
            'filter_type' => 'int',
        );

        $fields_list['pseudonym'] = array(
            'title' => $this->l('Pseudonym'),
            'align' => 'left',
        );
        if (($this->is_loyalty AND $this->loyalty_total!='coins') OR ($this->is_gamification AND $this->gamification_total!='coins')) {
            $fields_list['points'] = array(
                'title' => $this->l('Points'),
                'class' => 'fixed-width-xs',
                'align' => 'left',
            );
        }

        if (($this->is_loyalty AND $this->loyalty_total!='points') OR ($this->is_gamification AND $this->gamification_total!='points')) {
            $fields_list['coins'] = array(
                'title' => $this->l('Coins'),
                'class' => 'fixed-width-xs',
                'align' => 'left',
            );
        }

        if ($this->is_gamification) {
            $fields_list['total'] = array(
                'title' => $this->total_name,
                'class' => 'fixed-width-xs',
                'align' => 'left',
            );
        }
        if ($this->is_loyalty) {
            $fields_list['loyalty'] = array(
                'title' => $this->loyalty_name,
                'class' => 'fixed-width-xs',
                'align' => 'left',
            );
        }
        $fields_list['active'] = array(
            'title' => $this->l('Active'),
            'active' => 'toggleActive',
            'class' => 'fixed-width-xs',
            'align' => 'center',
            'type'  => 'bool',
            'filter_type' => 'int',
        );
        $fields_list['banned'] = array(
            'title' => $this->l('Banned'),
            'active' => 'toggleBanned',
            'class' => 'fixed-width-xs',
            'align' => 'center',
            'type'  => 'bool',
            'filter_type' => 'int',
        );


        $helper = new HelperList();
        $helper->shopLinkType = '';
        $helper->actions = array('edit');
        $helper->identifier = 'id_customer';
        $helper->table = 'Player';
        $helper->_pagination = [20,50,100];
        $helper->_default_pagination = 50;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $index = '&configure=' . $this->name . '&module_name=' . $this->name . '&content=ListPlayers';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . $index;

        // Filter, Pagination and Oder_By -> get Situation
        (Tools::isSubmit('submitFilter')) ? $filter_used = true : $filter_used = false;
        (Tools::isSubmit('submitFilter'.$helper->table)) ? $pagination_used = true : $pagination_used = false;
        (Tools::getValue($helper->table.'Orderby')) ? $order_by_used = true : $order_by_used = false;

        // Filter
        $filters = null;
        if ($filter_used OR $pagination_used) {
            $filters = $this->getFiltersFromList($fields_list, $helper->table);
        }

        if (Tools::isSubmit('submitReset'.$helper->table)) {
            foreach ($fields_list as $fieldName => $field) {
                unset($_POST[$helper->table.'Filter_'.$fieldName]);
                unset($_GET[$helper->table.'Filter_'.$fieldName]);
            }
            $filters = null;
        }

        // Pagination
        if ($pagination_used) {
            $pagination = $this->getPagination($helper->table);
        }
        elseif ($helper->_default_pagination) {
            $pagination['limit'] = $helper->_default_pagination;
            $pagination['offset'] = 0;
        }
        else {
            $pagination = null;
        }

        // OrderBy
        $order = array();
        if ($order_by_used AND Tools::getValue($helper->table.'Orderway')) {
            $order_by = Tools::getValue($helper->table.'Orderby');

            $order['order_by']  = $order_by;
            $order['order_way'] = Tools::getValue($helper->table.'Orderway');
            if (!empty($fields_list[$order_by]['alias'])) {
                $order['alias'] = $fields_list[$order_by]['alias'];
            }
            $this->context->cookie->{$helper->table.'Orderby'}  = Tools::getValue($helper->table.'Orderby');
            $this->context->cookie->{$helper->table.'Orderway'} = Tools::getValue($helper->table.'Orderway');
        }
        elseif (!empty($this->context->cookie->{$helper->table.'Orderway'})) {
            $order['order_by']  = $this->context->cookie->{$helper->table.'Orderby'};
            $order['order_way'] = $this->context->cookie->{$helper->table.'Orderway'};
        }

        // Set Final Settings
        $helper->listTotal = Player::getTotalPlayers($filters);
        $helper->title = $this->l('All Players') . " ({$helper->listTotal})";

        $values = Player::getAllPlayers($filters, $pagination, $order);

        return $helper->generateList($values, $fields_list);
    }

    private function generateListLevels($condition_type = null) {

        $fields_list = array(
            'id_level' => array(
                'title' => 'ID',
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'filter_type' => 'int',
            ),
            'name' => array(
                'title' => $this->l('Name'),
                'align' => 'left',
            ),
            'condition' => array(
                'title' => $this->l('Condition'),
                'align' => 'left',
            ),
            'condition_time' => array(
                'title' => $this->l('Days to achieve'),
                'align' => 'left',
            ),
            'achieve_max' => array(
                'title' => $this->l('Achieve max'),
                'align' => 'left',
            ),
            'duration' => array(
                'title' => $this->l('Days of reward'),
                'align' => 'left',
            ),
            'reward_type' => array(
                'title' => $this->l('Reward Type'),
                'align' => 'left',
            ),
            'active' => array(
                'title' => $this->l('Active'),
                'active' => 'toggleActive',
                'class' => 'fixed-width-xs',
                'align' => 'center',
                'type'  => 'bool',
                'filter_type' => 'int',
            ),
        );

        $helper = new HelperList();
        $helper->shopLinkType = '';
        $helper->actions = array('edit', 'delete');
        $helper->identifier = 'id_level';
        $helper->table = 'Level_'.$condition_type;
        $helper->_pagination = [20,50,100];

        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->toolbar_btn = array(
            'new' =>
                array(
                    'desc' => $this->l('New Entry'),
                    'href' => $this->context->link->getAdminLink('AdminModules', true) . '&configure=' . $this->name . '&module_name=' .
                        $this->name . '&content=FormLevel',
                ),
        );
        $index = '&configure=' . $this->name . '&module_name=' . $this->name . '&content=ListLevels';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . $index;

        // Filter, Pagination and Oder_By -> get Situation
        (Tools::isSubmit('submitFilter')) ? $filter_used = true : $filter_used = false;
        (Tools::isSubmit('submitFilter'.$helper->table)) ? $pagination_used = true : $pagination_used = false;
        (Tools::getValue($helper->table.'Orderby')) ? $order_by_used = true : $order_by_used = false;

        // Filter
        $filters = null;
        if ($filter_used OR $pagination_used) {
            $filters = $this->getFiltersFromList($fields_list, $helper->table);

        }
        // Custom Filter
        if ($condition_type) {
            $filters[] = "`condition_type` LIKE '%{$condition_type}%'";
        }

        if (Tools::isSubmit('submitReset'.$helper->table)) {
            foreach ($fields_list as $fieldName => $field) {
                unset($_POST[$helper->table.'Filter_'.$fieldName]);
                unset($_GET[$helper->table.'Filter_'.$fieldName]);
            }
            $filters = null;
        }

        // Pagination
        if ($pagination_used) {
            $pagination = $this->getPagination($helper->table);
        }
        elseif ($helper->_default_pagination) {
            $pagination['limit'] = $helper->_default_pagination;
            $pagination['offset'] = 0;
        }
        else {
            $pagination = null;
        }

        // OrderBy
        $order = array();
        if ($order_by_used AND Tools::getValue($helper->table.'Orderway')) {
            $order_by = Tools::getValue($helper->table.'Orderby');

            $order['order_by']  = $order_by;
            $order['order_way'] = Tools::getValue($helper->table.'Orderway');
            if (!empty($fields_list[$order_by]['alias'])) {
                $order['alias'] = $fields_list[$order_by]['alias'];
            }
            $this->context->cookie->{$helper->table.'Orderby'}  = Tools::getValue($helper->table.'Orderby');
            $this->context->cookie->{$helper->table.'Orderway'} = Tools::getValue($helper->table.'Orderway');
        }
        elseif (!empty($this->context->cookie->{$helper->table.'Orderway'})) {
            $order['order_by']  = $this->context->cookie->{$helper->table.'Orderby'};
            $order['order_way'] = $this->context->cookie->{$helper->table.'Orderway'};
        }

        // Set Final Settings
        $helper->listTotal = Level::getTotalLevels($filters);

        switch ($condition_type) {
            case 'total' : $helper->title = $this->l('All Levels by').' '. $this->total_name;
                break;
            case 'points' : $helper->title = $this->l('All Levels by Points');
                break;
            case 'coins' : $helper->title = $this->l('All Levels by Coins');
                break;
            case 'action' : $helper->title = $this->l('All Levels by Actions');
                break;
        }

        $values = Level::getAllLevels($filters, $pagination, $order);

        return $helper->generateList($values, $fields_list);
    }

    private function generateListCoupons() {

        $fields_list = array(
            'id_cart_rule' => array(
                'title' => 'ID',
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'filter_type' => 'int',
            ),
            'name' => array(
                'title' => $this->l('Name'),
                'align' => 'left',
                'alias' => 'l'
            ),
            'level' => array(
                'title' => $this->l('Used in Levels'),
                'align' => 'left',
                'type'  => 'text',
                'havingFilter' => false,
            ),
        );

        $helper = new HelperList();
        $helper->shopLinkType = '';
        $helper->actions = array('edit');
        $helper->identifier = 'id_cart_rule';
        $helper->table = 'Coupon';
        $helper->_pagination = [20,50,100];
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $index = '&configure=' . $this->name . '&module_name=' . $this->name . '&content=ListCoupons';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . $index;


        // Filter, Pagination and Oder_By -> get Situation
        (Tools::isSubmit('submitFilter')) ? $filter_used = true : $filter_used = false;
        (Tools::isSubmit('submitFilter'.$helper->table)) ? $pagination_used = true : $pagination_used = false;
        (Tools::getValue($helper->table.'Orderby')) ? $order_by_used = true : $order_by_used = false;

        // Filter
        $filters = null;
        if ($filter_used OR $pagination_used) {
            $filters = $this->getFiltersFromList($fields_list, $helper->table);
        }

        if (Tools::isSubmit('submitReset'.$helper->table)) {
            foreach ($fields_list as $fieldName => $field) {
                unset($_POST[$helper->table.'Filter_'.$fieldName]);
                unset($_GET[$helper->table.'Filter_'.$fieldName]);
            }
            $filters = null;
        }

        // Pagination
        if ($pagination_used) {
            $pagination = $this->getPagination($helper->table);
        }
        elseif ($helper->_default_pagination) {
            $pagination['limit'] = $helper->_default_pagination;
            $pagination['offset'] = 0;
        }
        else {
            $pagination = null;
        }

        // OrderBy
        $order = array();
        if ($order_by_used AND Tools::getValue($helper->table.'Orderway')) {
            $order_by = Tools::getValue($helper->table.'Orderby');

            $order['order_by']  = $order_by;
            $order['order_way'] = Tools::getValue($helper->table.'Orderway');
            if (!empty($fields_list[$order_by]['alias'])) {
                $order['alias'] = $fields_list[$order_by]['alias'];
            }
            $this->context->cookie->{$helper->table.'Orderby'}  = Tools::getValue($helper->table.'Orderby');
            $this->context->cookie->{$helper->table.'Orderway'} = Tools::getValue($helper->table.'Orderway');
        }
        elseif (!empty($this->context->cookie->{$helper->table.'Orderway'})) {
            $order['order_by']  = $this->context->cookie->{$helper->table.'Orderby'};
            $order['order_way'] = $this->context->cookie->{$helper->table.'Orderway'};
        }

        // Set Final Settings
        $helper->listTotal = Coupon::getTotalActions($filters);
        $helper->title = $this->l('All Coupons');

        $values = Coupon::getAllCoupons($filters, $pagination, $order);

        return $helper->generateList($values, $fields_list);
    }

    private function getGroupsPriority() {

	    $id_lang = $this->context->language->id;

        $query = new DbQuery();
        $query->select('g.id_group, g.name, s.position');
        $query->from('group_lang', 'g');
        $query->leftJoin($this->table_name . '_settings_group', 's', 's.id_group = g.id_group');
        $query->where('id_lang = ' . (int)$id_lang);
        $query->orderBy('position ASC');
        return Db::getInstance()->ExecuteS($query);
    }

    private function getFiltersFromList($fields_list, $table_name) {

	    $filters = array();

	    foreach ($fields_list as $key => $values) {

            (isset($values['filter_key'])) ? $filter_key = $values['filter_key'] : $filter_key = $key;

            $filter_value = Tools::getValue($table_name.'Filter_' . $filter_key);

            if ($filter_value!='') {

                $key = '`'.$key.'`';

                // Add the Alias
                if (!empty($values['alias'])) {
                    $key = $values['alias'] . '.' . $key;
                }

                // Generate the where clause, depending on filter_type
                if(isset($values['filter_type']) AND $values['filter_type']=='int') {
                    $filter_value = (int)$filter_value;
                    $where = " {$key} = {$filter_value} ";
                }
                else {
                    $filter_value = pSQL($filter_value);
                    $where = " {$key} LIKE '%{$filter_value}%' ";
                }
                $filters[] = $where;
            }
        }

        return $filters;
    }

    private function getPagination($tableName) {
        /* Determine current page number */
        $page = (int) Tools::getValue('submitFilter'.$tableName);
        if (!$page) {
            $page = 1;
        }

        $selectedPagination = Tools::getValue($tableName.'_pagination',
            isset($this->context->cookie->{$tableName.'_pagination'}) ? $this->context->cookie->{$tableName.'_pagination'} : 20
        );

        $this->context->cookie->{$tableName.'_pagination'} = $selectedPagination; // Save the cookie for later

        $pagination['limit']  = $selectedPagination;
        $pagination['offset'] = ($page-1) * $selectedPagination;

        return $pagination;
    }


    // Forms
    private function generateFormAction() {

	    $id_action = Tools::getValue('id_action');

        // Get Values
        $action = new Action($id_action);
        $vars = json_decode(json_encode($action), true); // Turns an object into an array

	    // Check for Inbuilt functions
        ($id_action == Action::getIdAction('genzo_krona', 'order')) ? $order = true : $order = false;
        ($id_action == Action::getIdAction('genzo_krona', 'newsletter')) ? $newsletter = true : $newsletter = false;

        $inputs[] = array(
            'type' => 'hidden',
            'name' => 'id_action'
        );
        $inputs[] = array(
            'type' => 'switch',
            'label' => $this->l('Active'),
            'name' => 'active',
            'values' => array(
                array(
                    'id' => 'active_on',
                    'value' => 1,
                    'label' => $this->l('Yes')
                ),
                array(
                    'id' => 'active_off',
                    'value' => 0,
                    'label' => $this->l('No')
                )
            ),
        );
        $inputs[] = array(
            'type'  => 'text',
            'name'  => 'title',
            'label' => $this->l('Title'),
            'lang'  => true,
        );

        if ($order) {
            $message_desc = $this->l('You can use:'). ' {points} {reference} {amount}';
        }
        else {
            $message_desc = $this->l('You can use:'). ' {points}';
        }

        $inputs[] = array(
            'type' => 'text',
            'label' => $this->l('Message'),
            'name' => 'message',
            'desc' => $message_desc,
            'lang' => true,
        );
        $inputs[] =array(
            'type' => 'select',
            'label' => $this->l('Execution Type'),
            'name' => 'execution_type',
            'options' => array(
                'query' => array(
                    array('value' => 'unlimited', 'name' => $this->l('Unlimited')),
                    array('value' => 'per_lifetime', 'name' => $this->l('Max Per Lifetime')),
                    array('value' => 'per_year', 'name' => $this->l('Max Per Year')),
                    array('value' => 'per_month', 'name' => $this->l('Max Per Month')),
                    array('value' => 'per_day', 'name' => $this->l('Max Per Day')),
                ),
                'id' => 'value',
                'name' => 'name',
            ),
        );
        $inputs[] = array(
            'type'  => 'text',
            'name'  => 'execution_max',
            'label' => $this->l('Execution Max'),
            'class'  => 'input fixed-width-sm',
        );


        if ($newsletter) {
            $points_desc = $this->l('Newsletter will be auto triggered by CronJob. For example every month a customer receives x amount of points. It\'s recommended
                                            to use execution type per Year, per Month or per Day.');
        }
        else {
            $points_desc = '';
        }

        $inputs[] = array(
            'type'  => 'text',
            'name'  => 'points_change',
            'label' => $this->l('Points Change'),
            'desc'  => $points_desc,
            'class'  => 'input fixed-width-sm',
            'suffix' => $this->total_name,
        );

        if ($this->is_multishop) {
            $inputs[] = array(
                'type' => 'shop',
                'label' => $this->l('Shop association:'),
                'name' => 'checkBoxShopAsso',
            );
        }

        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Edit Action'),
                    'icon' => 'icon-cogs'
                ),
                'input' => $inputs,
                'submit' => array(
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right'
                )
            )
        );

        $helper = new HelperForm();
        $helper->submit_action = 'saveAction';
        $helper->default_form_language = $this->context->language->id;
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name .'&module_name=';
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->id = $id_action;
        $helper->table = 'genzo_krona_action';
        $helper->identifier = 'id_action';

        $helper->tpl_vars = array(
            'fields_value' => $vars,
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($fields_form));

    }

    private function generateFormOrder() {

	    $id_action_order = Tools::getValue('id_action_order');

        // Get Values
        $actionOrder = new ActionOrder($id_action_order);
        $vars = json_decode(json_encode($actionOrder), true); // Turns an object into an array


        $inputs[] = array(
            'type' => 'hidden',
            'name' => 'id_action_order'
        );
        $inputs[] = array(
            'type'         => 'html',
            'name'         => 'html_note',
            'html_content' => "<p>{$this->l('Note: The other settings like order status, title or message can be set globaly under \"Settings\".')}</p>",
        );
        $inputs[] = array(
            'type' => 'switch',
            'label' => $this->l('Active'),
            'name' => 'active',
            'values' => array(
                array(
                    'id' => 'active_on',
                    'value' => 1,
                    'label' => $this->l('Yes')
                ),
                array(
                    'id' => 'active_off',
                    'value' => 0,
                    'label' => $this->l('No')
                )
            ),
        );
        $inputs[] = array(
            'type'  => 'text',
            'name'  => 'coins_change',
            'label' => $this->l('Coins reward'),
            'desc'  => sprintf($this->l('Example: For every %s spent, the user will get X coins.'),$actionOrder->currency),
            'class'  => 'input fixed-width-sm',
            'suffix' => $this->l('Coins').'/'.$actionOrder->currency_iso,
        );
        $inputs[] = array(
            'type'  => 'text',
            'name'  => 'minimum_amount',
            'label' => $this->l('Minimum Amount'),
            'desc'  => sprintf($this->l('Needs there to be a minimum amount of %s to get coins? If not, set it equal to 0.'), $actionOrder->currency),
            'class'  => 'input fixed-width-sm',
            'suffix' => $actionOrder->currency_iso,
        );
        $inputs[] = array(
            'type'  => 'text',
            'name'  => 'coins_conversion',
            'label' => $this->l('Coins conversion'),
            'desc'  => sprintf($this->l('Example: For every coin a customer collected, he can generate a voucher with the value of X %s.'),$actionOrder->currency),
            'class'  => 'input fixed-width-sm',
            'suffix' => $actionOrder->currency_iso.'/'.$this->l('Coins'),
        );

        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Edit Order Action:'). ' '. $actionOrder->currency,
                    'icon' => 'icon-cogs'
                ),
                'input' => $inputs,
                'submit' => array(
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right'
                )
            )
        );

        $helper = new HelperForm();
        $helper->submit_action = 'saveActionOrder';
        $helper->default_form_language = $this->context->language->id;
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name .'&module_name=';
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->id = $id_action_order;
        $helper->table = 'genzo_krona_action_order';
        $helper->identifier = 'id_action_order';


        $helper->tpl_vars = array(
            'fields_value' => $vars,
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($fields_form));

    }

    private function generateFormCustomAction($data = null) {
        $id_customer = (int)Tools::getValue('id_customer');

        $inputs[] = array(
            'type' => 'hidden',
            'name' => 'id_customer'
        );
        $inputs[] =array(
            'type' => 'select',
            'label' => $this->l('Type'),
            'name' => 'action_type',
            'options' => array(
                'query' => array(
                    array('value' => 'custom', 'name' => $this->l('Custom')),
                    array('value' => 'action', 'name' => $this->l('Action')),
                    array('value' => 'order', 'name' => $this->l('Order')),
                ),
                'id' => 'value',
                'name' => 'name',
            ),
        );

        $inputs[] = array(
            'type'  => 'text',
            'name'  => 'title',
            'label' => $this->l('Title'),
            'lang'  => true,
        );

        $inputs[] = array(
            'type' => 'text',
            'label' => $this->l('Message'),
            'name' => 'message',
            'lang' => true,
        );

        $inputs[] = array(
            'type' => 'select',
            'label' => $this->l('Action'),
            'name' => 'id_action',
            'class' => 'chosen',
            'options' => array(
                'query' => Action::getAllActions(),
                'id' => 'id_action',
                'name' => 'title',
            ),
        );
        $inputs[] = array(
            'type' => 'select',
            'label' => $this->l('Order'),
            'name' => 'id_action_order',
            'class' => 'chosen',
            'options' => array(
                'query' => ActionOrder::getAllActionOrder(),
                'id' => 'id_action_order',
                'name' => 'name',
            ),
        );

        $inputs[] = array(
            'type'  => 'text',
            'name'  => 'points_change',
            'label' => $this->l('Change'),
            'desc'  => $this->l('If you wanna give a penalty you can set -10 for example.'),
            'class'  => 'input fixed-width-sm',
            'suffix' => $this->l('Points'),
        );
        $inputs[] = array(
            'type'  => 'text',
            'name'  => 'coins_change',
            'label' => $this->l('Change'),
            'desc'  => $this->l('If you wanna give a penalty you can set -10 for example.'),
            'class'  => 'input fixed-width-sm',
            'suffix' => $this->l('Coins'),
        );

        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Add Custom Action'),
                    'icon' => 'icon-cogs'
                ),
                'input' => $inputs,
                'submit' => array(
                    'title' => $this->l('Save Custom Action'),
                    'class' => 'btn btn-default pull-right'
                )
            )
        );

        $helper = new HelperForm();
        $helper->submit_action = 'saveCustomAction';
        $helper->default_form_language = $this->context->language->id;
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name .'&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->table = 'genzo_krona_custom_action';


        // Get Values
        $vars['id_customer'] = $id_customer;

        if ($data) {
            $vars = json_decode(json_encode($data), true);
            if (empty($vars['points_change'])) {$vars['points_change'] = '';}
            if (empty($vars['coins_change'])) {$vars['coins_change'] = '';}
        }
        else {
            foreach (Language::getIDs() as $id_lang) {
                $vars['title'][$id_lang] = '';
                $vars['message'][$id_lang] = '';
            }

            $vars['action_type'] = 'custom';
            $vars['id_action'] = 1;
            $vars['id_action_order'] = 1;
            $vars['points_change'] = '';
            $vars['coins_change'] = '';
        }

        $helper->tpl_vars = array(
            'fields_value' => $vars,
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($fields_form));

    }

    private function generateFormPlayer() {

        $id_customer = (int)Tools::getValue('id_customer');

	    $id_lang = $this->context->language->id;

        $inputs[] = array(
            'type' => 'hidden',
            'name' => 'id_customer'
        );
        $inputs[] = array(
            'type' => 'switch',
            'label' => $this->l('Active'),
            'name' => 'active',
            'values' => array(
                array(
                    'id' => 'active_on',
                    'value' => 1,
                    'label' => $this->l('Yes')
                ),
                array(
                    'id' => 'active_off',
                    'value' => 0,
                    'label' => $this->l('No')
                )
            ),
        );
        $inputs[] = array(
            'type' => 'switch',
            'label' => $this->l('Banned'),
            'name' => 'banned',
            'values' => array(
                array(
                    'id' => 'active_on',
                    'value' => 1,
                    'label' => $this->l('Yes')
                ),
                array(
                    'id' => 'active_off',
                    'value' => 0,
                    'label' => $this->l('No')
                )
            ),
        );

        $inputs[] = array(
            'type' => (Configuration::get('krona_pseudonym', null, $this->id_shop_group, $this->id_shop)) ? 'text' : 'hidden',
            'name' => 'pseudonym',
            'label' => $this->l('Pseudonym'),
        );


        $avatar = Player::getAvatar($id_customer);

        $inputs[] = array(
            'type'         => 'html',
            'name'         => 'html_avatar',
            'html_content' => "<img src='{$avatar}' width='70' height='70' />",
        );

        $inputs[] = array(
            'type'  => 'file',
            'label' => 'Avatar',
            'name'  => 'avatar',
        );
        // We shouldn't change points this way, since it will not generate any history for the player. This will cause troubles, when checking points in levels.
        $inputs[] = array(
            'type'  => 'text',
            'name'  => 'points',
            'readonly' => true,
            'desc' => $this->l('If you want to change points, please add a custom action below.'),
            'label' => $this->l('Points'),
            'class'  => 'input fixed-width-sm',
        );
        $inputs[] = array(
            'type'  => 'text',
            'name'  => 'coins',
            'readonly' => true,
            'desc' => $this->l('If you want to change coins, please add a custom action below.'),
            'label' => $this->l('Coins'),
            'class'  => 'input fixed-width-sm',
        );
        $inputs[] = array(
            'type'  => 'text',
            'name'  => 'total',
            'readonly' => true,
            'desc' => $this->l('If you want to change total, please add a custom action below.'),
            'label' => $this->total_name,
            'class'  => 'input fixed-width-sm',
        );
        $inputs[] = array(
            'type'  => 'text',
            'name'  => 'loyalty',
            'readonly' => true,
            'desc' => $this->l('If you want to change loyalty, please add a custom action below.'),
            'label' => $this->loyalty_name,
            'class'  => 'input fixed-width-sm',
        );

        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Edit Player'),
                    'icon' => 'icon-cogs',
                ),
                'input' => $inputs,
                'submit' => array(
                    'title' => $this->l('Save Player'),
                    'class' => 'btn btn-default pull-right',
                )
            )
        );

        $helper = new HelperForm();
        $helper->submit_action = 'savePlayer';
        $helper->default_form_language = $id_lang;
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name .'&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->id = $id_customer;
        $helper->table = 'genzo_krona_player';
        $helper->identifier = 'id_customer';

        // Get Values
        $player = new Player($id_customer);
        $vars = json_decode(json_encode($player), true); // Turns an object into an array

        $helper->tpl_vars = array(
            'fields_value' => $vars,
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($fields_form));

    }

    private function generateFormLevel($data = null) {

        $id_level = (int)Tools::getValue('id_level');
	    $id_lang = $this->context->language->id;

        // Get Values
        ($data) ? $level = $data : $level = new Level($id_level);

        $vars = json_decode(json_encode($level), true); // Turns an object into an array

        $inputs[] = array(
            'type' => 'hidden',
            'name' => 'id_level'
        );
        $inputs[] = array(
            'type' => 'switch',
            'label' => $this->l('Active'),
            'name' => 'active',
            'values' => array(
                array(
                    'id' => 'active_on',
                    'value' => 1,
                ),
                array(
                    'id' => 'active_off',
                    'value' => 0,
                )
            ),
        );
        $inputs[] = array(
            'type'  => 'text',
            'name'  => 'name',
            'label' => $this->l('Level Name'),
            'lang' => true,
        );
        $inputs[] =array(
            'type' => 'select',
            'label' => $this->l('Condition Type'),
            'name' => 'condition_type',
            'options' => array(
                'query' => array(
                    array('value' => 'total', 'name' => $this->l('Threshold:') . ' ' . $this->total_name),
                    array('value' => 'points', 'name' => $this->l('Threshold:') . ' '. $this->l('Points')),
                    array('value' => 'coins', 'name' => $this->l('Threshold:') . ' ' . $this->l('Coins')),
                    array('value' => 'action', 'name' => $this->l('Executing Action')), // Todo: Executing Order
                ),
                'id' => 'value',
                'name' => 'name',
            ),
        );

        $inputs[] =array(
            'type' => 'select',
            'label' => $this->l('Action'),
            'name' => 'id_action',
            'class' => 'chosen',
            'options' => array(
                'query' => Action::getAllActions(array('active=1')),
                'id' => 'id_action',
                'name' => 'title',
            ),
        );

        $inputs[] = array(
            'type'  => 'text',
            'name'  => 'condition_points',
            'label' => $this->l('Condition'),
            'suffix'=> $this->total_name,
            'desc' => $this->l('Threshold: How many points need to be reached?'),
            'class'  => 'input fixed-width-sm',
        );
        $inputs[] = array(
            'type'  => 'text',
            'name'  => 'condition_action',
            'label' => $this->l('Condition'),
            'suffix'=> $this->l('Executing Times'),
            'desc' => $this->l('Action: How many times has the action to be executed?'),
            'class'  => 'input fixed-width-sm',
        );
        $inputs[] = array(
            'type'   => 'text',
            'name'   => 'condition_time',
            'class'  => 'input fixed-width-sm',
            'label'   => $this->l('Condition time span'),
            'desc'   => $this->l('Specify how many days the user has time, to fulfill the condition. If you set the value to 7, 
                                        the module will check the PlayerHistory of the last 7 days. Set the value to 0 for unlimited time.'),
            'suffix' => $this->l('Days'),
        );
        $inputs[] = array(
            'type'   => 'text',
            'name'   => 'duration',
            'class'  => 'input fixed-width-sm',
            'label'   => $this->l('Duration of Reward'),
            'desc'   => $this->l('How long will the user stay on this level? If you set it to 365, the user will be one year on this level. Set the value to 0, if the user should never lose this level.'),
            'suffix' => $this->l('Days'),
        );
        $inputs[] =array(
            'type' => 'select',
            'label' => $this->l('Reward Type'),
            'name' => 'reward_type',
            'options' => array(
                'query' => array(
                    array('value' => 'symbolic', 'name' => $this->l('Symbolic')),
                    array('value' => 'coupon', 'name' => $this->l('Coupon')),
                    array('value' => 'group', 'name' => $this->l('Customer Group')),
                ),
                'id' => 'value',
                'name' => 'name',
            ),
        );
        $inputs[] =array(
            'type' => 'select',
            'label' => $this->l('Coupon'),
            'name' => 'id_reward_coupon',
            'desc' => $this->l('The customer will get this coupon, when he reaches this level.'),
            'options' => array(
                'query' => Coupon::getAllCoupons(),
                'id' => 'id_cart_rule',
                'name' => 'name',
            ),
        );
        $inputs[] =array(
            'type' => 'select',
            'label' => $this->l('Customer Group'),
            'name' => 'id_reward_group',
            'desc' => $this->l('The customer will change his group to the selected one.'),
            'options' => array(
                'query' => GroupCore::getGroups($id_lang),
                'id' => 'id_group',
                'name' => 'name',
            ),
        );
        $inputs[] = array(
            'type'  => 'text',
            'label' => $this->l('Achieve max'),
            'desc' => $this->l('How often can an user achieve this level? This option is interesting, if reward duration ist not unlimited.'),
            'name'  => 'achieve_max',
            'class'  => 'input fixed-width-sm',
            'suffix' => $this->l('Times'),
        );
        if ($id_level) {
            $inputs[] = array(
                'type' => 'html',
                'name' => 'html_icon',
                'html_content' => "<img src='/modules/genzo_krona/views/img/icon/{$vars['icon']}' width='30' height='30' />",
            );
        }
        $inputs[] = array(
            'type'  => 'file',
            'label' => 'Icon',
            'name'  => 'icon',
        );
        if ($this->is_multishop) {
            $inputs[] = array(
                'type' => 'shop',
                'label' => $this->l('Shop association:'),
                'name' => 'checkBoxShopAsso',
            );
        }

        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => ($id_level) ? $this->l('Edit Level') : $this->l('Add new Level'),
                    'icon' => 'icon-cogs'
                ),
                'input' => $inputs,
                'submit' => array(
                    'title' => $this->l('Save Level'),
                    'class' => 'btn btn-default pull-right'
                )
            )
        );

        $helper = new HelperForm();
        $helper->submit_action = 'saveLevel';
        $helper->default_form_language = $id_lang;
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name .'&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->id = $id_level;
        $helper->table = 'genzo_krona_level';
        $helper->identifier = 'id_level';

        // Fix of values since we dont use always same names
        $vars['condition_points'] = $vars['condition'];
        $vars['condition_action'] = $vars['condition'];
        $vars['id_reward_coupon'] = $vars['id_reward'];
        $vars['id_reward_group'] = $vars['id_reward'];

        $helper->tpl_vars = array(
            'fields_value' => $vars,
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($fields_form));

    }

    private function generateFormSettings() {

	    $id_lang = $this->context->language->id;
	    $loyalty = Configuration::get('krona_loyalty_active', null, $this->id_shop_group, $this->id_shop);
	    $gamifaction = Configuration::get('krona_gamification_active', null, $this->id_shop_group, $this->id_shop);

	    // FrontOffice General
        $inputs[] = array(
            'type'         => 'html',
            'name'         => 'html_front',
            'html_content' => "<h2>{$this->l('General')}</h2>",
        );
        $inputs[] = array(
            'type' => 'switch',
            'label' => $this->l('Loyalty Activation'),
            'desc' => $this->l('Do you want to let the customer, convert loyalty points into coupons?'),
            'name' => 'loyalty_active',
            'values' => array(
                array(
                    'id' => 'active_on',
                    'value' => 1,
                    'label' => $this->l('Yes')
                ),
                array(
                    'id' => 'active_off',
                    'value' => 0,
                    'label' => $this->l('No')
                )
            ),
        );
        $inputs[] =array(
            'type' => 'select',
            'label' => $this->l('Loyalty Total Value'),
            'name' => 'loyalty_total',
            'desc' => $this->l('Points are collected by actions like: newsletter registration, reviews etc. Coins are collected by placing orders.'),
            'options' => array(
                'query' => array(
                    array('value' => 'points_coins', 'name' => $this->l('Points + Coins')),
                    array('value' => 'points', 'name' => $this->l('Points')),
                    array('value' => 'coins', 'name' => $this->l('Coins')),
                ),
                'id' => 'value',
                'name' => 'name',
            ),
        );

        $inputs[] = array(
            'type' => 'switch',
            'label' => $this->l('Gamification Activation'),
            'desc' => $this->l('Do you want to use gamifacation with leaderboard, avatar, pseudonym etc.'),
            'name' => 'gamification_active',
            'values' => array(
                array(
                    'id' => 'active_on',
                    'value' => 1,
                    'label' => $this->l('Yes')
                ),
                array(
                    'id' => 'active_off',
                    'value' => 0,
                    'label' => $this->l('No')
                )
            ),
        );
        $inputs[] =array(
            'type' => 'select',
            'label' => $this->l('Gamification Total Value'),
            'name' => 'gamification_total',
            'desc' => $this->l('Points are collected by actions like: newsletter registration, reviews etc. Coins are collected by placing orders.'),
            'options' => array(
                'query' => array(
                    array('value' => 'points_coins', 'name' => $this->l('Points + Coins')),
                    array('value' => 'points', 'name' => $this->l('Points')),
                    array('value' => 'coins', 'name' => $this->l('Coins')),
                ),
                'id' => 'value',
                'name' => 'name',
            ),
        );

        // FrontOffice Settings
        $inputs[] = array(
            'type'         => 'html',
            'name'         => 'html_front',
            'html_content' => "<h2>{$this->l('Front Office')}</h2>",
        );
        $inputs[] = array(
            'type'  => 'text',
            'name'  => 'url',
            'label' => $this->l('Url'),
            'desc'  => $this->l('The url in the frontoffice will look like: domain.com/url'),
        );
        if ($loyalty) {
            $inputs[] = array(
                'type' => 'text',
                'name' => 'game_name',
                'label' => $this->l('Name'),
                'desc' => $this->l('What is the name of your loyalty system?'),
                'lang' => true,
            );
        }
        if ($loyalty) {
            $inputs[] = array(
                'type' => 'text',
                'name' => 'loyalty_name',
                'label' => $this->l('Loyalty Points Name'),
                'desc' => $this->l('Loyalty points are the points, which can be converted directly into coupons.'),
                'lang' => true,
            );
        }
        if ($gamifaction) {
            $inputs[] = array(
                'type' => 'text',
                'name' => 'total_name',
                'label' => $this->l('Gamification Points Name'),
                'desc' => $this->l('How shall the total points be called? You could consider total points as "Lifetime points".'),
                'lang' => true,
            );
        }
        $inputs[] = array(
            'type' => 'switch',
            'label' => $this->l('Customer Activation'),
            'desc' => $this->l('Do you want to set the customer active right after registration?'),
            'name' => 'customer_active',
            'values' => array(
                array(
                    'id' => 'active_on',
                    'value' => 1,
                    'label' => $this->l('Yes')
                ),
                array(
                    'id' => 'active_off',
                    'value' => 0,
                    'label' => $this->l('No')
                )
            ),
        );
        if ($gamifaction) {
            $inputs[] = array(
                'type' => 'select',
                'label' => $this->l('Default Display Name'),
                'name' => 'display_name',
                'options' => array(
                    'query' => array(
                        array('value' => 1, 'name' => $this->l('John Doe')),
                        array('value' => 2, 'name' => $this->l('John D.')),
                        array('value' => 3, 'name' => $this->l('J. Doe')),
                        array('value' => 4, 'name' => $this->l('J. D.')),
                        array('value' => 5, 'name' => $this->l('John')),
                    ),
                    'id' => 'value',
                    'name' => 'name',
                ),
            );
        }
        if ($gamifaction) {
            $inputs[] = array(
                'type' => 'switch',
                'label' => $this->l('Pseudonym'),
                'desc' => $this->l('Are customers allowed to set a pseudonym as display name?'),
                'name' => 'pseudonym',
                'values' => array(
                    array(
                        'id' => 'active_on',
                        'value' => 1,
                        'label' => $this->l('Yes')
                    ),
                    array(
                        'id' => 'active_off',
                        'value' => 0,
                        'label' => $this->l('No')
                    )
                ),
            );
        }
        if ($gamifaction) {
            $inputs[] = array(
                'type' => 'switch',
                'label' => $this->l('Avatar'),
                'desc' => $this->l('Are customers allowed to upload an avatar?'),
                'name' => 'avatar',
                'values' => array(
                    array(
                        'id' => 'active_on',
                        'value' => 1,
                        'label' => $this->l('Yes')
                    ),
                    array(
                        'id' => 'active_off',
                        'value' => 0,
                        'label' => $this->l('No')
                    )
                ),
            );
        }
        $inputs[] = array(
            'type'  => 'textarea',
            'name'  => 'home_description',
            'label' => $this->l('Home content'),
            'desc' => $this->l('Describe your loyality game here.'),
            'lang'  => true,
            'autoload_rte' => true,
        );

        // Action: Order
        $inputs[] = array(
            'type'         => 'html',
            'name'         => 'html_coupon',
            'html_content' => "<br><h2>{$this->l('Coins for orders')}</h2>",
        );
        $inputs[] =array(
            'type' => 'select',
            'label' => $this->l('Total Amount'),
            'desc' => $this->l('Which total amount should be transformed into coins?'),
            'name' => 'order_amount',
            'options' => array(
                'query' => array(
                    array('value' => 'total_wt', 'name' => $this->l('Products + Shipping with tax')),
                    array('value' => 'total', 'name' => $this->l('Products + Shipping without tax')),
                    array('value' => 'total_products_wt', 'name' => $this->l('Products with tax')),
                    array('value' => 'total_products', 'name' => $this->l('Products without tax')),
                ),
                'id' => 'value',
                'name' => 'name',
            ),
        );
        $inputs[] = array(
            'type' => 'select',
            'label' => $this->l('Rounding'),
            'desc' => $this->l('Will a value of 8.90 become 9 or 8?'),
            'name' => 'order_rounding',
            'options' => array(
                'query' => array(
                    array('value' => 'up', 'name' => $this->l('Up')),
                    array('value' => 'down', 'name' => $this->l('Down')),
                ),
                'id' => 'value',
                'name' => 'name',
            ),
        );

        $orderStates = OrderState::getOrderStates($id_lang);

        $inputs[] = array(
            'type'     => 'checkbox',
            'label'    => $this->l('Order State'),
            'desc'     => $this->l('On which order state will the order be transformed into coins?'),
            'name'     => 'order_state',
            'multiple' => true,
            'values'   => [
                'query' => $orderStates,
                'id'    => 'id_order_state',
                'name'  => 'name',
            ],
            'expand'   => (count($orderStates) > 10) ? [
                'print_total' => count($orderStates),
                'default'     => 'show',
                'show'        => ['text' => $this->l('Show'), 'icon' => 'plus-sign-alt'],
                'hide'        => ['text' => $this->l('Hide'), 'icon' => 'minus-sign-alt'],
            ] : null,
        );
        $inputs[] = array(
            'type'     => 'checkbox',
            'label'    => $this->l('Cancel Order State'),
            'desc'     => $this->l('On which order state should the coins be taken back?'),
            'name'     => 'order_state_cancel',
            'multiple' => true,
            'values'   => [
                'query' => $orderStates,
                'id'    => 'id_order_state',
                'name'  => 'name',
            ],
            'expand'   => (count($orderStates) > 10) ? [
                'print_total' => count($orderStates),
                'default'     => 'show',
                'show'        => ['text' => $this->l('Show'), 'icon' => 'plus-sign-alt'],
                'hide'        => ['text' => $this->l('Hide'), 'icon' => 'minus-sign-alt'],
            ] : null,
        );


        $inputs[] = array(
            'type'  => 'text',
            'name'  => 'order_title',
            'label' => $this->l('Title order'),
            'desc'  => $this->l('The user will see title and message in Front Office.'),
            'lang'  => true,
        );
        $inputs[] = array(
            'type'  => 'text',
            'name'  => 'order_message',
            'label' => $this->l('Message order'),
            'desc'  => $this->l('You can use:'). ' {coins} {reference} {amount}',
            'lang'  => true,
        );
        $inputs[] = array(
            'type'  => 'text',
            'name'  => 'order_canceled_title',
            'label' => $this->l('Title canceled order'),
            'desc'  => $this->l('The user will see title and message in Front Office.'),
            'lang'  => true,
        );
        $inputs[] = array(
            'type'  => 'text',
            'name'  => 'order_canceled_message',
            'label' => $this->l('Message canceled order'),
            'desc'  => $this->l('You can use:'). ' {coins} {reference} {amount}',
            'lang'  => true,
        );

        // Coupons
        $inputs[] = array(
            'type'         => 'html',
            'name'         => 'html_coupon',
            'html_content' => "<br><h2>{$this->l('Coupons')}</h2>",
        );
        $inputs[] = array(
            'type'         => 'text',
            'name'         => 'coupon_prefix',
            'label'     => $this->l('Coupon prefix'),
            'class'  => 'input fixed-width-sm',
            'desc' => $this->l('Prefix is optional. The Coupon will look like: Prefix-Code'),
        );


        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs'
                ),
                'input' => $inputs,
                'submit' => array(
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right'
                )
            )
        );

        $helper = new HelperForm();
        $helper->submit_action = 'saveSettings';
        $helper->default_form_language = $this->context->language->id;
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name .'&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->table = 'genzo_krona_settings';

        // Get Values
        $ids_lang = Language::getIDs();
        foreach ($ids_lang as $id_lang) {
            $vars['total_name'][$id_lang] = Configuration::get('krona_total_name', $id_lang, $this->id_shop_group, $this->id_shop);
            $vars['loyalty_name'][$id_lang] = Configuration::get('krona_loyalty_name', $id_lang, $this->id_shop_group, $this->id_shop);
            $vars['game_name'][$id_lang] = Configuration::get('krona_game_name', $id_lang, $this->id_shop_group, $this->id_shop);
            $vars['order_title'][$id_lang] = Configuration::get('krona_order_title', $id_lang, $this->id_shop_group, $this->id_shop);
            $vars['order_message'][$id_lang] = Configuration::get('krona_order_message', $id_lang, $this->id_shop_group, $this->id_shop);
            $vars['order_canceled_title'][$id_lang] = Configuration::get('krona_order_canceled_title', $id_lang, $this->id_shop_group, $this->id_shop);
            $vars['order_canceled_message'][$id_lang] = Configuration::get('krona_order_canceled_message', $id_lang, $this->id_shop_group, $this->id_shop);
            $vars['home_description'][$id_lang] = Configuration::get('krona_description', $id_lang, $this->id_shop_group, $this->id_shop);
        }

        $vars['loyalty_active'] = Configuration::get('krona_loyalty_active', null, $this->id_shop_group, $this->id_shop);
        $vars['loyalty_total'] = Configuration::get('krona_loyalty_total', null, $this->id_shop_group, $this->id_shop);
        $vars['gamification_active'] = Configuration::get('krona_gamification_active', null, $this->id_shop_group, $this->id_shop);
        $vars['gamification_total'] = Configuration::get('krona_gamification_total', null, $this->id_shop_group, $this->id_shop);
        $vars['url'] = Configuration::get('krona_url', null, $this->id_shop_group, $this->id_shop);
        $vars['customer_active'] = Configuration::get('krona_customer_active', null, $this->id_shop_group, $this->id_shop);
        $vars['display_name'] = Configuration::get('krona_display_name', null, $this->id_shop_group, $this->id_shop);
        $vars['pseudonym'] = Configuration::get('krona_pseudonym', null, $this->id_shop_group, $this->id_shop);
        $vars['avatar'] = Configuration::get('krona_avatar', null, $this->id_shop_group, $this->id_shop);
        $vars['order_amount'] = Configuration::get('krona_order_amount', null, $this->id_shop_group, $this->id_shop);
        $vars['order_rounding'] = Configuration::get('krona_order_rounding', null, $this->id_shop_group, $this->id_shop);
        $vars['order_state'] = Configuration::get('krona_order_state', null, $this->id_shop_group, $this->id_shop);
        $vars['order_state_cancel'] = Configuration::get('krona_order_state_cancel', null, $this->id_shop_group, $this->id_shop);
        $vars['coupon_prefix'] = Configuration::get('krona_coupon_prefix', null, $this->id_shop_group, $this->id_shop);

        // Order Status
        $order_state = explode(',', Configuration::get('krona_order_state', null, $this->id_shop_group, $this->id_shop));
        foreach ($order_state as $id) {
            $vars['order_state_'.$id] = true;
        }
        $order_state_cancel = explode(',', Configuration::get('krona_order_state_cancel', null, $this->id_shop_group, $this->id_shop));
        foreach ($order_state_cancel as $id) {
            $vars['order_state_cancel_'.$id] = true;
        }

        $helper->tpl_vars = array(
            'fields_value' => $vars,
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($fields_form));

    }


    // Saving
    private function registerAction($module_name, $action_name) {

        $module_name = pSQL($module_name);
        $action_name = pSQL($action_name);

        // We prevent that same action is registered multiple times
        $id_action = Action::getIdAction($module_name, $action_name);

        if (!$id_action) {
            $ids_lang = Language::getIDs();

            $action = new Action();
            $action->module = $module_name;
            $action->key = $action_name;
            $action->points_change = 0;
            $action->active = ($module_name == 'genzo_krona') ? 1 : 0;
            $action->execution_type = 'unlimited';

            foreach ($ids_lang as $id_lang) {
                $action->title[$id_lang] = $action_name;
                $action->message[$id_lang] = '';
            }

            $action->add();

            // Handle Multistore
            $ids_shop = Shop::getCompleteListOfShopsID(); // This works for multistore and singlestore

            $insert['id_action'] = Action::getIdAction($module_name, $action_name);

            foreach ($ids_shop as $id_shop) {
                $insert['id_shop'] = $id_shop;
                DB::getInstance()->insert($this->table_name . '_action_shop', $insert);
            }

        }

        return true;
    }

    private function saveAction() {
	    $id_action = (int)Tools::getValue('id_action');
        $ids_lang = Language::getIDs();

	    $action = new Action($id_action);

	    // Lang Fields
        foreach ($ids_lang as $id_lang) {
            $action->title[$id_lang] = pSQL(Tools::getValue('title_'.$id_lang));
            $action->message[$id_lang] = pSQL(Tools::getValue('message_'.$id_lang));
        }

        // Basic Fields
        $action->points_change = (int)Tools::getValue('points_change');
        $action->execution_type = pSQL(Tools::getValue('execution_type'));
        $action->execution_max = ($action->execution_type=='unlimited') ? 0 : (int)Tools::getValue('execution_max');
        $action->active = (int)Tools::getValue('active');

        $action->update();

        // Shop Fields
        if ($this->is_multishop) {
            $ids_shop = Tools::getValue('checkBoxShopAsso_genzo_krona_action');
        }
        else {
            $ids_shop = $this->context->shop->id;
        }

        DB::getInstance()->delete($this->table_name . '_action_shop', "id_action={$id_action}");

        foreach ($ids_shop as $id_shop) {
            $insert['id_action'] = $id_action;
            $insert['id_shop'] = $id_shop;
            DB::getInstance()->insert($this->table_name . '_action_shop', $insert);
        }

    }

    private function saveActionOrder() {
	    $id_action_order = (int)Tools::getValue('id_action_order');

	    $action = new ActionOrder($id_action_order);

        // Basic Fields
        $action->coins_change = (int)Tools::getValue('coins_change');
        $action->coins_conversion = (float)Tools::getValue('coins_conversion');
        $action->minimum_amount = (int)Tools::getValue('minimum_amount');
        $action->active = (bool)Tools::getValue('active');

        $action->update();

        if (empty($this->errors)) {
            $this->confirmation = $this->l('Currency of order was successfully saved.');
        }

    }

    private function saveCustomAction() {

	    $ids_lang = Language::getIDs();

	    // Check inputs
	    $id_customer = (int)Tools::getValue('id_customer');
	    $points_change = Tools::getValue('points_change'); // Dont put these on int since we check it later
	    $coins_change = Tools::getValue('coins_change');
	    $type = Tools::getValue('action_type');

        $history = new PlayerHistory();
        $history->id_customer = $id_customer;
        ($type == 'action') ? $history->id_action = (int)Tools::getValue('id_action') : $history->id_action = 0;
        ($type == 'order') ? $history->id_action_order = (int)Tools::getValue('id_action_order') : $history->id_action_order = 0;

        $title = array();
        $message = array();

        if ($type == 'custom') {
            foreach ($ids_lang as $id_lang) {

                $history->title[$id_lang] = pSQL(Tools::getValue('title_' . $id_lang));
                $history->message[$id_lang] = pSQL(Tools::getValue('message_' . $id_lang));

                // Now we check if all titles and messages are empty. We have to do it like that otherwise there will be notice error after getting the $data
                if (Tools::getValue('title_' . $id_lang)) {
                    $title[$id_lang] = 'just for testing if empty';
                }

                if (Tools::getValue('message_' . $id_lang)) {
                    $message[$id_lang] = 'just for testing if empty';
                }
            }
        }
        elseif ($type == 'action') {
            $action = new Action($history->id_action);
            $history->title = $action->title;
            $history->message = $action->message;

            foreach ($history->message as $id_lang => $message) {
                $history->message[$id_lang] = str_replace('{points}', $points_change, $message);
            }
        }
        elseif ($type == 'order') {

            foreach (Language::getIDs() as $id_lang) {
                $history->title[$id_lang] = Configuration::get('krona_order_title', $id_lang, $this->id_shop_group, $this->id_shop);

                $message = Configuration::get('krona_order_message', $id_lang, $this->id_shop_group, $this->id_shop);
                $history->message[$id_lang] = str_replace('{coins}', $coins_change, $message);
            }
        }

        if ($type == 'custom' AND (empty($title) OR empty($message))) {
            $this->errors[] = $this->l('Please fill in title and message');
        }

        if ($points_change === '' AND $coins_change === '') {
            $this->errors[] = $this->l('Please fill in (at least one) a value for points or coins.');
        }

        if (empty($this->errors)) {

            // Keep in mind both points and coins could change (no else if)
            if ($points_change !== '') {
                $history->change = $points_change;
                $history->add();

                Player::updatePoints($id_customer, $points_change);
                PlayerLevel::updatePlayerLevel(new Customer($id_customer), 'points', $history->id_action);
            }

            if ($coins_change !== '' ) {
                $history->change = $coins_change;
                $history->add();

                Player::updateCoins($id_customer, $coins_change);
                PlayerLevel::updatePlayerLevel(new Customer($id_customer), 'coins', $history->id_action);
            }

            $this->confirmation = $this->l('The player action was sucessfully saved.');

            return true;
        }
        else {
            $history->action_type = $type;
            if ($points_change != 0) {
                $history->points_change = $points_change;
            }
            if ($coins_change != 0) {
                $history->coins_change = $coins_change;
            }

            return $history;
        }


    }

    private function deletePlayerLevel() {

	    // Check inputs
        $id = (int)Tools::getValue('id');

        $playerLevel = new Playerlevel($id);
        $playerLevel->delete();

        if (empty($this->errors)) {
            $this->confirmation = $this->l('The Player Level was deleted. Keep in my mind, that you have to remove any kind of reward manually.');
        }

        return true;
    }

    private function deletePlayerHistory() {

	    // Check inputs
        $id_history = (int)Tools::getValue('id_history');

        $history = new PlayerHistory($id_history);

        // This if is needed, in case of a refresh of the same url
        if ($history->id_customer) {
            Player::updatePoints($history->id_customer, ($history->change * (-1)));
        }
        $history->delete();

        if (empty($this->errors)) {
            $this->confirmation = $this->l('The Player History was deleted and the points removed.');
        }

        return true;
    }

    private function savePlayer() {
	    $id_customer = (int)Tools::getValue('id_customer');

	    $player = new Player($id_customer);

        // Basic Fields
        $player->active = (bool)Tools::getValue('active');
        $player->banned = (bool)Tools::getValue('banned');

        if (Configuration::get('krona_pseudonym', null, $this->id_shop_group, $this->id_shop)) {
            $player->pseudonym = pSQL(Tools::getValue('pseudonym'));
        }
        if (Configuration::get('krona_avatar', null, $this->id_shop_group, $this->id_shop)) {
            $player->avatar = ($this->uploadAvatar($id_customer)) ? $id_customer . '.jpg' : $player->avatar;
        }

        $player->update();

        if (empty($this->errors)) {
            $this->confirmation = $this->l('Player was successfully saved!');
        }

        return true;
    }

    private function saveLevel() {
	    $id_level = (int)Tools::getValue('id_level');

        ($id_level > 0) ? $level = new Level($id_level) : $level = new Level();

        // Lang Fields
        $ids_lang = Language::getIDs();
        foreach ($ids_lang as $id_lang) {
            $level->name[$id_lang] = pSQL(Tools::getValue('name_'.$id_lang));
        }

        // Basic Fields
        $level->active = (int)Tools::getValue('active');
        $level->condition_type = pSQL(Tools::getValue('condition_type'));

        if ($level->condition_type == 'points' OR $level->condition_type == 'coins' OR $level->condition_type=='total') {
            $level->condition = (int)Tools::getValue('condition_points');
            $level->id_action = 0;
        }
        elseif ($level->condition_type == 'action') {
            $level->condition = (int)Tools::getValue('condition_action');
            $level->id_action = (int)Tools::getValue('id_action');
        }

        $level->condition_time = (int)Tools::getValue('condition_time');
        $level->duration = (int)Tools::getValue('duration');
        $level->reward_type = pSQL(Tools::getValue('reward_type'));

        if ($level->reward_type == 'symbolic') {
            $level->id_reward = 0;
        }
        elseif ($level->reward_type == 'coupon') {
            $level->id_reward = (int)Tools::getValue('id_reward_coupon');
        }
        elseif ($level->reward_type == 'group') {
            $level->id_reward = (int)Tools::getValue('id_reward_group');
        }

        $level->achieve_max = (int)Tools::getValue('achieve_max');

        if($_FILES['icon']['tmp_name']) {
            $icon_old = $level->icon; // We need to delete the old image, since we don't override it
            $icon = $this->uploadIcon();
            $level->icon = ($icon) ? $icon : 'no-icon.png';

            if (isset($icon_old) && $icon_old != 'no-icon.png' && $icon_old != $level->icon) {
                unlink(_PS_MODULE_DIR_ . 'genzo_krona/views/img/icon/' . $icon_old);
            }
        }

        if (empty($level->name) OR
            $level->condition == '' OR
            $level->condition_time === '' ) {
            $this->errors = $this->l('Please fill Name, Condition and Time');
            return $level;
        }

        $level->save();

        if (empty($this->errors)) {
            $this->confirmation = $this->l('Level was successfully saved!');
        }

        // Shop Fields
        if ($this->is_multishop) {
            $ids_shop = Tools::getValue('checkBoxShopAsso_genzo_krona_level');
        }
        else {
            $ids_shop[] = $this->context->shop->id;
        }

        if (!($id_level > 0)) {
            $id_level = DB::getInstance()->Insert_ID();
        }

        DB::getInstance()->delete($this->table_name . '_level_shop', "id_level={$id_level}");

        foreach ($ids_shop as $id_shop) {
            $insert['id_level'] = (int)$id_level;
            $insert['id_shop'] = (int)$id_shop;
            DB::getInstance()->insert($this->table_name . '_level_shop', $insert);
        }

        return true;
    }

    private function deleteLevel() {
	    $id_level = (int)Tools::getValue('id_level');

	    $level = new Level($id_level);
	    $level->delete();

	    // Delete Icon
        if ($level->icon!='no-icon.jpg') {
            unlink(_PS_MODULE_DIR_ . 'genzo_krona/views/img/icon/'.$level->icon);
        }

	    // Delete Shop Relations
        Db::getInstance()->delete($this->table_name . '_level_shop', "id_level={$id_level}");

        if (empty($this->errors)) {
            $this->confirmation = $this->l('The Level was deleted.');
        }
    }

    private function saveSettings() {

	    $loyalty = Configuration::get('krona_loyalty_active', null, $this->id_shop_group, $this->id_shop);
	    $gamification = Configuration::get('krona_gamification_active', null, $this->id_shop_group, $this->id_shop);

	    // Settings
	    $ids_lang = Language::getIDs();
	    $game_names = array();
	    $total_names = array();
	    $loyalty_names = array();
	    $order_titles = array();
	    $order_messages = array();
	    $order_canceled_titles = array();
	    $order_canceled_messages = array();
	    $home_descriptions = array();

	    // Lang fields
	    foreach ($ids_lang as $id_lang) {
	        $game_names[$id_lang] = Tools::getValue('game_name_'.$id_lang);
	        $total_names[$id_lang] = Tools::getValue('total_name_'.$id_lang);
	        $loyalty_names[$id_lang] = Tools::getValue('loyalty_name_'.$id_lang);
	        $order_titles[$id_lang] = Tools::getValue('order_title_'.$id_lang);
	        $order_messages[$id_lang] = Tools::getValue('order_message_'.$id_lang);
	        $order_canceled_titles[$id_lang] = Tools::getValue('order_canceled_title_'.$id_lang);
	        $order_canceled_messages[$id_lang] = Tools::getValue('order_canceled_message_'.$id_lang);
	        $home_descriptions[$id_lang] = Tools::getValue('home_description_'.$id_lang);
        }

        Configuration::updateValue('krona_game_name', $game_names, false, $this->id_shop_group, $this->id_shop);
	    if ($gamification) { Configuration::updateValue('krona_total_name', $total_names, false, $this->id_shop_group, $this->id_shop); }
	    if ($loyalty) { Configuration::updateValue('krona_loyalty_name', $loyalty_names, false, $this->id_shop_group, $this->id_shop); }
	    Configuration::updateValue('krona_order_title', $order_titles, false, $this->id_shop_group, $this->id_shop);
	    Configuration::updateValue('krona_order_message', $order_messages, false, $this->id_shop_group, $this->id_shop);
	    Configuration::updateValue('krona_order_canceled_title', $order_canceled_titles, false, $this->id_shop_group, $this->id_shop);
	    Configuration::updateValue('krona_order_canceled_message', $order_canceled_messages, false, $this->id_shop_group, $this->id_shop);
	    Configuration::updateValue('krona_description', $home_descriptions, true, $this->id_shop_group, $this->id_shop);

	    // Basic Fields
        Configuration::updateValue('krona_loyalty_active', pSQL(Tools::getValue('loyalty_active')), false, $this->id_shop_group, $this->id_shop);
        Configuration::updateValue('krona_gamification_active', pSQL(Tools::getValue('gamification_active')), false, $this->id_shop_group, $this->id_shop);

        if ($loyalty) {
            Configuration::updateValue('krona_loyalty_total', pSQL(Tools::getValue('loyalty_total')), false, $this->id_shop_group, $this->id_shop);
        }
        if ($gamification) {
            Configuration::updateValue('krona_gamification_total', pSQL(Tools::getValue('gamification_total')), false, $this->id_shop_group, $this->id_shop);
            Configuration::updateValue('krona_display_name', (int)Tools::getValue('display_name'), false, $this->id_shop_group, $this->id_shop);
            Configuration::updateValue('krona_pseudonym', (bool)Tools::getValue('pseudonym'), false, $this->id_shop_group, $this->id_shop);
            Configuration::updateValue('krona_avatar', (bool)Tools::getValue('avatar'), false, $this->id_shop_group, $this->id_shop);
        }
        Configuration::updateValue('krona_url', pSQL(Tools::getValue('url')), false, $this->id_shop_group, $this->id_shop);
        Configuration::updateValue('krona_customer_active', (bool)Tools::getValue('customer_active'), false, $this->id_shop_group, $this->id_shop);
        Configuration::updateValue('krona_order_amount', pSQL(Tools::getValue('order_amount')), false, $this->id_shop_group, $this->id_shop);
        Configuration::updateValue('krona_order_rounding', pSQL(Tools::getValue('order_rounding')), false, $this->id_shop_group, $this->id_shop);
        Configuration::updateValue('krona_coupon_prefix', pSQL(Tools::getValue('coupon_prefix')), false, $this->id_shop_group, $this->id_shop);

        // Handling Status
        $orderStates = OrderState::getOrderStates($this->context->language->id);
        $order_state_selected = array();
        $order_state_cancel_selected = array();

        foreach ($orderStates as $orderState) {

            $id_order_state = $orderState['id_order_state'];

            if (Tools::isSubmit('order_state_'.$id_order_state)) {
                $order_state_selected[] = $id_order_state;
            }
            if (Tools::isSubmit('order_state_cancel_'.$id_order_state)) {
                $order_state_cancel_selected[] = $id_order_state;
            }

        }
        Configuration::updateValue('krona_order_state', implode(",", $order_state_selected), false, $this->id_shop_group, $this->id_shop);
        Configuration::updateValue('krona_order_state_cancel', implode(",", $order_state_cancel_selected), false, $this->id_shop_group, $this->id_shop);




        if (empty($this->errors)) {
            $this->confirmation = $this->l('Settings were sucessfully saved.');
        }

    }

    private function saveGroupsPriority() {

        $ids_group = Group::getGroups($this->context->language->id);

        Db::getInstance()->delete('genzo_krona_settings_group');

        foreach ($ids_group as $id_group) {
            $insert['id_group'] = $id_group['id_group'];
            $insert['position'] = (int)Tools::getValue('position_'.$id_group['id_group']);
            Db::getInstance()->insert('genzo_krona_settings_group', $insert);
        }

        if (empty($this->errors)) {
            $this->confirmation = $this->l('Groups Priority were sucessfully saved.');
        }
    }

    private function saveToggle($table, $primary_column, $toggle_column) {
	    $id = Tools::getValue($primary_column);
        $query = new DbQuery();
        $query->select($toggle_column);
        $query->from($table);
        $query->where($primary_column . '=' . (int)$id);
        $value = Db::getInstance()->getValue($query);

        ($value == 0) ? $new[$toggle_column] = 1 : $new[$toggle_column] = 0;

	    DB::getInstance()->update($table, $new, "{$primary_column}={$id}");
    }


    // Helper Functions
    public function uploadAvatar($id_customer) {

	    $id_customer = (int)$id_customer;
	    if($_FILES['avatar']['tmp_name']) {

	        if($_FILES['avatar']['size'] > 5242880) {
	            $this->errors[] = $this->l('Allowed file size is max 5MB');
	            return false;
            }

            // Handling File
            $file_tmp = $_FILES['avatar']['tmp_name'];

            // Check if its an image
            (@is_array(getimagesize($file_tmp))) ? $image = true : $image = false;

            if ($image) {
                $file_path = _PS_MODULE_DIR_ . 'genzo_krona/views/img/avatar/' . $id_customer . '.jpg'; // We need absolute path

                move_uploaded_file($file_tmp, $file_path);

                $avatar = new Zebra_Image();
                $avatar->source_path = $file_path;
                $avatar->target_path = $file_path;
                $avatar->jpeg_quality = 95;
                $avatar->resize(100, 100, ZEBRA_IMAGE_CROP_CENTER);
            }
            else {
                $this->errors[] = $this->l('Image Upload failed');
            }
            return true;
        }
        return false;
    }

    public function uploadIcon() {

        $file_name = pathinfo($_FILES['icon']['name'], PATHINFO_FILENAME); // Filename without extension

        $ugly = array('ä', 'ö', 'ü');
        $nice = array('ae', 'oe', 'ue');
        $file_name = str_replace($ugly, $nice, $file_name);

        // Remove anything which isn't a word, whitespace, number or any of the following caracters -_[]().
        $file_name = preg_replace("([^\w\s\d\-_\[\]\(\).])", '', $file_name);
        // Remove any runs of periods (points, comas)
        $file_name = preg_replace("([\.]{2,})", '', $file_name);
        // Remove spaces and lower the letters
        $file_name = strtolower(str_replace(' ','-',$file_name));
        // Add Extension
        $file_name .= '.png';

        if(!$file_name OR $file_name=='') {
            $this->errors[] = $this->l('Invalid Filename');
            return false;
        }

	    if($_FILES['icon']['tmp_name']) {

	        if($_FILES['icon']['size'] > 5242880) {
	            $this->errors[] = $this->l('Allowed file size is max 5MB');
	            return false;
            }

            // Handling File
            $file_tmp = $_FILES['icon']['tmp_name'];

            // Check if its an image
            (@is_array(getimagesize($file_tmp))) ? $image = true : $image = false;

            if ($image) {
                $file_path = _PS_MODULE_DIR_ . 'genzo_krona/views/img/icon/' . $file_name; // We need absolute path

                move_uploaded_file($file_tmp, $file_path);

                $avatar = new Zebra_Image();
                $avatar->source_path = $file_path;
                $avatar->target_path = $file_path;
                $avatar->png_compression = 1;
                $avatar->resize(30, 30, ZEBRA_IMAGE_CROP_CENTER, -1);
            }
            else {
                $this->errors[] = $this->l('Image Upload failed');
            }
            return $file_name;
        }
        return false;
    }

    private function getCronJobUrl() {

	    $secureKey = md5(_COOKIE_KEY_ . Configuration::get('PS_SHOP_NAME'));

        (Configuration::get('PS_SSL_ENABLED')==1) ? $ssl = true : $ssl = false;

        $url =  _PS_BASE_URL_._MODULE_DIR_.$this->name."/genzo_krona_cron.php?secure_key=".$secureKey;

        if ($ssl) {
            $url = str_replace('http://', 'https://', $url);
        }

        return $url;
    }

    private function checkGroups() {
	    // This functions checks basically, if all groups have Priority
        $query = new DbQuery();
        $query->select('Count(*)');
        $query->from('group');
        $group =  Db::getInstance()->getValue($query);

        $query = new DbQuery();
        $query->select('Count(*)');
        $query->from($this->table_name . '_settings_group');
        $priority =  Db::getInstance()->getValue($query);

        if ($group != $priority) {
            $this->errors[] = $this->l('You should check and save the group priorities. Go to: "Settings"');
        }

        return true;
    }

    private function checkCurrencies() {

	    // This functions checks basically, if all currencies are in the action_order table
        $query = new DbQuery();
        $query->select('id_currency');
        $query->from($this->table_name . '_action_order');
        $actionOrders =  Db::getInstance()->executeS($query);

        $query = new DbQuery();
        $query->select('id_currency');
        $query->from('currency');
        $query->where('deleted=0');
        $currencies = Db::getInstance()->executeS($query);

        // Flaten the multidimensional arrays, so we can use array_dif
        $actionOrders = array_map('current', $actionOrders);
        $currencies = array_map('current', $currencies);

        $missing = array_diff($currencies, $actionOrders); // Which currencies are missing in the module
        $redundant = array_diff($actionOrders, $currencies); // Which currencies are redundant in the module

        if (!empty($missing)) {
            foreach ($missing as $currency) {
                $actionOrder = new ActionOrder();
                $actionOrder->id_currency = $currency['id_currency'];
                $actionOrder->coins_change = 1;
                $actionOrder->minimum_amount = 0;
                $actionOrder->active = 0;
                $actionOrder->add();
            }
        }

        if (!empty($redundant)) {
            foreach ($redundant as $currency) {
                $id_currency = $currency['id_currency'];
                $id_action_order = ActionOrder::getIdActionOrderByCurrency($id_currency);

                $actionOrder = new ActionOrder($id_action_order);
                $actionOrder->delete();
            }
        }

        if (!empty($missing) OR !empty($redundant)) {
            // We redirect so that a refresh of the page is not needed
            $url = $this->context->link->getAdminLink('AdminModules', true) . '&configure=' . $this->name . '&module_name=' . $this->name;
            Tools::redirectAdmin($url);
        }
    }

    private function checkContextMultistore() {
        if ($this->is_multishop) {
            if (Shop::getContext() == Shop::CONTEXT_GROUP || Shop::getContext() == Shop::CONTEXT_ALL) {
                return '<p class="alert alert-warning">'. $this->l('Please chose a specific shop, to save the settings.'). '</p>';
            }
        }

        return false;
    }


    //Hooks

    public function hookDisplayBackofficeHeader() {
        $configure = Tools::getValue('configure');
        if ($configure == $this->name) {
            // CSS
            $this->context->controller->addCSS($this->_path . '/views/css/admin-krona.css');

            // JS
            $this->context->controller->addJquery(); // otherwise admin-krona.js is not working
            $this->context->controller->addJS($this->_path . '/views/js/admin-krona.js');
            $this->context->controller->addJqueryUI('ui.sortable');
        }
    }

    public function hookDisplayHeader () {
	    // CSS
        $this->context->controller->addCSS($this->_path.'/views/css/krona.css');

        // JS
        $this->context->controller->addJquery();
        $this->context->controller->addJS($this->_path.'/views/js/krona.js');

        if (Action::checkIfActionIsActive('genzo_krona', 'page_visit') AND
            $this->context->customer->isLogged()) {
            Media::addJsDef(array('id_customer' => $this->context->customer->id));
            $this->context->controller->addJS($this->_path . '/views/js/page_visit.js');
        }

    }

    public function hookDisplayCustomerAccount () {

        $this->context->smarty->assign(array(
            'game_name' => Configuration::get('krona_game_name', $this->context->language->id, $this->id_shop_group, $this->id_shop),
        ));

        return $this->display(__FILE__, 'views/templates/front/customer_account.tpl');
    }

    public function hookActionExecuteKronaAction($params) {

	    $module_name = pSQL($params['module_name']);
	    $action_name = pSQL($params['action_name']);

	    // Hook values: module_name, action_name, id_customer, action_url, action_message
        $customer = new Customer((int)$params['id_customer']);
        $id_action   = Action::getIdAction($module_name, $action_name);

        if (!$id_action) {
            return 'Action not found';
        }

        // We have to check Multistore
        if (Shop::isFeatureActive()) {
            $id_shop = $customer->id_shop;
        }
        else {
            $id_shop = null;
        }

        // Check if Player exits
        if (!Player::checkIfPlayerExits($customer->id)) {
            Player::createPlayer($customer->id);
        }

        if (
            !Player::checkIfPlayerIsActive($customer->id) == 1 OR
            !Player::checkIfPlayerIsBanned($customer->id) == 0 OR
            !Action::checkIfActionIsActive($module_name, $action_name, $id_shop) == 1
        ) {
            return 'Player or Action not active.';
        }
        else {

            $action = new Action($id_action);

            $execution_times = 0;

            // How many times was the action already executed for the defined time span?
            if ($action->execution_type != 'unlimited') {

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
                    $endDate = null; // This is max_per_lifetime
                }

                $execution_times = PlayerHistory::countActionByPlayer($customer->id, $id_action, $startDate, $endDate);

            }

            // Check if the User is still allowed to execute this action
            if (($execution_times < $action->execution_max) OR ($action->execution_type == 'unlimited')) {

                Player::updatePoints($customer->id, $action->points_change);

                $history = new PlayerHistory();
                $history->id_customer = $customer->id;
                $history->id_action = $id_action;

                if (!empty($params['action_url'])) {
                    $history->url = $params['action_url']; // Action url is not mandatory
                }

                $history->change = $action->points_change;

                // Preparing the lang array for the history message
                $ids_lang = Language::getIDs();
                $message = array();

                if (!empty($params['action_message'])) {
                    $message = $params['action_message'];
                }

                foreach ($ids_lang as $id_lang) {

                    if (empty($params['action_message'])) {
                        $message[$id_lang] = $action->message[$id_lang];
                    }

                    // After defining the message array we replace the shortcodes -> shortcodes can be used for external messages too
                    $search = array('{points}', '{coins}');
                    $replace = array($history->change, $history->change);
                    $history->message[$id_lang] = str_replace($search, $replace, $message[$id_lang]);

                    $history->title[$id_lang] = pSQL($action->title[$id_lang]);
                }



                $history->add();

                PlayerLevel::updatePlayerLevel($customer, 'points', $id_action);

            }
        }
        return true;
	}

	public function hookActionOrderStatusUpdate($params) {

        $id_state_new = $params['newOrderStatus']->id;

        // Order
        $id_order = $params['id_order'];
        $order = new Order($id_order);

        // Customer -> We first need to get the shop
        $id_customer = $order->id_customer;
        $customer = new Customer ($id_customer);

        $order_states = explode(',', Configuration::get('krona_order_state', null, $customer->id_shop_group, $customer->id_shop));
        $order_states_cancel = explode(',', Configuration::get('krona_order_state_cancel', null, $customer->id_shop_group, $customer->id_shop));

        // Check if the status is relevant
        if (in_array($id_state_new, $order_states) OR in_array($id_state_new, $order_states_cancel)) {

            // Check ActionOrder -> This is basically checking the currency
            $id_actionOrder = ActionOrder::getIdActionOrderByCurrency($order->id_currency);
            $actionOrder = new ActionOrder($id_actionOrder);

            if ($actionOrder->active) {

                // Get Total amount of the order
                $order_amount = Configuration::get('krona_order_amount', null, $this->id_shop_group, $this->id_shop);

                if ($order_amount == 'total_wt') {
                    $total = $order->total_paid; // Total with taxes
                } elseif ($order_amount == 'total') {
                    $total = $order->total_paid_tax_excl;
                } elseif ($order_amount == 'total_products_wt') {
                    $total = $order->total_products_wt;
                } elseif ($order_amount == 'total_products') {
                    $total = $order->total_products;
                } else {
                    $total = $order->total_paid; // Standard if nothing is set
                }

                // Check if total is high enough
                if ($total < $actionOrder->minimum_amount) {
                    return false;
                }

                // Check if Player exits
                if (!Player::checkIfPlayerExits($id_customer)) {
                    Player::createPlayer($id_customer);
                }

                if ( Player::checkIfPlayerIsActive($id_customer) == 0 OR Player::checkIfPlayerIsBanned($id_customer) == 1 ) {
                    return false;
                }
                else {
                    // Check the rounding method -> up is standard
                    $order_rounding = Configuration::get('krona_order_rounding', null, $this->id_shop_group, $this->id_shop);

                    if ($order_rounding == 'down') {
                        $coins_change = floor($total * $actionOrder->coins_change);
                    } else {
                        $coins_change = ceil($total * $actionOrder->coins_change);
                    }
                    foreach ($order_states as $id_state_ok) {

                        if ($id_state_new == $id_state_ok) {

                            Player::updateCoins($id_customer, $coins_change);

                            $history = new PlayerHistory();
                            $history->id_customer = $id_customer;
                            $history->id_action_order = $id_actionOrder;
                            $history->url = $this->context->link->getPageLink('history');
                            $history->change = $coins_change;

                            // Handling lang fields for Player History
                            $ids_lang = Language::getIDs();
                            $title = array();
                            $message = array();

                            foreach ($ids_lang as $id_lang) {

                                $title[$id_lang] = Configuration::get('krona_order_title', $id_lang, $customer->id_shop_group, $customer->id_shop);
                                $message[$id_lang] = Configuration::get('krona_order_message', $id_lang, $customer->id_shop_group, $customer->id_shop);

                                // Replace message variables
                                $search = array('{points}', '{reference}', '{amount}');

                                $total_currency = Tools::displayPrice(Tools::convertPrice($total, $order->id_currency));

                                $replace = array($coins_change, $order->reference, $total_currency);
                                $message[$id_lang] = str_replace($search, $replace, $message[$id_lang]);

                                $history->message[$id_lang] = pSQL($message[$id_lang]);
                                $history->title[$id_lang] = pSQL($title[$id_lang]);
                            }

                            $history->add();

                            PlayerLevel::updatePlayerLevel($customer, 'coins', $id_actionOrder);
                        }
                    }
                    foreach ($order_states_cancel as $id_state_cancel) {
                        if ($id_state_new == $id_state_cancel) {

                            Configuration::updateValue('krona_cancel_test', 'ja');

                            $history = new PlayerHistory($id_customer);
                            $history->id_customer = $id_customer;
                            $history->id_action_order = $id_actionOrder;
                            $history->url = $this->context->link->getPageLink('history');
                            $history->change = $coins_change * (-1);

                            $ids_lang = Language::getIDs();

                            foreach ($ids_lang as $id_lang) {
                                $title[$id_lang] = Configuration::get('krona_order_canceled_title', $id_lang, $customer->id_shop_group, $customer->id_shop);
                                $message[$id_lang] = Configuration::get('krona_order_canceled_message', $id_lang, $customer->id_shop_group, $customer->id_shop);

                                // Replace message variables
                                $search = array('{points}', '{reference}', '{amount}');

                                $total_currency = Tools::displayPrice(Tools::convertPrice($total, $order->id_currency));

                                $replace = array($coins_change, $order->reference, $total_currency);
                                $message[$id_lang] = str_replace($search, $replace, $message[$id_lang]);

                                $history->message[$id_lang] = pSQL($message[$id_lang]);
                                $history->title[$id_lang] = pSQL($title[$id_lang]);
                            }
                            $history->add();
                            Player::updateCoins($id_customer, $history->change);

                            // Todo: Theoretically we need to check here, if a customer loses a level after the cancel
                        }
                    }
                }
            }
        }

        return true;
    }

    public function hookActionCustomerAccountAdd($params) {
	    $id_customer = (int)$params['newCustomer']->id_customer;
	    Player::createPlayer($id_customer);
    }

    public function hookModuleRoutes () {

	    $slack = Configuration::get('krona_url', null, $this->id_shop_group, $this->id_shop);

        $my_routes = array(
            'module-genzo_krona-home' => array(
                'controller' => 'home',
                'rule' => $slack,
                'keywords' => array(),
                'params' => array(
                    'fc' => 'module',
                    'module' => 'genzo_krona',
                    'controller' => 'home',
                ),
            ),
            'module-genzo_krona-overview' => array(
                'controller' => 'overview',
                'rule' => $slack.'/overview',
                'keywords' => array(),
                'params' => array(
                    'fc' => 'module',
                    'module' => 'genzo_krona',
                    'controller' => 'overview',
                ),
            ),
            'module-genzo_krona-customersettings' => array(
                'controller' => 'customersettings',
                'rule' => $slack.'/settings',
                'keywords' => array(),
                'params' => array(
                    'fc' => 'module',
                    'module' => 'genzo_krona',
                    'controller' => 'customersettings',
                ),
            ),
            'module-genzo_krona-timeline' => array(
                'controller' => 'timeline',
                'rule' => $slack.'/timeline',
                'keywords' => array(),
                'params' => array(
                    'fc' => 'module',
                    'module' => 'genzo_krona',
                    'controller' => 'timeline',
                ),
            ),
            'module-genzo_krona-levels' => array(
                'controller' => 'levels',
                'rule' => $slack.'/levels',
                'keywords' => array(),
                'params' => array(
                    'fc' => 'module',
                    'module' => 'genzo_krona',
                    'controller' => 'levels',
                ),
            ),
            'module-genzo_krona-leaderboard' => array(
                'controller' => 'leaderboard',
                'rule' => $slack.'/leaderboard',
                'keywords' => array(),
                'params' => array(
                    'fc' => 'module',
                    'module' => 'genzo_krona',
                    'controller' => 'leaderboard',
                ),
            ),
            'module-genzo_krona-loyalty' => array(
                'controller' => 'loyalty',
                'rule' => $slack.'/loyalty',
                'keywords' => array(),
                'params' => array(
                    'fc' => 'module',
                    'module' => 'genzo_krona',
                    'controller' => 'loyalty',
                ),
            ),
        );

        return $my_routes;
    }



    // Helper for Configuration Values
    public static function isLoyaltyActive() {

        $id_shop_group = Context::getContext()->shop->id_shop_group;
        $id_shop = Context::getContext()->shop->id_shop;

        return Configuration::get('krona_loyalty_active', null, $id_shop_group, $id_shop);

    }

    public static function isGamificationActive() {

        $id_shop_group = Context::getContext()->shop->id_shop_group;
        $id_shop = Context::getContext()->shop->id_shop;

        return Configuration::get('krona_gamification_active', null, $id_shop_group, $id_shop);

    }

    public static function getLoyaltyTotalMethod() {

        $id_shop_group = Context::getContext()->shop->id_shop_group;
        $id_shop = Context::getContext()->shop->id_shop;

        return Configuration::get('krona_loyalty_total', null, $id_shop_group, $id_shop);

    }

    public static function getGamificationTotalMethod() {

        $id_shop_group = Context::getContext()->shop->id_shop_group;
        $id_shop = Context::getContext()->shop->id_shop;

       return Configuration::get('krona_gamification_total', null, $id_shop_group, $id_shop);

    }


}