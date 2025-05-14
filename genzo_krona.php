<?php

/**
 * Copyright (C) 2023 Emanuel Schiendorfer
 *
 * @author    Emanuel Schiendorfer <https://github.com/eschiendorfer>
 * @copyright 2023 Emanuel Schiendorfer
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

if (!defined('_PS_VERSION_'))
	exit;

require_once _PS_MODULE_DIR_ . 'genzo_krona/autoload.php';

use KronaModule\Action;
use KronaModule\ActionOrder;
use KronaModule\Player;
use KronaModule\PlayerHistory;
use KronaModule\PlayerLevel;

class Genzo_Krona extends Module
{
    public $errors;

	function __construct() {
		$this->name = 'genzo_krona';
		$this->tab = 'front_office_features';
		$this->version = '2.0.1';
		$this->author = 'Emanuel Schiendorfer';
		$this->need_instance = 0;

		$this->bootstrap = true;

		$this->controllers = array('home', 'overview', 'customersettings', 'timeline', 'levels', 'leaderboard', 'loyalty');

	 	parent::__construct();

		$this->displayName = $this->l('Krona Loyalty Points');
		$this->description = $this->l('Build up a community with a points system');
		$this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

	}

	public function install() {
		if (!parent::install() OR
			!$this->executeSqlScript('install') OR
            !$this->registerHook('displayBackOfficeHeader') OR
            !$this->registerHook('displayHeader') OR
            !$this->registerHook('displayCustomerAccount') OR
            !$this->registerHook('displayCustomerAccountForm') OR
            !$this->registerHook('displayCustomerIdentityForm') OR
            !$this->registerHook('displayRightColumnProduct') OR
            !$this->registerHook('displayShoppingCartFooter') OR
            !$this->registerHook('displayKronaCustomer') OR
            !$this->registerHook('displayKronaActionPoints') OR
            !$this->registerHook('actionExecuteKronaAction') OR
            !$this->registerHook('actionCustomerAccountAdd') OR
            !$this->registerHook('actionObjectCustomerUpdateAfter') OR
            !$this->registerHook('actionObjectCustomerDeleteAfter') OR
            !$this->registerHook('actionOrderStatusPostUpdate') OR
            !$this->registerHook('actionOrderEdited') OR
            !$this->registerHook('actionRegisterGenzoCrmEmail') OR
			!$this->registerHook('ModuleRoutes') OR
            !$this->registerInbuiltActions() OR
            !$this->registerExternalActions() OR
            !$this->moveImageFiles() OR
            !$this->installAdminMenus()
        )
			return false;
		return true;
	}

	public function uninstall() {
		if (!parent::uninstall() OR
			    !$this->executeSqlScript('uninstall') OR
                !$this->uninstallAdminMenus()
			)
			return false;
		return true;
	}

    public function executeSqlScript($script) {
        $file = dirname(__FILE__) . '/sql/' . $script . '.sql';
        if (! file_exists($file)) {
            return false;
        }
        $sql = file_get_contents($file);
        if (!$sql) {
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

            $order_title[$id_lang] = 'New Order'; // Just as an example
            $order_message[$id_lang] = 'Your new order (#{reference}) for {amount} brought you {coins} loyalty points. Note, that they will expire on {loyalty_expire_date}.'; // Just as an example
            $order_canceled_message[$id_lang] = 'Unfortunately your order (#{reference}) is no more valid, therefore we had to remove you {coins} loyalty points.'; // Just as an example
            $referral_title_referrer[$id_lang] = 'New referral order'; // Just as an example
            $referral_text_referrer[$id_lang] = 'Your friend {buyer_name} placed an order, which brought you {coins} loyalty points. Note, that they will expire on {loyalty_expire_date}.'; // Just as an example
            $loyalty_expire_title[$id_lang] = 'Loyalty Points expired'; // Just as an example
            $loyalty_expire_message[$id_lang] = 'Unfortunately today expired {loyalty_points} of your loyalty points.'; // Just as an example
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
            if (!Configuration::get('krona_order_title', null, $id_shop_group, $id_shop)) {
                Configuration::updateValue('krona_order_title', $order_title, false, $id_shop_group, $id_shop);
            }
            if (!Configuration::get('krona_order_message', null, $id_shop_group, $id_shop)) {
                Configuration::updateValue('krona_order_message', $order_message, false, $id_shop_group, $id_shop);
            }
            if (!Configuration::get('krona_order_canceled_message', null, $id_shop_group, $id_shop)) {
                Configuration::updateValue('krona_order_canceled_message', $order_canceled_message, false, $id_shop_group, $id_shop);
            }
            if (!Configuration::get('krona_referral_title_referrer', null, $id_shop_group, $id_shop)) {
                Configuration::updateValue('krona_referral_title_referrer', $referral_title_referrer, false, $id_shop_group, $id_shop);
            }
            if (!Configuration::get('krona_referral_text_referrer', null, $id_shop_group, $id_shop)) {
                Configuration::updateValue('krona_referral_text_referrer', $referral_text_referrer, false, $id_shop_group, $id_shop);
            }
            if (!Configuration::get('krona_loyalty_expire_title', null, $id_shop_group, $id_shop)) {
                Configuration::updateValue('krona_loyalty_expire_title', $loyalty_expire_title, false, $id_shop_group, $id_shop);
            }
            if (!Configuration::get('krona_loyalty_expire_message', null, $id_shop_group, $id_shop)) {
                Configuration::updateValue('krona_loyalty_expire_message', $loyalty_expire_message, false, $id_shop_group, $id_shop);
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
            if (!Configuration::get('krona_referral_active', null, $id_shop_group, $id_shop)) {
                Configuration::updateValue('krona_referral_active', 1, false, $id_shop_group, $id_shop);
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
            if (!Configuration::get('krona_loyalty_expire_method', null, $id_shop_group, $id_shop)) {
                Configuration::updateValue('krona_loyalty_expire_method', 'none', false, $id_shop_group, $id_shop);
            }
            if (!Configuration::get('krona_loyalty_expire_days', null, $id_shop_group, $id_shop)) {
                Configuration::updateValue('krona_loyalty_expire_days', 365, false, $id_shop_group, $id_shop);
            }
            if (!Configuration::get('krona_referral_order_nbr', null, $id_shop_group, $id_shop)) {
                Configuration::updateValue('krona_referral_order_nbr', 1, false, $id_shop_group, $id_shop);
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

        // Coupon Template
        $id_cart_rule = CartRule::getIdByCode('KRONA');
        if (!$id_cart_rule) {
            $coupon = new CartRule();
            $coupon->code = 'KRONA';
            $coupon->highlight = 1;
            $coupon->reduction_amount = 0;
            $coupon->date_from = date("Y-m-d H:i:s");
            $coupon->date_to = date("Y-m-d 23:59:59", strtotime("+1 year"));
            $coupon->active = 0;
            foreach ($ids_lang as $id_lang) {
                $coupon->name[$id_lang] = 'KronaTemplate: Orders';
            }

            $coupon->add();
        }

        return true;
    }

    private function registerAction($module_name, $action_name, $title, $message) {

        $module_name = pSQL($module_name);
        $action_name = pSQL($action_name);

        // We prevent that same action is registered multiple times
        if (!Action::getIdAction($module_name, $action_name)) {
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
        }

        return true;
    }

    public function registerExternalActions() {
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

    public function installAdminMenus() {
	    if (
	        !$this->uninstallAdminMenus() OR
            !$this->registerAdminMenu('AdminCustomers', 'AdminGenzoKronaActions', 'Krona', 1) OR
            !$this->registerAdminMenu('AdminCustomers', 'AdminGenzoKronaOrders', 'Krona Orders', 0) OR
            !$this->registerAdminMenu('AdminCustomers', 'AdminGenzoKronaLevels', 'Krona Levels', 0) OR
            !$this->registerAdminMenu('AdminCustomers', 'AdminGenzoKronaPlayers', 'Krona Players', 0) OR
            !$this->registerAdminMenu('AdminCustomers', 'AdminGenzoKronaCoupons', 'Krona Coupons', 0) OR
            !$this->registerAdminMenu('AdminCustomers', 'AdminGenzoKronaGroups', 'Krona Groups', 0) OR
            !$this->registerAdminMenu('AdminCustomers', 'AdminGenzoKronaSettings', 'Krona Settings', 0) OR
            !$this->registerAdminMenu('AdminCustomers', 'AdminGenzoKronaSupport', 'Krona Support', 0)
        ) {
	        return false;
        }
	    return true;
    }

    public function uninstallAdminMenus() {
	    $this->removeAdminMenu('AdminGenzoKronaActions');
        $this->removeAdminMenu('AdminGenzoKronaOrders');
        $this->removeAdminMenu('AdminGenzoKronaLevels');
        $this->removeAdminMenu('AdminGenzoKronaPlayers');
        $this->removeAdminMenu('AdminGenzoKronaCoupons');
        $this->removeAdminMenu('AdminGenzoKronaGroups');
        $this->removeAdminMenu('AdminGenzoKronaSettings');
        $this->removeAdminMenu('AdminGenzoKronaSupport');

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

        $file = $_FILES['krona_avatar'] ?? $_FILES['avatar']; // 'avatar' is deprecated but needed for backward compatibility

	    if ($file['tmp_name']) {

	        if ($file['size'] > 5242880) {
	            $this->errors[] = $this->l('Allowed file size is max 5MB');
	            return false;
            }

            // Handling File
            $file_tmp = $file['tmp_name'];

            // Check if it's an image
            (@is_array(getimagesize($file_tmp))) ? $image = true : $image = false;

            if ($image) {
                $file_path = _PS_UPLOAD_DIR_. 'genzo_krona/img/avatar/'; // We need absolute path

                if (!file_exists($file_path)) {
                    mkdir($file_path, 0777, true);
                }

                $filename = $id_customer . '.jpg';

                // Remove white background
                if (class_exists('SpielezarHelper')) {
                    SpielezarHelper::cropImageWithSpielezarStyle($file_tmp, false);
                }

                // Check if we first need to cut the image to a square
                list($width, $height) = getimagesize($file_tmp);

                if ($width!=$height) {

                    if ($width > $height) {
                        $dstX = round(($width-$height)/2);
                        $dstY = 0;
                    }
                    else {
                        $dstX = 0;
                        $dstY = round(($height-$width)/2);
                    }

                    ImageManager::cut($file_tmp, $file_tmp, min($width, $height), min($width, $height), 'jpg', $dstX, $dstY);
                }

                ImageManager::resize($file_tmp, $file_path.$filename, 100, 100);
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
        $file_extension = pathinfo($_FILES['icon']['name'], PATHINFO_EXTENSION); // Original extension

        $ugly = array('ä', 'ö', 'ü');
        $nice = array('ae', 'oe', 'ue');
        $file_name = str_replace($ugly, $nice, $file_name);

        // Remove anything which isn't a word, whitespace, number or any of the following caracters -_[]().
        $file_name = preg_replace("([^\w\s\d\-_\[\]\(\).])", '', $file_name);
        // Remove any runs of periods (points, comas)
        $file_name = preg_replace("([\.]{2,})", '', $file_name);
        // Remove spaces and lower the letters
        $file_name = strtolower(str_replace(' ','-',$file_name));

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
                $file_path = _PS_UPLOAD_DIR_ . 'genzo_krona/img/icon/'; // We need absolute path

                if (!file_exists($file_path)) {
                    mkdir($file_path, 0777, true);
                }

                move_uploaded_file($file_tmp, $file_path.$file_name.$file_extension);

                ImageManager::resize($file_path.$file_name.$file_extension, $file_path.$file_name.'_small.png', 30, 30);
                ImageManager::resize($file_path.$file_name.$file_extension, $file_path.$file_name.'_middle.png', 80, 80);
                ImageManager::resize($file_path.$file_name.$file_extension, $file_path.$file_name.'_big.png', 120, 120);

                unlink($file_path.$file_name.$file_extension);
            }
            else {
                $this->errors[] = $this->l('Image Upload failed');
            }
            return $file_name;
        }
        return false;
    }

    public function moveImageFiles() {

        // Avatar files
        $old_folder_path = _PS_MODULE_DIR_.'genzo_krona/views/img/avatar/';
        $new_folder_path = _PS_UPLOAD_DIR_.'genzo_krona/img/avatar/';
        $this->moveFiles($old_folder_path, $new_folder_path);

        // Icon/Level files
        $old_folder_path = _PS_MODULE_DIR_.'genzo_krona/views/img/icon/';
        $new_folder_path = _PS_UPLOAD_DIR_.'genzo_krona/img/icon/';
        $this->moveFiles($old_folder_path, $new_folder_path);

        return true;
    }

    public function moveFiles($old_folder_path, $new_folder_path) {

        if (!file_exists($new_folder_path)) {
            mkdir($new_folder_path, 0777, true);
        }

        $ignore = array(".","..","Thumbs.db", 'index.php');
        $original_files = scandir($old_folder_path);

        foreach ($original_files as $file) {
            if (!in_array($file, $ignore)){
                rename($old_folder_path.$file, $new_folder_path.$file); // rename the file
            }
        }
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
        }
    }

    public function hookDisplayKronaCustomer($params) {

	    $id_customer = (int)$params['id_customer'];

        // Check if a cache is available?
        $cacheKey = 'Krona::displayKronaCustomer_'.$id_customer;
        $cacheInstance = Cache::getInstance();

        if ($cachedData = $cacheInstance->get($cacheKey)) {
            return $cachedData;
        }


        // Check if player is active
        $playerObj = new Player($id_customer);

        if ($playerObj->active) {

            $name = Configuration::get('krona_total_name', $this->context->language->id, $this->context->shop->id_shop_group, $this->context->shop->id_shop);

            $player = array(
                'pseudonym' => $playerObj->display_name,
                'avatar' => $playerObj->avatar_full && file_exists(_PS_UPLOAD_DIR_ . 'genzo_krona/img/avatar/' . $playerObj->avatar) ? $playerObj->avatar_full : '/upload/genzo_krona/img/avatar/no-avatar.jpg',
                'total' => $playerObj->total . ' ' . $name,
                'rank' => $playerObj->getRank().' '.$this->l('from').' '.Player::getTotalPlayers(),
                'level' => PlayerLevel::getLastPlayerLevel($id_customer)->name,
                'url' => $this->context->link->getModuleLink('genzo_krona', 'overview') . '/' . strtolower($playerObj->referral_code),
            );
        }
        else {
            $player = [];
        }

        // Store cache
        $cacheInstance->set($cacheKey, $player, SpielezarHelper::CACHE_TTL_1_WEEK);

        return $player;
    }

    public function hookDisplayKronaActionPoints($params) {

	    $module_name = pSQL($params['module_name']);
	    $action_name = pSQL($params['action_name']);
        $id_customer = (int)$params['id_customer'];

	    $id_action = Action::getIdAction($module_name, $action_name);
        $action = new Action($id_action);
	    $player = new Player($id_customer);

        if (!$player->active) {
            $info['error'][] = 'This player is not active';
        }
        if ($player->banned) {
            $info['error'][] = 'This player is banned';
        }

        if (!$id_action || !$action->active) {
            $info['error'][] = 'This action is not active';
        }

        if (empty($info['error'])) {

            if ($info = $player->checkIfPlayerStillCanExecuteAction($id_customer, $action, true)) {
                $info['points'] = $action->points_change;
            }
            else {
                $info['points'] = 0;
            }

            $info['execution_type'] = $action->execution_type;
            $info['execution_max'] = (int)$action->execution_max;
        }

        return $info;

    }

    public function hookDisplayHeader() {

        // CSS
        // Todo: reimplement this for public version (make setting to disable loading of css files)
        // $this->context->controller->addCSS($this->_path.'/views/css/krona.css');
        // $this->context->controller->addCSS($this->_path.'/views/css/krona_custom.css');

        // JS
        $this->context->controller->addJS($this->_path . '/views/js/krona.js');

        if (Action::checkIfActionIsActive('genzo_krona', 'page_visit') AND
            $this->context->customer->isLogged()) {
            Media::addJsDef(array('id_customer' => $this->context->customer->id));
            $this->context->controller->addJS($this->_path . '/views/js/page_visit.js');
        }

        if (
            Configuration::get('krona_notification', null, $this->context->shop->id_shop_group, $this->context->shop->id) AND
            $this->context->customer->isLogged()
        ) {
            $player = new Player($this->context->customer->id);
            if ($player->active) {
                Media::addJsDef(array('id_customer' => $this->context->customer->id));
                $this->context->controller->addJS($this->_path . '/views/js/notification.js');
            }
        }
    }

    public function hookDisplayCustomerAccount() {

        $this->context->smarty->assign(array(
            'game_name' => Configuration::get('krona_game_name', $this->context->language->id, $this->context->shop->id_shop_group, $this->context->shop->id),
        ));

        return $this->display(__FILE__, 'views/templates/hook/customerAccount.tpl');
    }

    public function hookDisplayCustomerAccountForm($params) {
	    if (Configuration::get('krona_referral_active')) {
            return $this->display(__FILE__, 'views/templates/hook/createAccountForm.tpl');
        }
    }

    public function hookDisplayCustomerIdentityForm($params) {

        if ($this->context->theme->directory=='genzo_theme') {
            require_once _PS_MODULE_DIR_ . 'genzo_krona/controllers/front/customersettings.php';

            $customerSettingsController = new Genzo_KronaCustomerSettingsModuleFrontController();
            $customerSettingsController->initContent();

            return $this->display(__FILE__, 'views/templates/hook/customerIdentityForm.tpl');
        }

        return '';
    }

    public function hookDisplayRightColumnProduct($params) {

	    $id_shop_group = $this->context->shop->id_shop_group;
	    $id_shop = $this->context->shop->id_shop;

	    if (Configuration::get('krona_loyalty_product_page', null, $id_shop_group, $id_shop) AND Configuration::get('krona_loyalty_active', null, $id_shop_group, $id_shop)) {

            $id_currency = $this->context->currency->id;
            $id_ActionOrder = ActionOrder::getIdActionOrderByCurrency($id_currency);
            $actionOrder = new ActionOrder($id_ActionOrder);

            if ($actionOrder->active == 0) {
                return null;
            }

            $order_amount = Configuration::get('krona_order_amount', null, $id_shop_group, $id_shop);

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

            $id_product = (int)(Tools::getValue('id_product'));

            if ($tax) {
                $tax_rate = 1;
            }
            else {
                $tax_rate = 1 + (Tax::getProductTaxRate($id_product) / 100);
            }

            // Check which coin_change is relevant
            $coins_change = $actionOrder->coins_change;

            if ($this->context->customer->id) {
                $player = new Player($this->context->customer->id);

                if ($player->id_customer_referrer && (Configuration::get('krona_referral_order_nbr', null, $id_shop_group, $id_shop) > Player::getNbrOfOrders($this->context->customer->id, true))) {
                    $coins_change = $actionOrder->coins_change_buyer;
                }
            }


            Media::addJsDef(array(
                'krona_coins_change' => $coins_change,
                'krona_coins_change_max' => $actionOrder->coins_change_max,
                'krona_coins_conversion' => $actionOrder->coins_conversion,
                'krona_coins_in_cart' => $coins_in_cart * $actionOrder->coins_change,
                'krona_order_rounding' => Configuration::get('krona_order_rounding', null, $id_shop_group, $id_shop),
                'krona_tax' => $tax,
                'krona_tax_rate' => $tax_rate,
            ));

            $this->context->controller->addJS($this->_path.'/views/js/krona-loyalty.js');

            $this->context->smarty->assign(array(
                'game_name' => Configuration::get('krona_game_name', $this->context->language->id, $id_shop_group, $id_shop),
                'loyalty_name' => Configuration::get('krona_loyalty_name', $this->context->language->id, $id_shop_group, $id_shop),
                'krona_coins_in_cart' => $coins_in_cart * $actionOrder->coins_change,
            ));

            return $this->display(__FILE__, 'views/templates/hook/rightColumnProduct.tpl');
        }

        return null;
    }

    public function hookDisplayProductButtons($params) {
	    return $this->hookDisplayRightColumnProduct($params);
    }

    public function hookDisplayShoppingCartFooter($params) {

	    if (!Configuration::get('krona_loyalty_active')) {
            return null;
        }

        if (!Configuration::get('krona_loyalty_checkout_conversion') && !Configuration::get('krona_loyalty_cart_page')) {
            return null;
        }

	    if (Tools::isSubmit('convertLoyalty') && $loyalty_points = Tools::getValue('loyalty')) {
	        $this->convertLoyaltyPointsToCoupon($this->context->customer->id, $loyalty_points);
        }

        $id_currency = $this->context->currency->id;

        if (!$id_action_order = ActionOrder::getIdActionOrderByCurrency($id_currency)) {
            return null;
        }

        $actionOrder = new ActionOrder($id_action_order);

        if (!$actionOrder->coins_conversion) {
            return null;
        }

        $order_amount = Configuration::get('krona_order_amount');

        if ($order_amount == 'total_wt') {
            $cart_value = $this->context->cart->getSummaryDetails()['total_price'];
        } elseif ($order_amount == 'total') {
            $cart_value = $this->context->cart->getSummaryDetails()['total_price_without_tax'];
        } elseif ($order_amount == 'total_products_wt') {
            $cart_value = $this->context->cart->getSummaryDetails()['total_products_wt'];
        } elseif ($order_amount == 'total_products') {
            $cart_value = $this->context->cart->getSummaryDetails()['total_products'];
        } else {
            $cart_value = 0;
        }

        // Check if coupons should be substracted
        if (Configuration::get('krona_order_coupon') && ($order_amount == 'total_products_wt' OR $order_amount == 'total_products')) {
            $cart_value = $cart_value - $this->context->cart->getSummaryDetails()['total_discounts'];
        }

        if ($actionOrder->minimum_amount > $cart_value) {
            $cart_value = 0;
            $minimum = true;
        }
        else {
            $minimum = false;
        }

        // Check which coin_change is relevant
        $player = ($this->context->customer->id) ? new Player($this->context->customer->id) : false;

        $coins_change = $actionOrder->coins_change;

        if ($player instanceof Player &&
            $player->id_customer_referrer &&
            (Configuration::get('krona_referral_order_nbr', null, $this->context->shop->id_shop_group, $this->context->shop->id_shop) > Player::getNbrOfOrders($this->context->customer->id, true))
        ) {
            $coins_change = $actionOrder->coins_change_buyer;
        }

        // Check the rounding method -> nearest is standard
        $order_rounding = Configuration::get('krona_order_rounding');
        if ($order_rounding == 'down') {
            $total = floor($cart_value * $coins_change);
        }
        elseif ($order_rounding == 'up') {
            $total = ceil($cart_value * $coins_change);
        }
        else {
            $total = round($cart_value * $coins_change);
        }

        // Check for the max threshold
        if ($actionOrder->coins_change_max) {
            $total = min($total, $actionOrder->coins_change_max);
        }

        // Loyalty conversion
        $this->context->controller->addJS($this->_path.'/views/js/krona-loyalty.js');

        Media::addJsDef(
            array(
                'conversion' => $actionOrder->coins_conversion,
                'loyalty_max' => $player ? min($player->loyalty, $cart_value/$actionOrder->coins_conversion) : $cart_value/$actionOrder->coins_conversion,
            )
        );

        if ($player) {
            $player = json_decode(json_encode($player), true);
        }

        $this->context->smarty->assign(array(
            'display_coins' => Configuration::get('krona_loyalty_cart_page'),
            'display_conversion' => Configuration::get('krona_loyalty_checkout_conversion'),
            'game_name' => Configuration::get('krona_game_name', $this->context->language->id),
            'loyalty_name' => Configuration::get('krona_loyalty_name', $this->context->language->id),
            'krona_coins_in_cart' => $total,
            'minimum' => $minimum,
            'minimum_amount' => $actionOrder->minimum_amount.' '.$actionOrder->currency_iso,
            'conversion' => number_format(round($total * $actionOrder->coins_conversion, 2),2).' '.$actionOrder->currency_iso,
            'player' => $player,
        ));

        return $this->display(__FILE__, 'views/templates/hook/shoppingCartFooter.tpl');

    }

    public function hookActionExecuteKronaAction($params) {

	    // Hook values: module_name, action_name, id_customer, action_url, action_message
	    $module_name = pSQL($params['module_name']);
	    $action_name = pSQL($params['action_name']);

        if (!$id_action = Action::getIdAction($module_name, $action_name)) {
            return 'Action not found!';
        }

	    if (!$id_customer = (int)$params['id_customer']) {
	        return 'ID Customer not set!';
        }

	    if (isset($this->context->customer->id) && $this->context->customer->id == $id_customer) {
	        $customer = $this->context->customer;
        }
	    else {
            $customer = new Customer($id_customer);
        }

        $action = new Action($id_action, null, $customer->id_shop);

        /* @var $player Player */
        $player = new Player($id_customer);

        if (!$player->active || $player->banned || !$action->active) {
            return 'Player or Action not active.';
        }
        else {

            // Check if the User is still allowed to execute this action
            if ($player->checkIfPlayerStillCanExecuteAction($id_customer, $action)) {

                $history = new PlayerHistory();
                $history->id_customer = $customer->id;
                $history->id_action = $id_action;
                $history->points = $action->points_change;

                if (!empty($params['action_url'])) {
                    $history->url = $params['action_url']; // Action url is not mandatory
                }

                // Preparing the lang array for the history message
                $ids_lang = Language::getIDs();
                $message = !empty($params['action_message']) ? $params['action_message'] : array();

                foreach ($ids_lang as $id_lang) {

                    if (empty($params['action_message'])) {
                        $message[$id_lang] = $action->message[$id_lang];
                    }

                    // After defining the message array we replace the shortcodes -> shortcodes can be used for external messages too
                    $search = array('{points}', '{coins}');
                    $replace = array($history->points, $history->coins);
                    $history->message[$id_lang] = str_replace($search, $replace, $message[$id_lang]);

                    $history->title[$id_lang] = pSQL($action->title[$id_lang]);
                }

                $history->add();

                Player::updatePlayerLevels($id_customer);
            }
        }
        return true;
	}

    public function hookActionCustomerAccountAdd($params) {

	    // Check if the customer has any referrer
	    $id_customer_referrer = 0;

	    if (isset($params['_POST']['referral_code']) && ($referred_by = pSQL($params['_POST']['referral_code']))) {
	        $id_customer_referrer = Player::getIdByReferralCode($referred_by);
        }

	    // Add the player
        $customer = $params['newCustomer'];

        $player = new Player();
        $player->id_customer = $customer->id;
        $player->id_customer_referrer = $id_customer_referrer;
        $player->referral_code = Player::generateReferralCode();
        $player->avatar = 'no-avatar.jpg';
        $player->active = (int)\Configuration::get('krona_customer_active', null, $customer->id_shop_group, $customer->id_shop);

        $player->add();

    }

    public function hookActionObjectCustomerUpdateAfter($params) {

        if (Tools::isSubmit('submitKronaCustomerSettings')) {
            require_once _PS_MODULE_DIR_ . 'genzo_krona/controllers/front/customersettings.php';
            $customerSettingsController = new Genzo_KronaCustomerSettingsModuleFrontController();
            $customerSettingsController->saveCustomerSettings();
        }
    }

    public function hookActionObjectCustomerDeleteAfter($params) {

        /* @var $customer \Customer */
        $customer = $params['object'];
        $player = new Player($customer->id);
        $player->delete();
    }

	public function hookActionOrderStatusPostUpdate($params) {

	    // $newStatus = $params['newOrderStatus'];
	    $id_order = $params['id_order'];
        $order = new Order($id_order);

        return $this->processOrder($order);
    }

	public function hookActionOrderEdited($params) {
	    $order = $params['order'];
        return $this->processOrder($order);
    }

    /* @param $order Order */
    public function processOrder($order) {

        // Check if there is already history entry
        $id_history = PlayerHistory::getIdHistoryByIdOrder($order->id_customer, $order->id);

        if (!$krona_order_state = Configuration::get('krona_order_state', null, $order->id_shop_group, $order->id_shop)) {
            return true;
        }

        $ids_order_state = explode(',', $krona_order_state);

        // Check if the status is relevant
        if (in_array($order->current_state, $ids_order_state) || $id_history) {

            // Check ActionOrder -> This is basically checking the currency
            $id_action_order = ActionOrder::getIdActionOrderByCurrency($order->id_currency);
            $actionOrder = new ActionOrder($id_action_order);

            if ($actionOrder->active) {

                // Get Total amount of the order
                $order_amount = Configuration::get('krona_order_amount', null, $order->id_shop_group, $order->id_shop);

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

                // Check if coupons should be substracted (in total they are already substracted)
                if (Configuration::get('krona_order_coupon', null, $order->id_shop_group, $order->id_shop)) {
                    if ($order_amount == 'total_products_wt') {
                        $total = $total - $order->total_discounts_tax_incl;
                    }
                    elseif ($order_amount == 'total_products') {
                        $total = $total - $order->total_discounts_tax_excl;
                    }
                }

                // Check if total is high enough
                if ($total < $actionOrder->minimum_amount) {
                    return false;
                }

                // Check if Player exits
                $player = new Player($order->id_customer);

                if (!$player->active || $player->banned) {
                    return false;
                }

                $historyBuyer = new PlayerHistory($id_history);

                $id_history_referrer = PlayerHistory::getIdHistoryByIdOrder($player->id_customer_referrer, $order->id);
                $historyReferrer = new PlayerHistory($id_history_referrer);

                $languages = Language::getLanguages();

                if (in_array($order->current_state, $ids_order_state)) {

                    // Check if referral is relevant
                    $coins_change = $actionOrder->coins_change;
                    $coins_change_referrer = 0;

                    if (
                        $player->id_customer_referrer &&
                        Configuration::get('krona_referral_order_nbr') &&
                        (Configuration::get('krona_referral_order_nbr', null, $order->id_shop_group, $order->id_shop) >= Player::getNbrOfOrders($order->id_customer, true))
                    ) {
                        $coins_change = $actionOrder->coins_change_buyer;
                        $coins_change_referrer = $actionOrder->coins_change_referrer;
                    }

                    // Check the rounding method -> nearest is standard
                    $order_rounding = Configuration::get('krona_order_rounding', null, $order->id_shop_group, $order->id_shop);

                    if ($order_rounding == 'down') {
                        $coins = floor($total * $coins_change);
                        $coins_referrer = floor($total * $coins_change_referrer);
                    }
                    elseif ($order_rounding == 'up') {
                        $coins = ceil($total * $coins_change);
                        $coins_referrer = ceil($total * $coins_change_referrer);
                    }
                    else {
                        $coins = round($total * $coins_change);
                        $coins_referrer = round($total * $coins_change_referrer);
                    }

                    // Check for maximum
                    if ($actionOrder->coins_change_max) {
                        $coins = min($coins, $actionOrder->coins_change_max);
                        $coins_referrer = min($coins_referrer, $actionOrder->coins_change_max);
                    }

                    $coins = round($coins);
                    $coins_referrer =round($coins_referrer);

                    // Handle the buyer
                    $historyBuyer->id_customer = $order->id_customer;
                    $historyBuyer->id_action_order = $id_action_order;
                    $historyBuyer->id_order = $order->id;
                    $historyBuyer->url = $this->context->link->getPageLink('history');
                    $historyBuyer->coins = $coins;

                    // Expiring
                    $expire_method = Configuration::get('krona_loyalty_expire_method', null, $order->id_shop_group, $order->id_shop);

                    if ($expire_method!='none') {

                        $expire_days = Configuration::get('krona_loyalty_expire_days', null, $order->id_shop_group, $order->id_shop);
                        $expire_date = date("Y-m-d H:i:s", strtotime("+{$expire_days} days"));

                        // As the merchants don't want to update the expire date, after a order state update
                        if (!$id_history) {

                            $historyBuyer->loyalty_expire_date = $expire_date;
                            $historyReferrer->loyalty_expire_date = $expire_date;

                            // Updating other old expiring dates of customer, if refreshing method
                            if ($expire_method == 'refreshing') {
                                $sql_customer = ' (id_customer='.$order->id_customer.' OR id_customer='.$player->id_customer_referrer.')';
                                DB::getInstance()->update('genzo_krona_player_history', ['loyalty_expire_date' => $expire_date], 'loyalty-loyalty_used-loyalty_expired > 0 AND '.$sql_customer);
                            }
                        }
                    }

                    // Handling lang fields for Player History
                    $title = array();
                    $message = array();

                    foreach ($languages as $language) {

                        $id_lang = $language['id_lang'];

                        $title[$id_lang] = Configuration::get('krona_order_title', $id_lang, $order->id_shop_group, $order->id_shop);
                        $message[$id_lang] = Configuration::get('krona_order_message', $id_lang, $order->id_shop_group, $order->id_shop);

                        // Replace message variables
                        $search = array('{coins}', '{reference}', '{amount}', '{loyalty_expire_date}');

                        $total_currency = Tools::displayPrice(Tools::convertPrice($total, $order->id_currency));

                        $replace = array($coins, $order->reference, $total_currency, date($language['date_format_lite'], strtotime($historyBuyer->loyalty_expire_date)));
                        $message[$id_lang] = str_replace($search, $replace, $message[$id_lang]);

                        $historyBuyer->message[$id_lang] = pSQL($message[$id_lang]);
                        $historyBuyer->title[$id_lang] = pSQL($title[$id_lang]);
                    }

                    $historyBuyer->save();
                    Player::updatePlayerLevels($order->id_customer);

                    // Handle the referrer
                    if ($coins_referrer) {

                        $historyReferrer->id_customer = $player->id_customer_referrer;
                        $historyReferrer->id_action_order = $id_action_order;
                        $historyReferrer->id_order = $order->id;
                        $historyReferrer->coins = $coins_referrer;

                        // Handling lang fields for Player History
                        $title = array();
                        $message = array();

                        foreach ($languages as $language) {

                            $id_lang = $language['id_lang'];

                            $title[$id_lang] = Configuration::get('krona_referral_title_referrer', $id_lang, $order->id_shop_group, $order->id_shop);
                            $message[$id_lang] = Configuration::get('krona_referral_text_referrer', $id_lang, $order->id_shop_group, $order->id_shop);

                            // Replace message variables
                            $search = array('{coins}', '{buyer_name}', '{loyalty_expire_date}');
                            $replace = array($coins_referrer, $player->firstname.' '.$player->lastname[0].'.', date($language['date_format_lite'], strtotime($historyBuyer->loyalty_expire_date)));

                            $message[$id_lang] = str_replace($search, $replace, $message[$id_lang]);

                            $historyReferrer->message[$id_lang] = pSQL($message[$id_lang]);
                            $historyReferrer->title[$id_lang] = pSQL($title[$id_lang]);
                        }

                        $historyReferrer->save();
                        Player::updatePlayerLevels($player->id_customer_referrer);
                    }
                }
                else {

                    // When an order is cancelled or gets a status that doesn't deserve points
                    $historyBuyer->loyalty = 0;
                    $historyBuyer->coins = 0;

                    $ids_lang = Language::getIDs();

                    foreach ($ids_lang as $id_lang) {
                        $historyBuyer->comment[$id_lang] = Configuration::get('krona_order_canceled_message', $id_lang, $order->id_shop_group, $order->id_shop);
                    }

                    $historyBuyer->update();

                    // Check for referrer
                    if ($id_history_referrer) {

                        $historyReferrer->loyalty = 0;
                        $historyReferrer->coins = 0;

                        foreach ($ids_lang as $id_lang) {
                            $historyReferrer->comment[$id_lang] = Configuration::get('krona_order_canceled_message', $id_lang, $order->id_shop_group, $order->id_shop);
                        }

                        $historyReferrer->update();

                    }

                    // Theoretically we need to check here, if a customer loses a level after the cancel
                    // But since this is too complex, the merchant should do this manually
                }
            }
        }

        return true;
    }


    // Email Hooks
    public function hookActionRegisterGenzoCrmEmail($params) {

        $actions = array(
            'new_level_achieved' => array (
                'title' => 'Level achieved',
                'subtitle' => 'You have achieved a new Level.',
                'shortcodes' => array(
                    'level' => $this->l('This will display the name of the level.'),
                    'next_level' => $this->l('This will display the name of the level.'),
                    'reward' => $this->l('This will display a sentence of the reward.'),
                ),
            ),
        );

        return $actions;
    }

    public function hookModuleRoutes() {

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
            'module-genzo_krona-overview-player' => array(
                'controller' => 'overview',
                'rule' => $slack.'/overview/{referral_code}',
                'keywords' => array(
                    'referral_code' => array(
                        'regexp' => '[_a-zA-Z0-9-\pL]*',
                        'param' => 'referral_code',
                    )
                ),
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

    // Helper
    public function convertLoyaltyPointsToCoupon($id_customer, $loyalty_points, $penaltyMode = false) {

	    $player = new Player($id_customer);

        // Make sure penalty mode works trough, even if there aren't enough loyalty points left
        if ($penaltyMode && ($loyalty_points > $player->loyalty)) {
            $loyalty_points = $player->loyalty;
        }

	    // Basic checks
        if ($loyalty_points > $player->loyalty) {
            return false;
        }
        elseif (!$loyalty_points > 0) {
            return false;
        }

        // Add History
        $this->updatePlayerHistoryWhenConvertingLoyalty($id_customer, $loyalty_points);

        if ($penaltyMode) {
            return true; // The history should be added there
        }

        $actionOrder = new ActionOrder($this->context->currency->id);
        $ids_lang = Language::getIDs();

        $history = new PlayerHistory();
        $history->id_customer = $id_customer;
        $history->id_action = 0;
        $history->id_action_order = $actionOrder->id_action_order;

        $points_name = array();

        foreach ($ids_lang as $id_lang) {
            $points_name[$id_lang] = Configuration::get('krona_loyalty_name', $id_lang, $this->context->shop->id_shop_group, $this->context->shop->id);
            $history->title[$id_lang] = $points_name[$id_lang]. ' '. $this->l('Conversion');
            $history->message[$id_lang] = sprintf($this->l('You converted %s into a coupon.'),$loyalty_points.' '.$points_name[$id_lang]);
        }
        $history->force_display = -$loyalty_points;
        $history->add();


	    // Add Coupon
        $id_cart_rule = CartRule::getIdByCode('KRONA');
        $coupon = new CartRule($id_cart_rule);

        // Clone the cart rule and override some values
        $coupon->id_customer = $id_customer;
        $coupon->reduction_amount = ($loyalty_points * $actionOrder->coins_conversion);

        // Merchant can set date in cart rule, we need the difference between the dates
        if ($coupon->date_from && $coupon->date_to) {
            $validity = strtotime($coupon->date_to) - strtotime($coupon->date_from);
            $coupon->date_to = date("Y-m-d 23:59:59", strtotime("+{$validity} seconds"));
        }
        else {
            $coupon->date_to = date("Y-m-d 23:59:59", strtotime("+1 year")); // Default
        }
        $coupon->date_from = date("Y-m-d H:i:s");

        foreach ($ids_lang as $id_lang) {
            $game_name = Configuration::get('krona_game_name', $id_lang, $this->context->shop->id_shop_group, $this->context->shop->id);
            $coupon->name[$id_lang] = $game_name . ' - ' . $loyalty_points . ' ' . $points_name[$id_lang];
        }

        $prefix = Configuration::get('krona_coupon_prefix');
        $code = strtoupper(Tools::passwdGen(6));

        $coupon->code = ($prefix) ? $prefix.'-'.$code : $code;
        $coupon->active = true;
        $coupon->reduction_tax = true;
        $coupon->add();

        CartRule::copyConditions($id_cart_rule, $coupon->id);

        // Immediately use the generated coupon in the order
        if (($cartRule = new CartRule(CartRule::getIdByCode($coupon->code))) && Validate::isLoadedObject($cartRule)){
            if ($error = $cartRule->checkValidity($this->context, false, true)) {
                $this->errors[] = $error;
            } else {
                $this->context->cart->addCartRule($cartRule->id);
                CartRule::autoAddToCart($this->context);
                if (Configuration::get('PS_ORDER_PROCESS_TYPE') == 1) {
                    Tools::redirect('index.php?controller=order-opc&addingCartRule=1');
                }
                Tools::redirect('index.php?controller=order&addingCartRule=1');
            }
        }

        $this->confirmation = $this->l('Your Coupon was sucessfully created.');

        return true;
    }

    private function updatePlayerHistoryWhenConvertingLoyalty($id_customer, $loyalty_points) {

	    // Get the first history that needs to be used
        $query = new DbQuery();
        $query->select('id_history');
        $query->from('genzo_krona_player_history');
        $query->where('(loyalty-loyalty_used-loyalty_expired) > 0 AND id_customer = ' . $id_customer);
        $query->orderby('loyalty_expire_date ASC, id_history ASC');
        $id_history = Db::getInstance()->getValue($query);

        $playerHistory = new PlayerHistory($id_history);
        $loyalty_left_over = $playerHistory->loyalty-$playerHistory->loyalty_used-$playerHistory->loyalty_expired;

        // The history is enough to fulfill the conversion
        if ($loyalty_points <= $loyalty_left_over) {
            $playerHistory->loyalty_used += $loyalty_points;
            $playerHistory->update();
            return true;
        }
        else {
            $playerHistory->loyalty_used += $loyalty_left_over;
            $playerHistory->update();
            return $this->updatePlayerHistoryWhenConvertingLoyalty($id_customer, ($loyalty_points-$loyalty_left_over));
        }

    }

    // Todo for version 2.1
    // Show and allow edit for referral customers
    // Improving possible action table
    // When you look at the overview, you have a Rank, if you click on it it takes you to the top of the leaderboard. It would be nice if it were take you to yourself, in the list.  So you click on it, and maybe it takes you to page 23, which you are listed on.  Then you can easily see who is around you on the rank list. Also, it would be nice if you could search for players in the rank list
    // Maybe a BO cleanup of used coupons...
}