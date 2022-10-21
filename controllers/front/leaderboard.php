<?php

/**
 * Copyright (C) 2019 Emanuel Schiendorfer
 *
 * @author    Emanuel Schiendorfer <https://github.com/eschiendorfer>
 * @copyright 2019 Emanuel Schiendorfer
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

use KronaModule\Player;

class Genzo_KronaLeaderboardModuleFrontController extends ModuleFrontController {

	public function initContent() {

		// Disable left and right column
		$this->display_column_left = false;
		$this->display_column_right = false;

        parent::initContent();

        $filters = array(
            'p.`active` = 1',
            'p.`banned` = 0',
        );

        // Hide Players in leaderboard
        $hide_players = Configuration::get('krona_hide_players');
        if ($hide_players) {
            $filters[] = 'p.`id_customer` NOT IN ('.$hide_players.')';
        }

        // Pagination
        $total_players = Player::getTotalPlayers($filters);
        $limit_players = (int)Configuration::get('krona_leaderboard');

        if (!$limit_players OR $total_players < $limit_players) {
            $limit_players = $total_players;
        }

        $per_page = (int)Configuration::get('krona_leaderboard_page');
        $items = ($per_page) ? $per_page : 50;
        $pages = ceil($limit_players/$items);
        $page = (Tools::getValue('page')) ? Tools::getValue('page') : 1;

        // Check if page is valid
        if ($page > $pages) {
            $this->errors[] = $this->module->l('This page is not available');
            $page = 1;
        }

        $player_pagination = array(
            'limit' => $items,
            'offset' => ($page-1) * $items,
        );

        $order = array(
            'order_by' => 'total` DESC, `id_customer',
            'order_way' => 'ASC',
        );

        $game_name = Configuration::get('krona_game_name', $this->context->language->id);

		$this->context->smarty->assign(array(
            'meta_title' => $game_name.': '.$this->module->l('Leaderboard'),
            'game_name' => $game_name,
            'total_name' => Configuration::get('krona_total_name', $this->context->language->id),
            'loyalty_name' => Configuration::get('krona_loyalty_name', $this->context->language->id),
            'active' => 'Players',
            'players' => Player::getAllPlayers($filters, $player_pagination, $order),
            'pages' => $pages,
            'page' => $page,
            'loyalty' => Configuration::get('krona_loyalty_active'),
            'title' => ($limit_players!=0) ? "Top {$limit_players}" : false,
            'color_scheme' => 'red',
		));

		$this->setTemplate('leaderboard.tpl');
	}

}