<?php

/**
 * Copyright (C) 2018 Emanuel Schiendorfer
 *
 * @author    Emanuel Schiendorfer <https://github.com/eschiendorfer>
 * @copyright 2018 Emanuel Schiendorfer
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

namespace KronaModule;

class Coupon {

    public static function getAllCoupons ($filters = null) {

        $id_lang = (int)\Context::getContext()->language->id;

        $query = new \DbQuery();
        $query->select('c.id_cart_rule, l.name, kl.name AS level');
        $query->from('cart_rule', 'c');
        $query->innerJoin('cart_rule_lang', 'l', 'l.`id_cart_rule` = c.`id_cart_rule`');
        $query->leftJoin('genzo_krona_level', 'k', 'k.`id_reward` = c.`id_cart_rule`');
        $query->leftJoin('genzo_krona_level_lang', 'kl', 'kl.`id_level` = k.`id_level`');
        $query->where("l.`id_lang` = {$id_lang}");
        $query->where("kl.`id_lang` = {$id_lang} OR kl.`id_lang` IS NULL");
        $query->where("l.`name` LIKE 'KronaTemplate%'");
        $query->where("k.`reward_type` ='coupon' OR k.`reward_type` IS NULL");

        if (!empty($filters)) {
            foreach ($filters as $filter) {
                $query->where($filter);
            }
        }

        $rules = \Db::getInstance()->ExecuteS($query);
        $coupons = array();

        foreach ($rules as $rule) {
            $key = $rule['id_cart_rule'];
            if (array_key_exists($key, $coupons)) {
                $coupons[$key]['level'] .= ', '.$rule['level'];
            }
            else {
                $coupons[$key]['id_cart_rule'] = $key;
                $coupons[$key]['name'] = self::getCouponName($rule['name']);
                $coupons[$key]['level'] = $rule['level'];
            }
        }

        return $coupons;

    }

    public static function getCouponName($cart_rule_name) {
        $name = str_replace('KronaTemplate', '', $cart_rule_name);
        return ltrim($name, ':');
    }

}