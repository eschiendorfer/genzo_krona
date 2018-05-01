<?php

/**
 * Copyright (C) 2018 Emanuel Schiendorfer
 *
 * @author    Emanuel Schiendorfer <https://github.com/eschiendorfer>
 * @copyright 2018 Emanuel Schiendorfer
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

require_once _PS_MODULE_DIR_ . 'genzo_krona/genzo_krona.php';
require_once _PS_MODULE_DIR_ . 'genzo_krona/classes/Level.php';
require_once _PS_MODULE_DIR_ . 'genzo_krona/classes/Action.php';
require_once _PS_MODULE_DIR_ . 'genzo_krona/classes/ActionOrder.php';
require_once _PS_MODULE_DIR_ . 'genzo_krona/classes/Coupon.php';

use KronaModule\Level;
use KronaModule\Action;
use KronaModule\ActionOrder;
use KronaModule\Coupon;


class AdminGenzoKronaLevelsController extends ModuleAdminController
{

    /**
     * @var Level object
     */
    protected $object;

    public $total_name;

    public function __construct() {

        $this->module = 'genzo_krona';
        $this->bootstrap = true;
        $this->context = Context::getContext();
        $this->className = 'KronaModule\Level';
        $this->table = 'genzo_krona_level';
        $this->identifier = 'id_level';
        $this->lang = true;

        Shop::addTableAssociation($this->table, array('type' => 'shop'));

        $fields_list = array(
            'id_level' => array(
                'title' => 'ID',
                'alias' => 'l',
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'filter_type' => 'int',
            ),
            'name' => array(
                'title' => $this->l('Name'),
                'align' => 'left',
                'filter_type' => 'string',
            ),
            'condition_type' => array(
                'title' => $this->l('Type'),
                'align' => 'left',
                'type' => 'select',
                'list' => array(
                    'total' => $this->l('Threshold Total'),
                    'points' => $this->l('Threshold Points'),
                    'coins' => $this->l('Threshold Coins'),
                    'action' => $this->l('Executing Action'),
                    'order' => $this->l('Executing Order'),
                ),
                'filter_key' => 'a!condition_type',
                'filter_type' => 'string',
            ),
            'condition' => array(
                'title' => $this->l('Condition'),
                'align' => 'left',
                'filter_type' => 'int',
            ),
            'condition_time' => array(
                'title' => $this->l('Days to achieve'),
                'align' => 'left',
                'filter_type' => 'int',
            ),
            'achieve_max' => array(
                'title' => $this->l('Achieve max'),
                'align' => 'left',
                'filter_type' => 'int',
            ),
            'duration' => array(
                'title' => $this->l('Days of reward'),
                'align' => 'left',
                'filter_type' => 'int',
            ),
            'reward_type' => array(
                'title' => $this->l('Reward Type'),
                'align' => 'left',
                'filter_type' => 'string',
                'type' => 'select',
                'list' => array(
                    'symbolic' => $this->l('Symbolic'),
                    'coupon' => $this->l('Coupon'),
                    'group' => $this->l('group'),
                ),
                'filter_key' => 'a!reward_type',
            ),
            'active' => array(
                'title' => $this->l('Active'),
                'class' => 'fixed-width-xs',
                'active' => 'status',
                'align' => 'center',
                'type'  => 'bool',
                'filter_type' => 'bool',
            ),
            'position' => array(
                'title' => $this->l('Position'),
                'position' => 'position',
                'align' => 'center',
                'class' => 'fixed-width-md',
                'search' => false
            ),
        );

        $this->fields_list = $fields_list;
        $this->actions = array('edit', 'delete');
        $this->position_identifier = 'position';
        $this->_defaultOrderBy = 'position';
        $this->bulk_actions = [];

        parent::__construct();

    }

    public function init() {

        parent::init();

        // Configuration
        $id_lang = $this->context->language->id;
        $id_shop_group = $this->context->shop->id_shop_group;
        $id_shop = $this->context->shop->id;

        $this->total_name = Configuration::get('krona_total_name', $id_lang, $id_shop_group, $id_shop);
    }

    public function initContent() {

        // Some Basic Display functions
        $this->initTabModuleList();
        $this->initToolbar();
        $this->initPageHeaderToolbar();

        if ($this->display == 'edit' || $this->display == 'add') {
            if (!$this->loadObject(true)) {
                return;
            }
            $this->content = $this->renderForm();
        }
        else {
            $this->content = $this->renderList();
        }

        $this->context->smarty->assign(
            array(
                'content'   => $this->content,
                'tab'       => 'Levels',
                'show_page_header_toolbar'  => $this->show_page_header_toolbar,
                'page_header_toolbar_title' => $this->page_header_toolbar_title,
                'page_header_toolbar_btn'   => $this->page_header_toolbar_btn,
            )
        );

        $content = $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'genzo_krona/views/templates/admin/main.tpl');

        $this->context->smarty->assign(array(
            'content' => $content,
        ));

    }

    public function renderForm() {

        $id_level = (int)Tools::getValue('id_level');
        $id_lang = $this->context->language->id;

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
            'required' => true,
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
                    array('value' => 'action', 'name' => $this->l('Executing Action')),
                    array('value' => 'order', 'name' => $this->l('Executing Order')),
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
        $inputs[] =array(
            'type' => 'select',
            'label' => $this->l('Order'),
            'name' => 'id_action_order',
            'class' => 'chosen',
            'options' => array(
                'query' => ActionOrder::getAllActionOrder(array('o.active=1')),
                'id' => 'id_action_order',
                'name' => 'name',
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
        $inputs[] = array(
            'type' => 'switch',
            'label' => $this->l('Hide inactive'),
            'name' => 'hide',
            'desc' => $this->l('Should this level be hidden in customer account once it\'s inactive?'),
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
                'html_content' => "<img src='/modules/genzo_krona/views/img/icon/{$this->object->icon}_middle.png' width='50' height='50' />",
            );
        }
        $inputs[] = array(
            'type'  => 'file',
            'label' => 'Icon',
            'name'  => 'icon',
        );
        if (Shop::isFeatureActive()) {
            $inputs[] = array(
                'type' => 'shop',
                'label' => $this->l('Shop association:'),
                'name' => 'checkBoxShopAsso',
            );
        }

        $fields_form = array(
            'legend' => array(
                'title' => $this->l('Edit Level') ,
                'icon' => 'icon-cogs'
            ),
            'input' => $inputs,
            'submit' => array(
                'title' => $this->l('Save Level'),
                'class' => 'btn btn-default pull-right',
            )
        );

        $this->submit_action = 'saveLevel';

        // Fix of values since we dont use always same names
        $this->fields_form = $fields_form;
        $this->fields_value = array(
            'condition_points' => $this->object->condition,
            'condition_action' => $this->object->condition,
            'id_reward_coupon' => $this->object->id_reward,
            'id_reward_group' => $this->object->id_reward,
            'id_action_order' => $this->object->id_action,
        );

        if (!$this->object->id) {
            $this->fields_value['active'] = 1; // Active -> yes -> when adding new level
        }

        $this->tpl_form_vars = array(
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        $this->default_form_language = $id_lang;

        return parent::renderForm();


    }

    public function postProcess() {

        if (Tools::isSubmit('saveLevel')) {
            $id_level = (int)Tools::getValue('id_level');

            ($id_level > 0) ? $level = new Level($id_level) : $level = new Level();

            // Lang Fields
            $ids_lang = Language::getIDs();
            foreach ($ids_lang as $id_lang) {

                $name = pSQL(Tools::getValue('name_'.$id_lang));

                if ($name) {
                    $level->name[$id_lang] = $name;
                }
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
            elseif ($level->condition_type == 'order') {
                $level->condition = (int)Tools::getValue('condition_action');
                $level->id_action = (int)Tools::getValue('id_action_order');
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
                $krona = new Genzo_Krona();
                $icon = $krona->uploadIcon();
                $level->icon = ($icon) ? $icon : 'no-icon';

                if (isset($icon_old) && $icon_old != 'no-icon' && $icon_old != $level->icon) {
                    unlink(_PS_MODULE_DIR_ . 'genzo_krona/views/img/icon/' . $icon_old.'_small.png');
                    unlink(_PS_MODULE_DIR_ . 'genzo_krona/views/img/icon/' . $icon_old.'_middle.png');
                    unlink(_PS_MODULE_DIR_ . 'genzo_krona/views/img/icon/' . $icon_old.'_big.png');
                }
            }

            if (!$level->icon) {
                $level->icon = 'no-icon'; // For new levels without an upload
            }

            $level->hide = ($level->duration) ? (bool)Tools::getValue('hide') : 0;

            if (!$level->id) {
                $level->position = Level::getHighestPosition()+1;
            }

            if (!$level->name) {
                $this->errors[] = $this->l('Please fill in a name!');
            }

            if (empty($this->errors)) {
                $level->save();
                $this->confirmations[] = $this->l('Level was successfully saved!');
            }
            else {
                $this->display = 'edit';
            }

            // Shop Fields
            if (Shop::isFeatureActive()) {
                $ids_shop = Tools::getValue('checkBoxShopAsso_genzo_krona_level');
            }
            else {
                $ids_shop[] = $this->context->shop->id;
            }

            if (!($id_level > 0)) {
                $id_level = DB::getInstance()->Insert_ID();
            }

            DB::getInstance()->delete($this->table . '_shop', "id_level={$id_level}");

            foreach ($ids_shop as $id_shop) {
                $insert['id_level'] = (int)$id_level;
                $insert['id_shop'] = (int)$id_shop;
                DB::getInstance()->insert($this->table . '_shop', $insert);
            }
        }

        return parent::postProcess();
    }

    public function ajaxProcessUpdatePositions() {

        $way = (int) (Tools::getValue('way'));
        $id_level = (int) (Tools::getValue('id'));
        $positions = Tools::getValue('level');

        foreach ($positions as $position => $value) {
            $pos = explode('_', $value);

            if (isset($pos[2]) && (int) $pos[2] === $id_level) {
                if ($level = new Level((int) $pos[2])) {
                    if (isset($position) && $level->updatePosition($way, $position)) {
                        echo 'ok position';
                    } else {
                        echo '{"hasError" : true, "errors" : "Can not update position"}';
                    }
                } else {
                    echo '{"hasError" : true, "errors" : "This Level cant be loaded"}';
                }

                break;
            }
        }

        Level::cleanPositions();
    }

    public function setMedia() {

        parent::setMedia();

        $this->addJquery();
        $this->addJqueryPlugin('sortable');

        $this->addJS(array(
            _MODULE_DIR_.'genzo_krona/views/js/admin-krona.js',
        ));

        $this->addCSS(array(
            _MODULE_DIR_.'genzo_krona/views/css/admin-krona.css',
        ));

    }

}
