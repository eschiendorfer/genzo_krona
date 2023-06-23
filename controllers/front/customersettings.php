<?php

/**
 * Copyright (C) 2019 Emanuel Schiendorfer
 *
 * @author    Emanuel Schiendorfer <https://github.com/eschiendorfer>
 * @copyright 2019 Emanuel Schiendorfer
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

use KronaModule\Player;

class Genzo_KronaCustomerSettingsModuleFrontController extends ModuleFrontController {

    public $errors;
    public $confirmations;

    public function __construct() {

        // Making sure, that controller is accessible in other files/hooks with require_once like genzo_krona.php
        if(!Tools::getValue('module')) {
            $_GET['module'] = 'genzo_krona';
        }

        parent::__construct();
    }

    public function postProcess() {

        parent::postProcess();

        // Customer Saves Settings Form - needs to be before the playerObj, to have refreshed values
        // Usage of saveCustomerSettings is deprecated, but needed for backward compatibility
        if (Tools::isSubmit('submitKronaCustomerSettings') || Tools::isSubmit('saveCustomerSettings')) {
            $this->saveCustomerSettings();
        }

    }

    public function initContent() {

		// Disable left and right column
		$this->display_column_left = false;
		$this->display_column_right = false;

        parent::initContent();

        // Check if there needs to be a redirection
        $id_customer = $this->context->customer->id;

        if (!$id_customer || !$this->context->customer->isLogged()) {
            Tools::redirect($this->context->link->getModuleLink('genzo_krona', 'home'));
        }

        $playerObj = new Player($id_customer);

        if ($playerObj->banned) {
            Tools::redirect($this->context->link->getModuleLink('genzo_krona', 'home').'?banned=1');
        }

        $game_name = Configuration::get('krona_game_name', $this->context->language->id);

		$this->context->smarty->assign(array(
            'meta_title' => $game_name.': '. $this->module->l('Settings'),
            'game_name' => $game_name,
            'loyalty_name' => Configuration::get('krona_loyalty_name', $this->context->language->id),
            'confirmation' => $this->confirmations,
            'errors' => $this->errors,
            'active' => 'Settings',
            'player' => json_decode(json_encode($playerObj), true),
            'pseudonym' => Configuration::get('krona_pseudonym'),
            'avatar' => Configuration::get('krona_avatar'),
            'gamification' => Configuration::get('krona_gamification_active'),
            'loyalty' => Configuration::get('krona_loyalty_active'),
            'color_scheme' => 'red',
		));

		$this->setTemplate('customersettings.tpl');
	}

	public function saveCustomerSettings() {

	    $id_customer = $this->context->customer->id;

	    $playerObj = new Player($id_customer);
	    $playerObj->active = (bool)Tools::getValue('krona_active', Tools::getValue('active'));

	    if (Configuration::get('krona_pseudonym')) {
	        $playerObj->pseudonym = pSQL(Tools::getValue('krona_pseudonym', Tools::getValue('pseudonym')));
        }

        if (isset($_FILES['krona_avatar']['tmp_name']) || $_FILES['avatar']['tmp_name']) {

            $playerObj->avatar = ($this->module->uploadAvatar($id_customer)) ? $id_customer.'.jpg' : $playerObj->avatar;

            $hook = array(
                'module_name' => 'genzo_krona',
                'action_name' => 'avatar_upload',
                'id_customer' => $id_customer,
            );

            \Hook::exec('ActionExecuteKronaAction', $hook);
        }

        if (!empty($this->module->errors)) {
            $this->errors = $this->module->errors;
            return false;
        }

        $this->confirmations = $this->module->l('Your Customer Settings were sucessfully saved.');
        return $playerObj->update();

    }

    public function setMedia() {
        parent::setMedia();
        $this->addJS(_MODULE_DIR_.'genzo_krona/views/js/krona.js');
    }

}