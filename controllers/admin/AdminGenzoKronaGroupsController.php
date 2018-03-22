<?php

/**
 * Copyright (C) 2018 Emanuel Schiendorfer
 *
 * @author    Emanuel Schiendorfer <https://github.com/eschiendorfer>
 * @copyright 2018 Emanuel Schiendorfer
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

require_once _PS_MODULE_DIR_ . 'genzo_krona/classes/Group.php';

use KronaModule\Group;

class AdminGenzoKronaGroupsController extends ModuleAdminController
{

    /**
     * @var SettingsGroup object
     */
    protected $object;

    public function __construct() {

        $this->module = 'genzo_krona';
        $this->bootstrap = true;
        $this->context = Context::getContext();
        $this->className = 'KronaModule\Group';
        $this->table = 'genzo_krona_settings_group';
        $this->identifier = 'id_group';
        $this->lang = false;

        parent::__construct();

    }

    public function renderList() {

        $this->checkGroups();

        $this->_join.= 'INNER JOIN '._DB_PREFIX_.'group_lang gl ON (gl.id_group = a.id_group) ';

        $this->_join.= 'LEFT JOIN '._DB_PREFIX_.'genzo_krona_level k ON (k.`id_reward` = a.`id_group` AND k.`reward_type`=\'group\')';
        $this->_join.= 'LEFT JOIN '._DB_PREFIX_.'genzo_krona_level_lang kl ON (kl.`id_level` = k.`id_level` AND kl.`id_lang` = '.$this->context->language->id.')';

        $this->_select = 'gl.`name`, GROUP_CONCAT(kl.`name` SEPARATOR \', \') AS levels';
        $this->_group = 'GROUP BY a.`id_group` ';

        $this->_filter = ' AND gl.`id_lang` = '.$this->context->language->id;

        $fields_list = array(
            'id_group' => array(
                'type' => 'text',
                'title' => 'ID',
                'alias' => 'l',
                'align' => 'center',
                'filter_type' => 'int',
                'class' => 'fixed-width-xs',
            ),
            'name' => array(
                'type' => 'text',
                'title' => $this->l('Name'),
                'alias' => 'l',
                'filter_type' => 'int',
            ),
            'levels' => array(
                'type' => 'text',
                'title' => $this->l('In Levels'),
                'align' => 'center',
            ),
            'position' => array(
                'title' => $this->l('Priority'),
                'position' => 'position',
                'align' => 'center',
                'class' => 'fixed-width-sm',
                'search' => false
            ),
        );
        $this->fields_list = $fields_list;
        $this->actions = array('edit');
        $this->position_identifier = 'position';
        $this->_orderBy = 'position';
        $this->_orderWay = 'ASC';
        $this->bulk_actions = [];
        $this->list_simple_header = true;

        return parent::renderList();
    }

    public function displayEditLink($token = null, $id = null) {

        // We have to do it with this override. If we go directly in renderList (currentIndex) the position ajax doesn't work.

       $this->context->link->getAdminLink('AdminGroups', false).'&updategroup';

        $tpl = $this->createTemplate('helpers/list/list_action_edit.tpl');

        $tpl->assign(array(
            'href' => $this->context->link->getAdminLink('AdminGroups', true).'&updategroup&id_group='.$id,
            'action' => $this->l('Edit'),
            'id' => $id
        ));

        return $tpl->fetch();
    }

    public function initContent() {

        // Some Basic Display functions
        $this->initTabModuleList();
        $this->initToolbar();
        $this->initPageHeaderToolbar();

        if (Shop::isFeatureActive() && (Shop::getContext() != Shop::CONTEXT_ALL)) {
            $this->informations[] = $this->l('Keep in mind that this setting will be saved always for all Shops.');
        }

        $this->content = $this->renderList();

        // This are the real smarty variables
        $this->context->smarty->assign(
            array(
                'content'   => $this->content,
                'tab'       => 'Groups',
                'show_page_header_toolbar'  => $this->show_page_header_toolbar,
                'page_header_toolbar_title' => $this->page_header_toolbar_title,
                'page_header_toolbar_btn'   => $this->page_header_toolbar_btn,
            )
        );

        $tpl = $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'genzo_krona/views/templates/admin/main.tpl');

        $this->context->smarty->assign(array(
            'content' => $tpl, // This seems to be anything inbuilt. It's just chance that we both use content as an assign variable
        ));
    }

    public function setMedia() {

        parent::setMedia();

        $this->addJS(array(
            _MODULE_DIR_.'genzo_krona/views/js/admin-krona.js',
        ));

        $this->addCSS(array(
            _MODULE_DIR_.'genzo_krona/views/css/admin-krona.css',
        ));

    }

    // Helpers
    private function checkGroups() {
        // This functions checks basically, if all groups have Priority
        $query = new DbQuery();
        $query->select('Count(*)');
        $query->from('group');
        $group =  Db::getInstance()->getValue($query);

        $query = new DbQuery();
        $query->select('Count(*)');
        $query->from($this->table);
        $priority =  Db::getInstance()->getValue($query);

        if ($group != $priority) {

            // This functions checks basically, if all currencies are in the action_order table
            $query = new DbQuery();
            $query->select('id_group');
            $query->from($this->table);
            $settings =  Db::getInstance()->executeS($query);

            $query = new DbQuery();
            $query->select('id_group');
            $query->from('group');
            $groups = Db::getInstance()->executeS($query);

            // Flaten the multidimensional arrays, so we can use array_dif
            $settings = array_map('current', $settings);
            $groups = array_map('current', $groups);

            $missing = array_diff($groups, $settings); // Which currencies are missing in the module
            $redundant = array_diff($settings, $groups); // Which currencies are redundant in the module

            if (!empty($missing)) {
                foreach ($missing as $id_group) {
                    $insert['id_group'] = $id_group;
                    $insert['position'] = Group::getHighestPosition()+1;
                    Db::getInstance()->insert($this->table, $insert);
                }
            }

            if (!empty($redundant)) {
                foreach ($redundant as $id_group) {
                    Db::getInstance()->delete($this->table, "`id_group`={$id_group}");
                }
            }

        }

        return true;
    }

    public function ajaxProcessUpdatePositions() {

        Configuration::updateValue('genzo_krona_updiii', 'was uppp');

        $way = (int) (Tools::getValue('way'));
        $id_group = (int) (Tools::getValue('id'));
        $positions = Tools::getValue('group');

        foreach ($positions as $position => $value) {
            $pos = explode('_', $value);

            if (isset($pos[2]) && (int) $pos[2] === $id_group) {
                if ($group = new Group((int) $pos[2])) {

                    Configuration::updateValue('genzo_krona_updiii', 'was uppp');

                    if (isset($position) && $group->updatePosition($way, $position)) {
                        Configuration::updateValue('genzo_krona_updiii', 'I am still here');
                        echo 'ok position';
                    } else {
                        echo '{"hasError" : true, "errors" : "Can not update position"}';
                    }
                } else {
                    echo '{"hasError" : true, "errors" : "This Level cant be loaded"}';
                }

                break;
            }
        }

        Group::cleanPositions();
    }
    
}
