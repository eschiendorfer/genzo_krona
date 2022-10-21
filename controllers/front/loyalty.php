<?php

/**
 * Copyright (C) 2019 Emanuel Schiendorfer
 *
 * @author    Emanuel Schiendorfer <https://github.com/eschiendorfer>
 * @copyright 2019 Emanuel Schiendorfer
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

use KronaModule\Player;
use KronaModule\PlayerHistory;
use KronaModule\ActionOrder;

class Genzo_KronaLoyaltyModuleFrontController extends ModuleFrontController {

    public $errors;
    public $confirmation;

    /* @var $module Genzo_Krona */
    public $module;

	public function initContent() {

		// Disable left and right column
		$this->display_column_left = false;
		$this->display_column_right = false;

        parent::initContent();

        // Check if there needs to be a redirection
        if (!$this->context->customer->isLogged() || !$id_customer = $this->context->customer->id) {
            Tools::redirect($this->context->link->getModuleLink('genzo_krona', 'home'));
        }

        // Check if there is a Conversion - needs to be on top, to get right values after conversion
        if (Tools::isSubmit('convertLoyalty')) {
            $this->module->convertLoyaltyPointsToCoupon($id_customer, (int)Tools::getValue('loyalty'));
            $this->confirmation = $this->module->l('Your Coupon was sucessfully created.');
        }

        $playerObj = new Player($id_customer);

        if ($playerObj->banned) {
            Tools::redirect($this->context->link->getModuleLink('genzo_krona', 'home').'?banned=1');
        }
        elseif (!$playerObj->active) {
            Tools::redirect($this->context->link->getModuleLink('genzo_krona', 'customersettings'));
        }

        $game_name = Configuration::get('krona_game_name', $this->context->language->id);
        $loyalty_name = Configuration::get('krona_loyalty_name', $this->context->language->id);

        // Coupon Value Calculation
        $this->context->controller->addJS(_MODULE_DIR_.'genzo_krona/views/js/krona-loyalty.js');

        $id_action_order = ActionOrder::getIdActionOrderByCurrency($this->context->currency->id);
        $actionOrder = new ActionOrder($id_action_order);

        Media::addJsDef(
            array(
                'conversion' => $actionOrder->coins_conversion,
                'loyalty_max' => $playerObj->loyalty,
            )
        );

        $player = json_decode(json_encode($playerObj), true); // Turns an object into an array

		$this->context->smarty->assign(array(
            'meta_title' => $game_name.': '. $loyalty_name,
            'game_name' => $game_name,
            'loyalty_name' => $loyalty_name,
            'confirmation' => $this->confirmation,
            'errors' => $this->errors,
            'active' => 'Loyalty',
            'gamification' => Configuration::get('krona_gamification_active'),
            'loyalty' => Configuration::get('krona_loyalty_active'),
            'player' => $player,
            'krona_currency' => $actionOrder->currency_iso,
            'color_scheme' => 'red',
		));

		$this->setTemplate('loyalty.tpl');
	}

}