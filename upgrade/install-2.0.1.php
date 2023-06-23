<?php

/**
 * @var genzo_krona $module
 * @return bool
 * @throws PrestaShopException
 */
function upgrade_module_2_0_1($module) {

    if (
        !$module->registerHook('displayCustomerIdentityForm') OR
        !$module->registerHook('actionObjectCustomerUpdateAfter')

    ) {
        return false;
    }

    return true;
}