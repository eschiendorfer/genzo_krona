<?php

/**
 * Copyright (C) 2025 Emanuel Schiendorfer
 *
 * @author    Emanuel Schiendorfer <https://github.com/eschiendorfer>
 * @copyright 2025 Emanuel Schiendorfer
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../init.php');
require_once _PS_MODULE_DIR_ . 'genzo_krona/autoload.php';

use KronaModule\Action;
use KronaModule\Player;

// Page Visit
if (Tools::isSubmit('page_visit')) {
    $id_customer = (int)(Context::getContext()->customer->id ?? 0);
    if ($id_customer > 0) {
        Action::triggerPageVisit($id_customer);
    }
    die('true');
}

if (Tools::isSubmit('loadCommunityMembers')) {

    $query = new \DbQuery();
    $query->select('id_customer, pseudonym, avatar');
    $query->from(Player::$definition['table']);
    $query->where("active=1 AND banned=0 AND pseudonym!=''");
    $query->orderBy("pseudonym ASC");
    $players = \Db::getInstance()->ExecuteS($query);

    die(json_encode($players));
}
