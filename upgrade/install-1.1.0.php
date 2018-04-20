<?php

/**
 * @var genzo_krona $module
 * @return bool
 * @throws PrestaShopException
 */
function upgrade_module_1_1_0($module) {

    if (!$module->executeSqlScript('install-1.1.0') OR
        !$module->registerHook('displayKronaActionPoints')) {
        return false;
    }
    else {
        return true;
    }

}