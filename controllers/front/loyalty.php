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

	public function initContent()
	{	
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
        elseif (!Player::checkIfPlayerIsActive($id_customer)) {
            $settings_url = $this->context->link->getModuleLink('genzo_krona', 'customersettings');
            Tools::redirect($settings_url);
        }

        $game_name = Configuration::get('krona_game_name', $id_lang, $id_shop_group, $id_shop);
        $loyalty_name = Configuration::get('krona_loyalty_name', $id_lang, $id_shop_group, $id_shop);


        // Coupon Value Calculation
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
    private function convertLoyalty($player, $actionOrder) {
	    $loyalty = (int)Tools::getValue('loyalty');

	    if ($loyalty > $player->loyalty) {
	        $this->errors[] = $this->module->l('You haven\'t enough loyalty points.');
	        return;
        }
        elseif (empty($loyalty)) {
	        $this->errors[] = $this->module->l('Must select more than 0 loyalty points.');
	        return;
        }
        else {
	        // Remove Loyalty Points
	        $player->loyalty = $player->loyalty - $loyalty;
	        $player->update();

	        // Add History
            $ids_lang = Language::getIDs();

            $history = new PlayerHistory();
            $history->id_customer = $player->id_customer;
            $history->id_action = 0;
            $history->id_action_order = $actionOrder->id_action_order;

            $points_name = array();

            foreach ($ids_lang as $id_lang) {
                $points_name[$id_lang] = Configuration::get('krona_loyalty_name', $id_lang, $this->context->shop->id_shop_group, $this->context->shop->id);
                $history->title[$id_lang] = $points_name[$id_lang]. ' '. $this->module->l('Conversion');
                $history->message[$id_lang] = sprintf($this->module->l('You converted %s into a coupon.'),$loyalty.' '.$points_name[$id_lang]);
            }
            $history->change = 0;
            $history->change_loyalty = -$loyalty;
            $history->add();


            $customer = new Customer();
            $customer->email;
            $customer->firstname;
            $customer->getGroups();

            // Add Coupon
            $id_cart_rule = CartRule::getIdByCode('KRONA');
            $coupon = new CartRule($id_cart_rule);

            // Clone the cart rule and override some values
            $coupon->id_customer = $player->id;
            $coupon->reduction_amount = ($loyalty * $actionOrder->coins_conversion);

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
                $coupon->name[$id_lang] = $game_name . ' - ' . $loyalty . ' ' . $points_name[$id_lang];
            }

            $prefix = Configuration::get('krona_coupon_prefix', null, $customer->id_shop_group, $customer->id_shop);
            $code = strtoupper(Tools::passwdGen(6));

            $coupon->code = ($prefix) ? $prefix.'-'.$code : $code;
            $coupon->active = true;
            $coupon->add();

            CartRule::copyConditions($id_cart_rule, $coupon->id);

            $this->confirmation = $this->module->l('Your Coupon was sucessfully created.');

        }
    }

}