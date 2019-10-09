<?php

/**
 * Copyright (C) 2019 Emanuel Schiendorfer
 *
 * @author    Emanuel Schiendorfer <https://github.com/eschiendorfer>
 * @copyright 2019 Emanuel Schiendorfer
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
use KronaModule\Zebra_Image;

class Genzo_Krona extends Module
{
    public $errors;

	function __construct() {
		$this->name = 'genzo_krona';
		$this->tab = 'front_office_features';
		$this->version = '2.0.0';
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
            !$this->registerHook('displayRightColumnProduct') OR
            !$this->registerHook('displayShoppingCartFooter') OR
            !$this->registerHook('displayKronaCustomer') OR
            !$this->registerHook('displayKronaActionPoints') OR
            !$this->registerHook('actionExecuteKronaAction') OR
            !$this->registerHook('actionCustomerAccountAdd') OR
            !$this->registerHook('actionOrderStatusUpdate') OR
            !$this->registerHook('actionOrderEdited') OR
            !$this->registerHook('actionRegisterGenzoCrmEmail') OR
			!$this->registerHook('ModuleRoutes') OR
            !$this->registerInbuiltActions() OR
            !$this->registerExternalActions() OR
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
                $file_path = _PS_MODULE_DIR_ . 'genzo_krona/views/img/icon/' . $file_name; // We need absolute path

                move_uploaded_file($file_tmp, $file_path.$file_extension);

                $avatar_small = new Zebra_Image();
                $avatar_small->source_path = $file_path.$file_extension;
                $avatar_small->target_path = $file_path.'_small.png';
                $avatar_small->png_compression = 1;
                $avatar_small->resize(30, 30, ZEBRA_IMAGE_BOXED, -1);

                $avatar_middle = new Zebra_Image();
                $avatar_middle->source_path = $file_path.$file_extension;
                $avatar_middle->target_path = $file_path.'_middle.png';
                $avatar_middle->png_compression = 1;
                $avatar_middle->resize(80, 80, ZEBRA_IMAGE_BOXED, -1);

                $avatar_big = new Zebra_Image();
                $avatar_big->source_path = $file_path.$file_extension;
                $avatar_big->target_path = $file_path.'_big.png';
                $avatar_big->png_compression = 1;
                $avatar_big->resize(120, 120, ZEBRA_IMAGE_BOXED, -1);

                unlink($file_path.$file_extension);
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
        }
    }

    public function hookDisplayKronaCustomer($params) {

	    $id_customer = (int)$params['id_customer'];
        $player = new Player($id_customer);

	    if ($player->active) {

            $name = Configuration::get('krona_total_name', $this->context->language->id, $this->context->shop->id_shop_group, $this->context->shop->id_shop);

            $player = array(
                'pseudonym' => $player->display_name,
                'avatar' => $player->avatar_full,
                'total' => $player->total . ' ' . $name,
            );

            return $player;
        }
        else {
	        return false;
        }

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
        $this->context->controller->addCSS($this->_path.'/views/css/krona.css');
        $this->context->controller->addCSS($this->_path.'/views/css/krona_custom.css');

        // JS
        $this->context->controller->addJquery();
        $this->context->controller->addJS($this->_path.'/views/js/krona.js');

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

        // include_once(dirname(__FILE__).'/ReferralProgramModule.php');

        /*if (Configuration::get('PS_CIPHER_ALGORITHM'))
            $cipherTool = new Rijndael(_RIJNDAEL_KEY_, _RIJNDAEL_IV_);
        else
            $cipherTool = new Blowfish(_COOKIE_KEY_, _COOKIE_IV_);
        $explodeResult = explode('|', $cipherTool->decrypt(Tools::getValue('sponsor')));
        if ($explodeResult AND count($explodeResult) > 1 AND list($id_referralprogram, $email) = $explodeResult AND (int)($id_referralprogram) AND !empty($email) AND Validate::isEmail($email) AND $id_referralprogram == ReferralProgramModule::isEmailExists($email))
        {
            $referralprogram = new ReferralProgramModule($id_referralprogram);
            if (Validate::isLoadedObject($referralprogram))
            {
                 // hack for display referralprogram information in form
                $_POST['customer_firstname'] = $referralprogram->firstname;
                $_POST['firstname'] = $referralprogram->firstname;
                $_POST['customer_lastname'] = $referralprogram->lastname;
                $_POST['lastname'] = $referralprogram->lastname;
                $_POST['email'] = $referralprogram->email;
                $_POST['email_create'] = $referralprogram->email;
                $sponsor = new Customer((int)$referralprogram->id_sponsor);
                $_POST['referralprogram'] = $sponsor->email;
            }
        }*/
        return $this->display(__FILE__, 'views/templates/hook/createAccountForm.tpl');
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

            Media::addJsDef(array(
                'krona_coins_change' => $actionOrder->coins_change,
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

	    if (Tools::isSubmit('convertLoyalty') && $loyalty_points = Tools::getValue('loyalty')) {
	        $this->convertLoyaltyPointsToCoupon($this->context->customer->id, $loyalty_points);
        }

        $id_shop_group = $this->context->shop->id_shop_group;
        $id_shop = $this->context->shop->id_shop;

        if (Configuration::get('krona_loyalty_cart_page', null, $id_shop_group, $id_shop) AND Configuration::get('krona_loyalty_active', null, $id_shop_group, $id_shop)) {

            $id_currency = $this->context->currency->id;
            $id_ActionOrder = ActionOrder::getIdActionOrderByCurrency($id_currency);
            $actionOrder = new ActionOrder($id_ActionOrder);

            $order_amount = Configuration::get('krona_order_amount', null, $id_shop_group, $id_shop);

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
            if (Configuration::get('krona_order_coupon', null, $id_shop_group, $id_shop) && ($order_amount == 'total_products_wt' OR $order_amount == 'total_products')) {
                $cart_value = $cart_value - $this->context->cart->getSummaryDetails()['total_discounts'];
            }

            if ($actionOrder->minimum_amount > $cart_value) {
                $cart_value = 0;
                $minimum = true;
            }
            else {
                $minimum = false;
            }

            // Check the rounding method -> nearest is standard
            $order_rounding = Configuration::get('krona_order_rounding', null, $id_shop_group, $id_shop);
            if ($order_rounding == 'down') {
                $total = floor($cart_value * $actionOrder->coins_change);
            }
            elseif ($order_rounding == 'up') {
                $total = ceil($cart_value * $actionOrder->coins_change);
            }
            else {
                $total = round($cart_value * $actionOrder->coins_change);
            }

            // Loyalty conversion
            $this->context->controller->addJS($this->_path.'/views/js/krona-loyalty.js');

            $player = ($this->context->customer->id) ? new Player($this->context->customer->id) : false;

            Media::addJsDef(
                array(
                    'conversion' => $actionOrder->coins_conversion,
                    'loyalty_max' => min($player->loyalty, $cart_value/$actionOrder->coins_conversion),
                )
            );

            if ($player) {
                $player = json_decode(json_encode($player), true);
            }

            $this->context->smarty->assign(array(
                'game_name' => Configuration::get('krona_game_name', $this->context->language->id, $id_shop_group, $id_shop),
                'loyalty_name' => Configuration::get('krona_loyalty_name', $this->context->language->id, $id_shop_group, $id_shop),
                'krona_coins_in_cart' => $total,
                'minimum' => $minimum,
                'minimum_amount' => $actionOrder->minimum_amount.' '.$actionOrder->currency_iso,
                'conversion' => number_format(round($total * $actionOrder->coins_conversion, 2),2).' '.$actionOrder->currency_iso,
                'player' => $player,
            ));

            return $this->display(__FILE__, 'views/templates/hook/shoppingCartFooter.tpl');
        }
        return null;
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

                if (isset($params['action_url']) && !empty($params['action_url'])) {
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

	    $referred_by = $params['_POST']['referral_code'];

	    if ($referred_by) {
	        // Todo: finish this
        }

        $customer = $params['newCustomer'];

        $player = new Player();
        $player->id_customer = $customer->id;
        $player->avatar = 'no-avatar.jpg';
        $player->active = (int)\Configuration::get('krona_customer_active', null, $customer->id_shop_group, $customer->id_shop);
        $player->referral_code = Player::generateReferralCode();
        $player->add();
    }

	public function hookActionOrderStatusUpdate($params) {

	    $newStatus = $params['newOrderStatus'];
	    $id_order = $params['id_order'];
        $order = new Order($id_order);

        return $this->processOrder($order, $newStatus->id);
    }

	public function hookActionOrderEdited($params) {
	    $order = $params['order'];
        return $this->processOrder($order, $order->current_state);
    }

    /* @param $order Order */
    public function processOrder($order, $id_order_state) {

        // Check if there is already history entry
        $id_history = PlayerHistory::getIdHistoryByIdOrder($order->id);

        $ids_order_state = explode(',', Configuration::get('krona_order_state', null, $order->id_shop_group, $order->id_shop));

        // Check if the status is relevant
        if (in_array($id_order_state, $ids_order_state) || $id_history) {

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

                // Check the rounding method -> nearest is standard
                $order_rounding = Configuration::get('krona_order_rounding', null, $order->id_shop_group, $order->id_shop);

                if ($order_rounding == 'down') {
                    $coins_change = floor($total * $actionOrder->coins_change);
                }
                elseif ($order_rounding == 'up') {
                    $coins_change = ceil($total * $actionOrder->coins_change);
                }
                else {
                    $coins_change = round($total * $actionOrder->coins_change);
                }

                $history = new PlayerHistory($id_history);
                $ids_lang = Language::getIDs();

                if (in_array($id_order_state, $ids_order_state)) {

                    $history->id_customer = $order->id_customer;
                    $history->id_action_order = $id_action_order;
                    $history->id_order = $order->id;
                    $history->url = $this->context->link->getPageLink('history');
                    $history->coins = $coins_change;

                    $expire_method = Configuration::get('krona_loyalty_expire_date', null, $order->id_shop_group, $order->id_shop);

                    if ($expire_method!='none') {
                        $expire_days = Configuration::get('krona_loyalty_expire_days', null, $order->id_shop_group, $order->id_shop);
                        $expire_date = date("Y-m-d H:i:s", strtotime("+{$expire_days} days"));
                        $history->loyalty_expire_date = $expire_date;

                        // Updating other old expiring dates of customer, if refreshing method
                        if ($expire_method == 'refreshing') {
                            DB::getInstance()->update('genzo_krona_player_history', ['loyalty_expire_date' => $expire_date], 'loyalty-loyalty_used-loyalty_expired >0 AND id_customer='.$order->id_customer);
                        }
                    }

                    // Handling lang fields for Player History

                    $title = array();
                    $message = array();

                    foreach ($ids_lang as $id_lang) {

                        $title[$id_lang] = Configuration::get('krona_order_title', $id_lang, $order->id_shop_group, $order->id_shop);
                        $message[$id_lang] = Configuration::get('krona_order_message', $id_lang, $order->id_shop_group, $order->id_shop);

                        // Replace message variables
                        $search = array('{coins}', '{reference}', '{amount}');

                        $total_currency = Tools::displayPrice(Tools::convertPrice($total, $order->id_currency));

                        $replace = array($coins_change, $order->reference, $total_currency);
                        $message[$id_lang] = str_replace($search, $replace, $message[$id_lang]);

                        $history->message[$id_lang] = pSQL($message[$id_lang]);
                        $history->title[$id_lang] = pSQL($title[$id_lang]);
                    }

                    $history->save();

                    Player::updatePlayerLevels($order->id_customer);
                }
                else {
                    // When an order is cancelled or get's a status that doesn't deserve points
                    $this->convertLoyaltyPointsToCoupon($order->id_customer, $history->loyalty, true);
                    $history->loyalty = 0;
                    $history->coins = 0;

                    foreach ($ids_lang as $id_lang) {
                        $history->comment[$id_lang] = Configuration::get('krona_order_canceled_message', $id_lang, $order->id_shop_group, $order->id_shop);
                    }

                    $history->update();

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

}