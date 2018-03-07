<?php

/**
 * Copyright (C) 2018 Emanuel Schiendorfer
 *
 * @author    Emanuel Schiendorfer <https://github.com/eschiendorfer>
 * @copyright 2018 Emanuel Schiendorfer
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

use KronaModule\Player;

class Genzo_KronaCustomerSettingsModuleFrontController extends ModuleFrontController
{
    public $errors;

	public function initContent()
	{	
		// Disable left and right column
		$this->display_column_left = false;
		$this->display_column_right = true;

        parent::initContent();

        $id_lang = $this->context->language->id;
        $id_shop_group = $this->context->shop->id_shop_group;
        $id_shop = $this->context->shop->id_shop;
        $id_customer = $this->context->customer->id;

        // Check if there needs to be a redirction
        if (!$this->context->customer->isLogged()) {
            $krona_url = $this->context->link->getModuleLink('genzo_krona', 'home');
            Tools::redirect($krona_url);
        }

        // Customer Saves Settings Form
        if (Tools::isSubmit('saveCustomerSettings')) {
            $this->saveCustomerSettings($id_customer);
        }

        if (!Player::checkIfPlayerIsActive($id_customer)) {
            $this->errors[] = $this->module->l('Please activate your account.');
        }

        $game_name = Configuration::get('krona_game_name', $id_lang, $id_shop_group, $id_shop);

        $player_obj = new Player($id_customer);
        $player = json_decode(json_encode($player_obj), true); // Turns an object into an array

		$this->context->smarty->assign(array(
            'meta_title' => $game_name.': '. $this->module->l('Settings'),
            'game_name' => $game_name,
            'confirmation' => $this->confirmation,
            'errors' => $this->errors,
            'active' => 'Settings',
            'player' => $player,
            'avatar_img' => Player::getAvatar($id_customer),
            'pseudonym' => Configuration::get('krona_pseudonym', null, $id_shop_group, $id_shop),
            'avatar' => Configuration::get('krona_avatar', null, $id_shop_group, $id_shop),
            'gamification' => Configuration::get('krona_gamification_active', null, $id_shop_group, $id_shop),
		));

		$this->setTemplate('customersettings.tpl');
	}

	private function saveCustomerSettings($id_customer) {
	    $id_customer = (int)$id_customer;
	    $active = (bool)Tools::getValue('active');
	    $pseudonym = pSQL(Tools::getValue('pseudonym'));

	    $player = new Player($id_customer);
	    $player->active = $active;
	    if (Configuration::get('krona_pseudonym', null, $this->context->shop->id_shop_group, $this->context->shop->id_shop)) {
	        $player->pseudonym = $pseudonym;
        }
        if ($_FILES['avatar']['tmp_name']) {
	        $genzo_krona = new Genzo_Krona();
            $player->avatar = ($genzo_krona->uploadAvatar($id_customer)) ? $id_customer.'.jpg' : $player->avatar;
        }
        if (empty($genzo_krona->errors)) {
	        $player->update();
	        $this->confirmation = $this->module->l('Your Customer Settings were sucessfully saved.');

            // Add History
            $hook = array(
                'module_name' => 'genzo_krona',
                'action_name' => 'avatar_upload',
                'id_customer' => $id_customer,
            );

            \Hook::exec('ActionExecuteKronaAction', $hook);
        }
        else {
	        $this->errors = $genzo_krona->errors;
        }
    }
}