<?php

namespace KronaModule\EntityData;

use CoreExtension\EntityDataProfileEnum;
use CoreExtension\EntityDataProviderInterface;
use CoreExtension\EntityReference;
use CoreExtension\EntityTypeEnum;
use CoreExtension\OutputChannelEnum;
use KronaModule\Player;

final class KronaCustomerProfileEntityDataProvider implements EntityDataProviderInterface
{
    private const DEFAULT_AVATAR = '/upload/genzo_krona/img/avatar/no-avatar.jpg';

    public static function getDataEntityTypes(): array
    {
        return [EntityTypeEnum::KRONA_CUSTOMER_PROFILE];
    }

    /**
     * @param EntityReference[] $references
     * @return array<string, array<string, mixed>>
     */
    public static function getDataRows(
        array $references,
        OutputChannelEnum $channel,
        EntityDataProfileEnum $profile,
        array $context = []
    ): array {
        $idsCustomer = [];
        foreach ($references as $reference) {
            if (
                $reference instanceof EntityReference
                && $reference->isValid()
                && $reference->getEntityType() === EntityTypeEnum::KRONA_CUSTOMER_PROFILE
            ) {
                $idsCustomer[] = $reference->getIdEntity();
            }
        }

        $playerRows = self::loadPlayerRows($idsCustomer);
        if (empty($playerRows)) {
            return [];
        }

        $rows = [];
        foreach ($references as $reference) {
            if (
                !$reference instanceof EntityReference
                || !$reference->isValid()
                || $reference->getEntityType() !== EntityTypeEnum::KRONA_CUSTOMER_PROFILE
            ) {
                continue;
            }

            $idCustomer = $reference->getIdEntity();
            $playerRow = $playerRows[$idCustomer] ?? null;
            if (!is_array($playerRow)) {
                continue;
            }

            $row = self::mapPlayerRow($playerRow, $channel);
            if (is_array($row) && trim((string)($row['title'] ?? '')) !== '') {
                $rows[$reference->getKey()] = $row;
            }
        }

        return $rows;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function loadPlayerRows(array $idsCustomer): array
    {
        $idsCustomer = array_values(array_unique(array_filter(array_map('intval', $idsCustomer))));
        if (empty($idsCustomer) || !class_exists('\Db') || !class_exists('\DbQuery')) {
            return [];
        }

        $query = new \DbQuery();
        $query->select('p.id_customer, p.pseudonym, p.avatar, p.referral_code, p.date_upd');
        $query->from('genzo_krona_player', 'p');
        $query->innerJoin('customer', 'c', 'c.id_customer = p.id_customer');
        $query->where('p.id_customer IN (' . implode(',', $idsCustomer) . ')');
        $query->where('p.active = 1');
        $query->where('p.banned = 0');

        try {
            $rawRows = (array)\Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
        } catch (\Throwable) {
            return [];
        }

        $rows = [];
        foreach ($rawRows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $idCustomer = (int)($row['id_customer'] ?? 0);
            if ($idCustomer > 0) {
                $rows[$idCustomer] = $row;
            }
        }

        return $rows;
    }

    private static function mapPlayerRow(array $playerRow, OutputChannelEnum $channel): ?array
    {
        $idCustomer = (int)($playerRow['id_customer'] ?? 0);
        if ($idCustomer <= 0) {
            return null;
        }

        $title = self::resolveDisplayName($playerRow);
        if ($title === '') {
            return null;
        }

        $avatar = self::resolveAvatarUrl($playerRow);
        $url = self::resolveProfileUrl((string)($playerRow['referral_code'] ?? ''));

        if ($channel === OutputChannelEnum::EMAIL) {
            $avatar = self::toAbsoluteUrl($avatar);
            $url = self::toAbsoluteUrl($url);
        }

        return [
            'entity_type' => EntityTypeEnum::KRONA_CUSTOMER_PROFILE->value,
            'entity_type_key' => EntityTypeEnum::KRONA_CUSTOMER_PROFILE->getEntityTypeKey(),
            'id_entity' => $idCustomer,
            'title' => $title,
            'subtitle' => '',
            'img' => $avatar,
            'image_url' => $avatar,
            'avatar' => $avatar,
            'url' => $url,
            'link' => ['url' => $url],
            'active' => true,
        ];
    }

    private static function resolveDisplayName(array $playerRow): string
    {
        $pseudonym = trim((string)($playerRow['pseudonym'] ?? ''));
        if ((bool)\Configuration::get('krona_pseudonym') && $pseudonym !== '') {
            return $pseudonym;
        }

        $names = Player::getDisplayNames((int)($playerRow['id_customer'] ?? 0));

        return trim((string)($names['display_name'] ?? ''));
    }

    private static function resolveAvatarUrl(array $playerRow): string
    {
        $avatar = basename(trim((string)($playerRow['avatar'] ?? '')));
        if ($avatar === '' || $avatar === 'no-avatar.jpg') {
            return self::DEFAULT_AVATAR;
        }

        $filePath = _PS_UPLOAD_DIR_ . 'genzo_krona/img/avatar/' . $avatar;
        if (!file_exists($filePath)) {
            return self::DEFAULT_AVATAR;
        }

        $timestamp = strtotime((string)($playerRow['date_upd'] ?? ''));
        $version = $timestamp !== false ? '?=' . $timestamp : '';

        return '/upload/genzo_krona/img/avatar/' . $avatar . $version;
    }

    private static function resolveProfileUrl(string $referralCode): string
    {
        $referralCode = trim($referralCode);
        if ($referralCode === '') {
            return '';
        }

        try {
            $link = \Context::getContext()->link;
            if (!$link instanceof \Link) {
                return '';
            }

            return (string)$link->getModuleLink('genzo_krona', 'overview', [
                'referral_code' => $referralCode,
            ]);
        } catch (\Throwable) {
            return '';
        }
    }

    private static function toAbsoluteUrl(string $url): string
    {
        return class_exists('\ImageHelper') ? \ImageHelper::convertToAbsoluteUrl($url) : $url;
    }
}
