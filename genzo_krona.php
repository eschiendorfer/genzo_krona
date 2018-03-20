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
require_once _PS_MODULE_DIR_ . 'genzo_krona/classes/PlayerLevel.php';

require_once _PS_MODULE_DIR_ . 'genzo_krona/classes/helper/Zebra_Image.php';

use KronaModule\Action;
use KronaModule\ActionOrder;
use KronaModule\Player;
use KronaModule\PlayerHistory;
use KronaModule\PlayerLevel;
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
            !$this->registerHook('displayRightColumnProduct') OR
            !$this->registerHook('displayShoppingCartFooter') OR
            !$this->registerHook('displayKronaCustomer') OR
            !$this->registerHook('actionExecuteKronaAction') OR
            !$this->registerHook('actionCustomerAccountAdd') OR
            !$this->registerHook('actionOrderStatusUpdate') OR
			!$this->registerHook('ModuleRoutes') OR
			!$this->registerHook('actionAdminGenzoKronaPlayersListingResultsModifier') OR
            !$this->registerInbuiltActions() OR
            !$this->registerExternalActions() OR
            !$this->registerAdminMenu('AdminCustomers', 'AdminGenzoKronaActions', 'Krona', 1) OR
            !$this->registerAdminMenu('AdminGenzoKronaActions', 'AdminGenzoKronaOrders', 'Orders', 0) OR
            !$this->registerAdminMenu('AdminGenzoKronaActions', 'AdminGenzoKronaLevels', 'Levels', 0) OR
            !$this->registerAdminMenu('AdminGenzoKronaActions', 'AdminGenzoKronaPlayers', 'Players', 0) OR
            !$this->registerAdminMenu('AdminGenzoKronaActions', 'AdminGenzoKronaCoupons', 'Coupons', 0) OR
            !$this->registerAdminMenu('AdminGenzoKronaActions', 'AdminGenzoKronaGroups', 'Groups', 0) OR
            !$this->registerAdminMenu('AdminGenzoKronaActions', 'AdminGenzoKronaSettings', 'Settings', 0) OR
            !$this->registerAdminMenu('AdminGenzoKronaActions', 'AdminGenzoKronaSupport', 'Support', 0)
        )
			return false;
		return true;
	}

	public function uninstall() {
		if (!parent::uninstall() OR
			    !$this->executeSqlScript('uninstall') OR
                !$this->removeAdminMenu('AdminGenzoKronaActions') OR
                !$this->removeAdminMenu('AdminGenzoKronaOrders') OR
                !$this->removeAdminMenu('AdminGenzoKronaLevels') OR
                !$this->removeAdminMenu('AdminGenzoKronaPlayers') OR
                !$this->removeAdminMenu('AdminGenzoKronaCoupons') OR
                !$this->removeAdminMenu('AdminGenzoKronaGroups') OR
                !$this->removeAdminMenu('AdminGenzoKronaSettings') OR
                !$this->removeAdminMenu('AdminGenzoKronaSupport')
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

        // Inbuilt Actions
        $actions = array(
            'account_creation' => array(
                'title' => 'Account Creation',
                'message' => 'You created an account. This brought you {points} points.',
            ),
            'page_visit' => array(
                'title' => 'Page Visit',
                'message' => 'You visited our page today. This brought you {points} points.',
            ),
            'avatar_upload' => array(
                'title' => 'Avatar Upload',
                'message' => 'The avatar upload brought you {points} points.',
            ),
            'newsletter' => array(
                'title' => 'Newsletter Subscription',
                'message' => 'You a subscribed to our newsletter. This just brought you {points} points.',
            )
        );

        foreach ($actions as $action_name => $action) {
            $this->registerAction($this->name, $action_name, $action['title'], $action['message']);
        }

        $this->updateInbuiltActions();

        return true;

    }

    private function updateInbuiltActions() {

	    $ids_lang = Language::getIDs();

	    // Account Creation
	    $id_account = Action::getIdAction($this->name, 'account_creation');
	    $account = new Action($id_account);
	    $account->execution_type = 'per_lifetime';
	    $account->execution_max = 1;
	    $account->points_change = 100;
	    $account->update();

	    // Page Visit
	    $id_visit = Action::getIdAction($this->name, 'page_visit');
	    $visit = new Action($id_visit);
	    $visit->execution_type = 'per_day';
	    $visit->execution_max = 1;
	    $visit->points_change = 5;
	    $visit->update();

	    // Avatar Upload
        $id_avatar = Action::getIdAction($this->name, 'avatar_upload');
        $avatar = new Action($id_avatar);
        $avatar->execution_type = 'per_lifetime';
        $avatar->execution_max = 1;
        $avatar->points_change = 50;
        $avatar->update();

	    // Newsletter
        $id_newsletter = Action::getIdAction($this->name, 'newsletter');
        $newsletter = new Action($id_newsletter);
        $newsletter->execution_type = 'per_month';
        $newsletter->execution_max = 1;
        $newsletter->points_change = 20;
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
                Configuration::updateValue('krona_loyalty_active', 1, false, $id_shop_group, $id_shop);
            }
            if (!Configuration::get('krona_loyalty_total', null, $id_shop_group, $id_shop)) {
                Configuration::updateValue('krona_loyalty_total', 'points_coins', false, $id_shop_group, $id_shop);
            }
            if (!Configuration::get('krona_gamification_active', null, $id_shop_group, $id_shop)) {
                Configuration::updateValue('krona_gamification_active', 1, false, $id_shop_group, $id_shop);
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
            if (!Configuration::get('krona_loyalty_product_page', null, $id_shop_group, $id_shop)) {
                Configuration::updateValue('krona_loyalty_product_page', 1, false, $id_shop_group, $id_shop);
            }
            if (!Configuration::get('krona_loyalty_cart_page', null, $id_shop_group, $id_shop)) {
                Configuration::updateValue('krona_loyalty_cart_page', 1, false, $id_shop_group, $id_shop);
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

    private function registerAction($module_name, $action_name, $title, $message) {

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
                $action->title[$id_lang] = $title;
                $action->message[$id_lang] = $message;
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

    private function registerExternalActions() {
        $modules = Hook::exec('actionRegisterKronaAction', [], null, true, false);
        if(!empty($modules)) {
            foreach ($modules as $module_name => $actions) {
                if (!empty($actions)) {
                    foreach ($actions as $action_name => $action) {
                        if ($action_name!='' && !empty($action['title']) && !empty($action['message'])) {
                            $this->registerAction($module_name, $action_name, $action['title'], $action['message']);
                        }
                    }
                }
            }
        }
        return true;
    }

    private function registerAdminMenu($parent, $class_name, $name, $active = true) {
        // Create new admin tab -> This is needed, otherwise the Admin Controller aren't working
        $tab = new Tab();
        $tab->id_parent = (int)Tab::getIdFromClassName($parent);
        $tab->name = array();
        foreach (Language::getLanguages(true) as $lang)
            $tab->name[$lang['id_lang']] = $name;
        $tab->class_name = $class_name;
        $tab->module = $this->name;
        $tab->active = ($active) ? 1 : 0;
        return $tab->add();
    }

    private function removeAdminMenu($class_name) {
        $id_tab = (int)Tab::getIdFromClassName($class_name);
        $tab = new Tab($id_tab);
        return $tab->delete();
    }

	// Backoffice

    public function getContent() {
	    $url = $this->context->link->getAdminLink('AdminGenzoKronaSettings', true);
	    Tools::redirectAdmin($url);
    }


    // List Helpers -> We need them if we go for new Helper List()
    public function getFiltersFromList($fields_list, $table_name) {

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

    public function getPagination($tableName) {
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
                $avatar->resize(100, 100, ZEBRA_IMAGE_CROP_CENTER, -1);
            }
            else {
                $this->errors[] = $this->l('Image Upload failed');
            }
            return $file_name;
        }
        return false;
    }

    public function saveToggle($table, $primary_column, $toggle_column) {
        $id = Tools::getValue($primary_column);
        $query = new DbQuery();
        $query->select($toggle_column);
        $query->from($table);
        $query->where($primary_column . '=' . (int)$id);
        $value = Db::getInstance()->getValue($query);
        ($value == 0) ? $new[$toggle_column] = 1 : $new[$toggle_column] = 0;
        DB::getInstance()->update($table, $new, "{$primary_column}={$id}");
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

    public function hookDisplayKronaCustomer ($params) {

	    $id_customer = (int)$params['id_customer'];

	    if (Player::checkIfPlayerIsActive($id_customer)) {

            $player = new Player($id_customer);

            $name = Configuration::get('krona_total_name', $this->context->language->id, $this->context->shop->id_shop_group, $this->context->shop->id_shop);

            $player = array(
                'pseudonym' => $player->pseudonym,
                'avatar' => $player->avatar_full,
                'total' => $player->total . ' ' . $name,
            );

            return $player;
        }
        else {
	        return false;
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
            'game_name' => Configuration::get('krona_game_name', $this->context->language->id, $this->context->shop->id_shop_group, $this->context->shop->id),
        ));

        return $this->display(__FILE__, 'views/templates/hook/customerAccount.tpl');
    }

    public function hookDisplayRightColumnProduct ($params) {

	    if (Configuration::get('krona_loyalty_product_page')) {

            $this->context->controller->addJS($this->_path.'/views/js/krona-loyalty.js');

            $id_currency = $this->context->currency->id;
            $id_ActionOrder = ActionOrder::getIdActionOrderByCurrency($id_currency);
            $actionOrder = new ActionOrder($id_ActionOrder);

            $order_amount = Configuration::get('krona_order_amount', null, $this->context->shop->id_shop_group, $this->context->shop->id_shop);

            if ($order_amount == 'total_wt') {
                $coins_in_cart = $this->context->cart->getSummaryDetails()['total_price'];
                $tax = true;
            } elseif ($order_amount == 'total') {
                $coins_in_cart = $this->context->cart->getSummaryDetails()['total_price_without_tax'];
                $tax = false;
            } elseif ($order_amount == 'total_products_wt') {
                $coins_in_cart = $this->context->cart->getSummaryDetails()['total_products_wt'];
                $tax = true;
            } elseif ($order_amount == 'total_products') {
                $coins_in_cart = $this->context->cart->getSummaryDetails()['total_products'];
                $tax = false;
            } else {
                $coins_in_cart = 0;
                $tax = true;
            }

            ($tax) ? $tax_rate = 1 : $tax_rate = 1 + ($params['product']->tax_rate / 100);

            Media::addJsDef(array(
                'krona_coins_change' => $actionOrder->coins_change,
                'krona_coins_conversion' => $actionOrder->coins_conversion,
                'krona_coins_in_cart' => $coins_in_cart * $actionOrder->coins_change,
                'krona_order_rounding' => Configuration::get('krona_order_rounding', null, $this->context->shop->id_shop_group, $this->context->shop->id),
                'krona_tax' => $tax,
                'krona_tax_rate' => $tax_rate,
            ));

            $this->context->smarty->assign(array(
                'game_name' => Configuration::get('krona_game_name', $this->context->language->id, $this->context->shop->id_shop_group, $this->context->shop->id),
                'loyalty_name' => Configuration::get('krona_loyalty_name', $this->context->language->id, $this->context->shop->id_shop_group, $this->context->shop->id),
                'krona_coins_in_cart' => $coins_in_cart * $actionOrder->coins_change,
            ));

            return $this->display(__FILE__, 'views/templates/hook/rightColumnProduct.tpl');
        }
    }

    public function hookDisplayProductButtons($params) {
	    return $this->hookDisplayRightColumnProduct($params);
    }

    public function hookDisplayShoppingCartFooter ($params) {

        if (Configuration::get('krona_loyalty_cart_page')) {

            $id_currency = $this->context->currency->id;
            $id_ActionOrder = ActionOrder::getIdActionOrderByCurrency($id_currency);
            $actionOrder = new ActionOrder($id_ActionOrder);

            $order_amount = Configuration::get('krona_order_amount', null, $this->context->shop->id_shop_group, $this->context->shop->id_shop);

            if ($order_amount == 'total_wt') {
                $coins_in_cart = $this->context->cart->getSummaryDetails()['total_price'];
            } elseif ($order_amount == 'total') {
                $coins_in_cart = $this->context->cart->getSummaryDetails()['total_price_without_tax'];
            } elseif ($order_amount == 'total_products_wt') {
                $coins_in_cart = $this->context->cart->getSummaryDetails()['total_products_wt'];
            } elseif ($order_amount == 'total_products') {
                $coins_in_cart = $this->context->cart->getSummaryDetails()['total_products'];
            } else {
                $coins_in_cart = 0;
            }

            if ($actionOrder->minimum_amount > $coins_in_cart) {
                $coins_in_cart = 0;
                $minimum = true;
            }
            else {
                $minimum = false;
            }

            if (Configuration::get('krona_order_rounding', null, $this->context->shop->id_shop_group, $this->context->shop->id) == 'up') {
                $total = ceil($coins_in_cart * $actionOrder->coins_change);
            }
            else {
                $total = floor($coins_in_cart * $actionOrder->coins_change);
            }

            $this->context->smarty->assign(array(
                'game_name' => Configuration::get('krona_game_name', $this->context->language->id, $this->context->shop->id_shop_group, $this->context->shop->id),
                'loyalty_name' => Configuration::get('krona_loyalty_name', $this->context->language->id, $this->context->shop->id_shop_group, $this->context->shop->id),
                'krona_coins_in_cart' => $total,
                'minimum' => $minimum,
                'minimum_amount' => $actionOrder->minimum_amount.' '.$actionOrder->currency_iso,
            ));

            return $this->display(__FILE__, 'views/templates/hook/shoppingCartFooter.tpl');
        }
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
                $order_amount = Configuration::get('krona_order_amount', null, $this->context->shop->id_shop_group, $this->context->shop->id);

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
                    $order_rounding = Configuration::get('krona_order_rounding', null, $this->context->shop->id_shop_group, $this->context->shop->id);

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
                                $search = array('{points}', '{coins}', '{reference}', '{amount}');

                                $total_currency = Tools::displayPrice(Tools::convertPrice($total, $order->id_currency));

                                $replace = array($coins_change, $coins_change, $order->reference, $total_currency);
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

	    $slack = Configuration::get('krona_url', null, $this->context->shop->id_shop_group, $this->context->shop->id);

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


    // Helper for Configuration Values in Object Models
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