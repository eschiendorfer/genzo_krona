<?php

/**
 * @var genzo_krona $module
 * @return bool
 * @throws PrestaShopException
 */
function upgrade_module_1_2_0($module) {

    if (!$module->executeSqlScript('install-1.2.0') OR
        !$this->registerHook('actionRegisterGenzoCrmEmail')) {
        return false;
    }

    return true;
}