<?php

/**
 * Copyright (C) 2018 Emanuel Schiendorfer
 *
 * @author    Emanuel Schiendorfer <https://github.com/eschiendorfer>
 * @copyright 2018 Emanuel Schiendorfer
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

use KronaModule\Player;

class Genzo_KronaLeaderboardModuleFrontController extends ModuleFrontController
{

	public function initContent()
	{	
		// Disable left and right column
		$this->display_column_left = false;
		$this->display_column_right = false;

        parent::initContent();

        $id_lang = $this->context->language->id;
        $id_shop_group = $this->context->shop->id_shop_group;
        $id_shop = $this->context->shop->id_shop;

        $game_name = Configuration::get('krona_game_name', $id_lang, $id_shop_group, $id_shop);

        $filters = array(
            'p.`active` = 1',
            'p.`banned` = 0',
        );

        $order = array(
            'order_by' => 'points',
            'order_way' => 'DESC',
        );

		$this->context->smarty->assign(array(
            'meta_title' => $game_name.': Leaderboard',
            'game_name' => $game_name,
            'slack' => Configuration::get('krona_url', null, $id_shop_group, $id_shop),
            'players' => Player::getAllPlayers($filters, null, $order),
		));

		$this->setTemplate('leaderboard.tpl');
	}

}