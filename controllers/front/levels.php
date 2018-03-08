<?php

/**
 * Copyright (C) 2018 Emanuel Schiendorfer
 *
 * @author    Emanuel Schiendorfer <https://github.com/eschiendorfer>
 * @copyright 2018 Emanuel Schiendorfer
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

use KronaModule\Player;
use KronaModule\PlayerLevel;

class Genzo_KronaLevelsModuleFrontController extends ModuleFrontController
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

        $player_obj = new Player($id_customer);

        // Check if there needs to be a redirction
        if (!$this->context->customer->isLogged()) {
            $krona_url = $this->context->link->getModuleLink('genzo_krona', 'home');
            Tools::redirect($krona_url);
        }
        elseif ($player_obj->banned) {
            $krona_url = $this->context->link->getModuleLink('genzo_krona', 'home').'?banned=1';
            Tools::redirect($krona_url);
        }
        elseif (!Player::checkIfPlayerIsActive($id_customer)) {
            $settings_url = $this->context->link->getModuleLink('genzo_krona', 'customersettings');
            Tools::redirect($settings_url);
        }


        $game_name = Configuration::get('krona_game_name', $id_lang, $id_shop_group, $id_shop);

		$this->context->smarty->assign(array(
            'meta_title' => $game_name.': '. $this->module->l('Timeline'),
            'game_name' => $game_name,
            'total_name' => Configuration::get('krona_points_name', $id_lang, $id_shop_group, $id_shop),
            'loyalty_name' => Configuration::get('krona_loyalty_name', $id_lang, $id_shop_group, $id_shop),
            'confirmation' => $this->confirmation,
            'errors' => $this->errors,
            'active' => 'Levels',
            'levels' => PlayerLevel::getAllPlayerLevels($id_customer),
            'gamification' => Configuration::get('krona_gamification_active', null, $id_shop_group, $id_shop),
            'loyalty' => Configuration::get('krona_loyalty_active', null, $id_shop_group, $id_shop),
		));

		$this->setTemplate('levels.tpl');
	}


}