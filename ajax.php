<?php

/**
 * Copyright (C) 2018 Emanuel Schiendorfer
 *
 * @author    Emanuel Schiendorfer <https://github.com/eschiendorfer>
 * @copyright 2018 Emanuel Schiendorfer
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../init.php');

require_once _PS_MODULE_DIR_ . 'genzo_krona/autoload.php';

use KronaModule\Action;
use KronaModule\Player;

// Page Visit
if(Tools::getValue('page_visit')) {
    $id_customer = (int)Tools::getValue('page_visit');
    Action::triggerPageVisit($id_customer);
    echo true;
}

// Notification
if(Tools::getValue('notification')) {
    $id_customer = (int)Tools::getValue('notification');
    $context = Context::getContext();
    if ($id_customer == $context->customer->id) {
        $player = new Player($id_customer);
        echo json_encode($player->notification);
    }
}
