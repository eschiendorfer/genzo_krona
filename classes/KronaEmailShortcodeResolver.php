<?php

namespace KronaModule;

class KronaEmailShortcodeResolver
{
    public static function resolveLevel(array $context): string
    {
        return self::resolveShortcodeValue($context, 'level');
    }

    public static function resolveNextLevel(array $context): string
    {
        return self::resolveShortcodeValue($context, 'next_level');
    }

    public static function resolveReward(array $context): string
    {
        return self::resolveShortcodeValue($context, 'reward');
    }

    private static function resolveShortcodeValue(array $context, string $shortcode): string
    {
        $params = $context['params'] ?? [];
        $shortcodes = $params['shortcodes'] ?? [];

        if (is_array($shortcodes) && array_key_exists($shortcode, $shortcodes)) {
            $value = $shortcodes[$shortcode];
            if (is_scalar($value) || $value === null) {
                return (string)$value;
            }
        }

        $demoShortcodes = self::getDemoShortcodes();
        return (string)($demoShortcodes[$shortcode] ?? '');
    }

    private static function getDemoShortcodes(): array
    {
        static $demoShortcodes = null;
        if (is_array($demoShortcodes)) {
            return $demoShortcodes;
        }

        $context = \Context::getContext();
        $idLang = (int)($context->language->id ?: \Configuration::get('PS_LANG_DEFAULT'));

        $query = new \DbQuery();
        $query->select('l.id_level, l.position, ll.name');
        $query->from('genzo_krona_level', 'l');
        $query->innerJoin('genzo_krona_level_lang', 'll', 'll.id_level = l.id_level AND ll.id_lang = ' . $idLang);
        $query->where('l.active = 1');
        $query->orderBy('l.position ASC');
        $levels = (array)\Db::getInstance()->executeS($query);

        $levelName = 'Level';
        $nextLevelName = 'Nächstes Level';

        if (!empty($levels)) {
            $index = array_rand($levels);
            $current = $levels[$index];
            $next = $levels[$index + 1] ?? null;

            $levelName = trim((string)($current['name'] ?? '')) ?: $levelName;

            if (is_array($next)) {
                $nextLevelName = trim((string)($next['name'] ?? '')) ?: $nextLevelName;
            } else {
                $nextLevelName = '';
            }
        }

        $couponCode = 'KR-' . strtoupper(substr(md5((string)mt_rand()), 0, 8));
        $reward = 'Als Dankeschön haben Sie einen 10%-Gutschein erhalten! Einlösbar in den nächsten drei Monaten: ' . $couponCode;

        $demoShortcodes = [
            'level' => $levelName,
            'next_level' => $nextLevelName,
            'reward' => $reward,
        ];

        return $demoShortcodes;
    }
}
