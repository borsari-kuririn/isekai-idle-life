<?php
declare(strict_types=1);

function gameNewHeroState(): array
{
    return [
        'created' => false,
        'name' => 'Adventurer',
        'class' => 'fencer',
        'level' => 1,
        'exp' => 0,
        'gold' => 12,
        'hp' => 30,
        'max_hp' => 30,
        'inventory' => [],
        'equipped' => [
            'weapon' => null,
            'armor' => null,
        ],
        'battle' => null,
        'log' => ['A new life in a medieval isekai begins.'],
    ];
}

function gameEnsureHero(): void
{
    if (!isset($_SESSION['hero']) || !is_array($_SESSION['hero'])) {
        $_SESSION['hero'] = gameNewHeroState();
    }
}

function gameAppendLog(string $message): void
{
    $_SESSION['hero']['log'] = array_slice(array_merge([date('H:i') . ' - ' . $message], $_SESSION['hero']['log'] ?? []), 0, 7);
}

function gameGetEquippedItemStats(array $equipmentCatalog, ?string $itemId): array
{
    if ($itemId === null) {
        return ['attack' => 0, 'defense' => 0, 'magic' => 0, 'speed' => 0];
    }

    foreach ($equipmentCatalog as $slotItems) {
        if (isset($slotItems[$itemId])) {
            return $slotItems[$itemId]['stats'];
        }
    }

    return ['attack' => 0, 'defense' => 0, 'magic' => 0, 'speed' => 0];
}

function gameCalculateHeroStats(array $hero, array $classDefinitions, array $equipmentCatalog): array
{
    $classKey = gameNormalizeClassKey((string) ($hero['class'] ?? 'fencer'));
    if (!isset($classDefinitions[$classKey])) {
        $classKey = 'fencer';
    }

    $classStats = $classDefinitions[$classKey]['stats'];
    $weaponStats = gameGetEquippedItemStats($equipmentCatalog, $hero['equipped']['weapon']);
    $armorStats = gameGetEquippedItemStats($equipmentCatalog, $hero['equipped']['armor']);

    $stats = ['attack' => 0, 'defense' => 0, 'magic' => 0, 'speed' => 0];

    foreach ($stats as $key => $value) {
        $stats[$key] = (int) ($classStats[$key] ?? 0)
            + (int) ($weaponStats[$key] ?? 0)
            + (int) ($armorStats[$key] ?? 0)
            + (($hero['level'] - 1) * ($key === 'speed' ? 0 : 1));
    }

    return $stats;
}

function gameGainExperience(array &$hero, int $amount): bool
{
    $hero['exp'] += $amount;
    $leveledUp = false;

    while ($hero['exp'] >= $hero['level'] * 12) {
        $hero['exp'] -= $hero['level'] * 12;
        $hero['level']++;
        $hero['max_hp'] += 6;
        $hero['hp'] = $hero['max_hp'];
        $hero['gold'] += 4;
        $leveledUp = true;
    }

    return $leveledUp;
}

function gameRandomRange(array $range): int
{
    return random_int($range[0], $range[1]);
}

function gameFindEquipment(string $itemId, array $equipmentCatalog): ?array
{
    foreach ($equipmentCatalog as $slotItems) {
        if (isset($slotItems[$itemId])) {
            return $slotItems[$itemId];
        }
    }

    return null;
}

function gameHandleAction(?string $action, array $request, array $classDefinitions, array $equipmentCatalog, array $monsterPool): void
{
    gameEnsureHero();
    $hero = &$_SESSION['hero'];

    if ($action === 'reset') {
        $_SESSION['hero'] = gameNewHeroState();
        gameAppendLog('The cycle has been reset.');
        return;
    }

    if ($action === 'create') {
        $name = trim((string) ($request['name'] ?? ''));
        $class = gameNormalizeClassKey((string) ($request['class'] ?? 'fencer'));

        if ($name === '') {
            $name = 'Adventurer';
        }

        if (!isset($classDefinitions[$class])) {
            $class = 'fencer';
        }

        $hero['created'] = true;
        $hero['name'] = substr($name, 0, 20);
        $hero['class'] = $class;
        $hero['level'] = 1;
        $hero['exp'] = 0;
        $hero['gold'] = 20;
        $hero['hp'] = 34;
        $hero['max_hp'] = 34;
        $hero['inventory'] = [];
        $hero['equipped'] = ['weapon' => null, 'armor' => null];
        $hero['battle'] = null;
        $hero['log'] = ['Character created: ' . $hero['name'] . ' (' . $classDefinitions[$class]['name'] . ').'];
        return;
    }

    $hero['class'] = gameNormalizeClassKey((string) ($hero['class'] ?? 'fencer'));
    if (!isset($classDefinitions[$hero['class']])) {
        $hero['class'] = 'fencer';
    }

    if (empty($hero['created'])) {
        return;
    }

    if ($action === 'hunt') {
        $monster = $monsterPool[array_rand($monsterPool)];
        $hero['battle'] = [
            'monster' => $monster,
            'current_hp' => (int) $monster['hp'],
            'max_hp' => (int) $monster['hp'],
        ];
        $stats = gameCalculateHeroStats($hero, $classDefinitions, $equipmentCatalog);
        $heroPower = ($stats['attack'] * 2) + $stats['defense'] + $stats['magic'] + $stats['speed'];
        $monsterPower = ($monster['attack'] * 2) + $monster['defense'] + $monster['magic'] + $monster['speed'];
        $heroRoll = random_int(0, 8) + $heroPower;
        $monsterRoll = random_int(0, 8) + $monsterPower;
        $damageTaken = gameRandomRange([2, 6]) + max(0, $monster['attack'] - $stats['defense']);

        if ($heroRoll >= $monsterRoll) {
            $goldGain = gameRandomRange($monster['gold']);
            $xpGain = gameRandomRange($monster['xp']);
            $loot = $monster['loot'][array_rand($monster['loot'])];

            $hero['gold'] += $goldGain;
            $hero['inventory'][] = [
                'name' => $loot,
                'type' => 'loot',
                'value' => gameRandomRange([2, 7]),
            ];

            $leveledUp = gameGainExperience($hero, $xpGain);
            $hero['hp'] = max(1, $hero['hp'] - max(1, intdiv($damageTaken, 2)));

            gameAppendLog('Victory over ' . $monster['name'] . '. +' . $goldGain . ' gold, +' . $xpGain . ' XP, item found: ' . $loot . '.');
            if ($leveledUp) {
                gameAppendLog('You leveled up. The isekai is getting less hostile.');
            }
        } else {
            $hero['hp'] = max(1, $hero['hp'] - max(3, $damageTaken));
            $hero['gold'] = max(0, $hero['gold'] - 1);
            gameAppendLog('Partial defeat against ' . $monster['name'] . '. You escaped with ' . $hero['hp'] . ' HP.');
        }

        return;
    }

    if ($action === 'rest') {
        $cost = 4;
        if ($hero['gold'] >= $cost) {
            $hero['gold'] -= $cost;
            $hero['hp'] = $hero['max_hp'];
            gameAppendLog('You rested in town and fully recovered.');
        } else {
            gameAppendLog('Not enough gold to rest.');
        }
        return;
    }

    if ($action === 'sell') {
        $sold = 0;
        $goldEarned = 0;
        $remainingInventory = [];

        foreach ($hero['inventory'] as $item) {
            if (($item['type'] ?? '') === 'loot') {
                $sold++;
                $goldEarned += (int) ($item['value'] ?? 0);
            } else {
                $remainingInventory[] = $item;
            }
        }

        $hero['inventory'] = $remainingInventory;
        $hero['gold'] += $goldEarned;
        gameAppendLog('Sold ' . $sold . ' loot items for ' . $goldEarned . ' gold in town.');
        return;
    }

    if ($action === 'buy') {
        $itemId = (string) ($request['item_id'] ?? '');
        $item = gameFindEquipment($itemId, $equipmentCatalog);

        if ($item !== null && $hero['gold'] >= $item['price']) {
            $hero['gold'] -= $item['price'];
            $hero['equipped'][$item['slot']] = $itemId;
            gameAppendLog('Equipped ' . $item['name'] . ' in the ' . $item['slot'] . ' slot.');
        } else {
            gameAppendLog('Could not buy the selected equipment.');
        }
    }
}
