<?php

/**
 * Copyright (C) 2019 Emanuel Schiendorfer
 *
 * @author    Emanuel Schiendorfer <https://github.com/eschiendorfer>
 * @copyright 2019 Emanuel Schiendorfer
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

require_once _PS_MODULE_DIR_ . 'genzo_krona/autoload.php';

use KronaModule\Action;

class AdminGenzoKronaActionsController extends ModuleAdminController {

    /* @var $module Genzo_Krona */
    public $module;

    /* @var Action object */
    protected $object;

    public function __construct() {

        $this->module = 'genzo_krona';
        $this->bootstrap = true;
        $this->context = Context::getContext();
        $this->className = 'KronaModule\Action';
        $this->table = 'genzo_krona_action';
        $this->identifier = 'id_action';
        $this->lang = true;

        Shop::addTableAssociation($this->table, array('type' => 'shop'));

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
                'active' => 'status',
                'class' => 'fixed-width-xs',
                'align' => 'center',
                'type'  => 'bool',
                'filter_type' => 'int',
            )
        );

        $this->fields_list = $fields_list;
        $this->actions = array('edit');
        $this->_orderBy = 'id_action';
        $this->_orderWay = 'ASC';
        $this->bulk_actions = [];
        $this->allow_export = true;
        $this->token = Tools::getAdminTokenLite('AdminGenzoKronaActions');
        $this->tpl_list_vars['title'] = 'Krona Actions';


        parent::__construct();

    }

    public function init() {

        // Check Actions
        $this->module->registerExternalActions();

        parent::init();

    }

    public function initContent() {

        // Some Basic Display functions
        $this->initTabModuleList();
        $this->initToolbar();
        $this->initPageHeaderToolbar();

        if ($this->display == 'edit') {
            if (!$this->loadObject(true)) {
                return;
            }
            $this->content = $this->renderForm();
        }
        else {
            $this->content = $this->renderList();
        }

        // This are the real smarty variables
        $this->context->smarty->assign(
            array(
                'content'   => $this->content,
                'tab'       => 'Actions',
                'show_page_header_toolbar'  => $this->show_page_header_toolbar,
                'page_header_toolbar_title' => $this->page_header_toolbar_title,
                'page_header_toolbar_btn'   => $this->page_header_toolbar_btn,
            )
        );

        $tpl = $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'genzo_krona/views/templates/admin/main.tpl');

        $this->context->smarty->assign(array(
            'content' => $tpl, // This seems to be anything inbuilt. It's just chance that we both use content as an assign variable
        ));

    }

    public function initToolbar() {
        parent::initToolbar();
        unset( $this->toolbar_btn['new'] ); // To remove the add button
    }

    public function renderForm() {

        $id_action = Tools::getValue('id_action');

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
            'suffix' => $this->l('Points'),
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
                'title' => $this->l('Edit Action'),
                'icon' => 'icon-cogs'
            ),
            'input' => $inputs,
            'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right'
            )
        );

        // Fix of values since we dont use always same names
        $this->fields_form = $fields_form;

        $this->tpl_form_vars = array(
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return parent::renderForm();
    }

    public function setMedia() {

        parent::setMedia();

        $this->addJS(array(
            _MODULE_DIR_.'genzo_krona/views/js/admin-krona.js',
        ));

        $this->addCSS(array(
            _MODULE_DIR_.'genzo_krona/views/css/admin-krona.css',
        ));

    }

}
