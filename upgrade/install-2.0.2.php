<?php

/**
 * @var genzo_krona $module
 * @return bool
 * @throws PrestaShopException
 */
function upgrade_module_2_0_2($module)
{
    if (
        !addKronaPlayerSnapshotColumns()
        || !rebuildKronaPlayerSnapshots()
    ) {
        return false;
    }

    return true;
}

function addKronaPlayerSnapshotColumns()
{
    $db = Db::getInstance();
    $table = _DB_PREFIX_.'genzo_krona_player';

    if (!kronaColumnExists($table, 'total')) {
        if (!$db->execute('ALTER TABLE `'.$table.'` ADD `total` INT(12) NOT NULL DEFAULT 0')) {
            return false;
        }
    }

    if (!kronaColumnExists($table, 'id_level')) {
        if (!$db->execute('ALTER TABLE `'.$table.'` ADD `id_level` INT(12) NOT NULL DEFAULT 0')) {
            return false;
        }
    }

    if (!kronaIndexExists($table, 'total')) {
        if (!$db->execute('ALTER TABLE `'.$table.'` ADD INDEX `total` (`total`)')) {
            return false;
        }
    }

    if (!kronaIndexExists($table, 'id_level')) {
        if (!$db->execute('ALTER TABLE `'.$table.'` ADD INDEX `id_level` (`id_level`)')) {
            return false;
        }
    }

    return true;
}

function rebuildKronaPlayerSnapshots()
{
    $db = Db::getInstance();
    $mode = (string)Configuration::get('krona_gamification_total');

    if (!in_array($mode, ['points_coins', 'points', 'coins'], true)) {
        $mode = 'points_coins';
    }

    $rows = $db->executeS('
        SELECT p.id_customer, COALESCE(SUM(h.points), 0) AS points, COALESCE(SUM(h.coins), 0) AS coins
        FROM '._DB_PREFIX_.'genzo_krona_player p
        LEFT JOIN '._DB_PREFIX_.'genzo_krona_player_history h ON h.id_customer = p.id_customer
        GROUP BY p.id_customer
    ');

    if ($rows === false) {
        return false;
    }

    $levels = $db->executeS('
        SELECT pl.id_customer, pl.id_level
        FROM '._DB_PREFIX_.'genzo_krona_player_level pl
        INNER JOIN (
            SELECT id_customer, MAX(id_player_level) AS id_player_level
            FROM '._DB_PREFIX_.'genzo_krona_player_level
            GROUP BY id_customer
        ) latest ON latest.id_player_level = pl.id_player_level
    ');

    if ($levels === false) {
        return false;
    }

    $idLevelByCustomer = [];

    foreach ($levels as $levelRow) {
        $idLevelByCustomer[(int)$levelRow['id_customer']] = (int)$levelRow['id_level'];
    }

    foreach ($rows as $row) {
        $idCustomer = (int)$row['id_customer'];
        $points = (int)$row['points'];
        $coins = (int)$row['coins'];

        if ($mode === 'points') {
            $total = $points;
        } elseif ($mode === 'coins') {
            $total = $coins;
        } else {
            $total = $points + $coins;
        }

        $idLevel = (int)($idLevelByCustomer[$idCustomer] ?? 0);

        if (!$db->update('genzo_krona_player', [
            'total' => $total,
            'id_level' => $idLevel,
        ], 'id_customer = '.$idCustomer)) {
            return false;
        }
    }

    return true;
}

function kronaColumnExists($table, $column)
{
    $sql = '
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = "'.pSQL($table).'"
          AND COLUMN_NAME = "'.pSQL($column).'"
        LIMIT 1
    ';

    return !empty(Db::getInstance()->executeS($sql));
}

function kronaIndexExists($table, $indexName)
{
    $sql = '
        SELECT 1
        FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = "'.pSQL($table).'"
          AND INDEX_NAME = "'.pSQL($indexName).'"
        LIMIT 1
    ';

    return !empty(Db::getInstance()->executeS($sql));
}
