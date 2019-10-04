<?php

/**
 * Copyright (C) 2018 Emanuel Schiendorfer
 *
 * @author    Emanuel Schiendorfer <https://github.com/eschiendorfer>
 * @copyright 2018 Emanuel Schiendorfer
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

use KronaModule\Player;
use KronaModule\PlayerHistory;
use KronaModule\ActionOrder;

class Genzo_KronaLoyaltyModuleFrontController extends ModuleFrontController
{
    public $errors;
    public $confirmation;

    /* @var $module Genzo_Krona */
    public $module;

	public function initContent() {

		// Disable left and right column
		$this->display_column_left = false;
		$this->display_column_right = false;

        parent::initContent();

        if (!$this->context->customer->isLogged()) {
            $krona_url = $this->context->link->getModuleLink('genzo_krona', 'home');
            Tools::redirect($krona_url);
        }

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
        elseif (!$player_obj->active) {
            $settings_url = $this->context->link->getModuleLink('genzo_krona', 'customersettings');
            Tools::redirect($settings_url);
        }

        $game_name = Configuration::get('krona_game_name', $id_lang, $id_shop_group, $id_shop);
        $loyalty_name = Configuration::get('krona_loyalty_name', $id_lang, $id_shop_group, $id_shop);


        // Coupon Value Calculation
        $this->context->controller->addJS(_MODULE_DIR_.'genzo_krona/views/js/krona-loyalty.js');

        $id_actionOrder = ActionOrder::getIdActionOrderByCurrency($this->context->currency->id);
        $actionOrder = new ActionOrder($id_actionOrder);

        Media::addJsDef(
            array(
                'conversion' => $actionOrder->coins_conversion,
                'loyalty_max' => $player_obj->loyalty,
            )
        );

        // Check if there is a Conversion
        if (Tools::isSubmit('convertLoyalty')) {
            $this->convertLoyalty($player_obj, $actionOrder);
            $player_obj = new Player($id_customer); // Just to refresh loyalty value
        }

        $player = json_decode(json_encode($player_obj), true); // Turns an object into an array

		$this->context->smarty->assign(array(
            'meta_title' => $game_name.': '. $loyalty_name,
            'game_name' => $game_name,
            'loyalty_name' => $loyalty_name,
            'confirmation' => $this->confirmation,
            'errors' => $this->errors,
            'active' => 'Loyalty',
            'gamification' => Configuration::get('krona_gamification_active', null, $id_shop_group, $id_shop),
            'loyalty' => Configuration::get('krona_loyalty_active', null, $id_shop_group, $id_shop),
            'player' => $player,
            'krona_currency' => $actionOrder->currency_iso,
		));

		$this->setTemplate('loyalty.tpl');
	}


    /**
     * @var Player $player
     * @var ActionOrder $actionOrder
     */
    private function convertLoyalty() {

        $loyalty_points = (int)Tools::getValue('loyalty');

        // Remove Loyalty Points
        $this->module->convertLoyaltyPointsToCoupon($this->context->customer->id, $loyalty_points);

        $this->confirmation = $this->module->l('Your Coupon was sucessfully created.');

    }

}