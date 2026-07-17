<?php
declare(strict_types=1);

function gameBaseStamina(): int
{
    return 100;
}

function gameStaminaPerQuarter(): int
{
    return 30;
}

function gameBaseBagCapacity(): int
{
    return 20;
}

function gameBagUpgradeStep(): int
{
    return 5;
}

function gameTimeQuarterNames(): array
{
    return ['Morning', 'Day', 'Afternoon', 'Night'];
}

function gameHeroStateCookieName(): string
{
    return 'isekai_hero_state';
}

function gameReadHeroFromCookie(): ?array
{
    $raw = $_COOKIE[gameHeroStateCookieName()] ?? null;
    if (!is_string($raw) || $raw === '') {
        return null;
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return null;
    }

    return $decoded;
}

function gamePersistHeroToCookie(array $hero): void
{
    $cookiePath = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/'))), '/');
    if ($cookiePath === '') {
        $cookiePath = '/';
    }

    $isSecure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    setcookie(gameHeroStateCookieName(), json_encode($hero), [
        'expires' => time() + 60 * 60 * 24 * 14,
        'path' => $cookiePath,
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function gameClearHeroCookie(): void
{
    $cookiePath = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/'))), '/');
    if ($cookiePath === '') {
        $cookiePath = '/';
    }

    setcookie(gameHeroStateCookieName(), '', [
        'expires' => time() - 3600,
        'path' => $cookiePath,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function gameNewHeroState(): array
{
    $baseStamina = gameBaseStamina();
    $baseBagCapacity = gameBaseBagCapacity();

    return [
        'created' => false,
        'name' => 'Adventurer',
        'class' => 'fencer',
        'level' => 1,
        'exp' => 0,
        'gold' => 12,
        'hp' => 30,
        'max_hp' => 30,
        'stamina' => $baseStamina,
        'max_stamina' => $baseStamina,
        'day' => 1,
        'day_quarter' => 0,
        'quarter_stamina_spent' => 0,
        'bag_capacity' => $baseBagCapacity,
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
        $cookieHero = gameReadHeroFromCookie();
        if (is_array($cookieHero)) {
            $_SESSION['hero'] = $cookieHero;
            return;
        }

        $_SESSION['hero'] = gameNewHeroState();
    }
}

function gameAppendLog(string $message): void
{
    $_SESSION['hero']['log'] = array_slice(array_merge([date('H:i') . ' - ' . $message], $_SESSION['hero']['log'] ?? []), 0, 7);
}

function gameEnsureStaminaState(array &$hero): void
{
    $baseStamina = gameBaseStamina();
    $hero['max_stamina'] = $baseStamina;

    if (!isset($hero['stamina'])) {
        $hero['stamina'] = $baseStamina;
    }

    $hero['stamina'] = (int) max(0, min($baseStamina, (int) $hero['stamina']));
}

function gameEnsureBagState(array &$hero): void
{
    $baseBagCapacity = gameBaseBagCapacity();

    if (!isset($hero['bag_capacity']) || (int) $hero['bag_capacity'] < $baseBagCapacity) {
        $hero['bag_capacity'] = $baseBagCapacity;
    }

    $hero['bag_capacity'] = (int) $hero['bag_capacity'];
}

function gameGetBagUpgradeCost(array $hero): int
{
    $baseBagCapacity = gameBaseBagCapacity();
    $upgradeStep = gameBagUpgradeStep();
    $currentCapacity = (int) ($hero['bag_capacity'] ?? $baseBagCapacity);
    $tiers = (int) max(0, floor(($currentCapacity - $baseBagCapacity) / $upgradeStep));

    return 15 + ($tiers * 10);
}

function gameEnsureTimeState(array &$hero): void
{
    if (!isset($hero['day']) || (int) $hero['day'] < 1) {
        $hero['day'] = 1;
    }

    if (!isset($hero['day_quarter'])) {
        $hero['day_quarter'] = 0;
    }

    $hero['day_quarter'] = ((int) $hero['day_quarter']) % 4;
    if ((int) $hero['day_quarter'] < 0) {
        $hero['day_quarter'] = 0;
    }

    if (!isset($hero['quarter_stamina_spent']) || (int) $hero['quarter_stamina_spent'] < 0) {
        $hero['quarter_stamina_spent'] = 0;
    }

    $hero['quarter_stamina_spent'] = (int) min(gameStaminaPerQuarter() - 1, (int) $hero['quarter_stamina_spent']);
}

function gameAdvanceTimeQuarters(array &$hero, int $quarters = 1): void
{
    gameEnsureTimeState($hero);

    if ($quarters <= 0) {
        return;
    }

    for ($i = 0; $i < $quarters; $i++) {
        $hero['day_quarter']++;
        if ((int) $hero['day_quarter'] >= 4) {
            $hero['day_quarter'] = 0;
            $hero['day'] = max(1, (int) $hero['day'] + 1);
        }
    }
}

function gameSpendStaminaAndAdvanceTime(array &$hero, int $staminaCost): void
{
    gameEnsureStaminaState($hero);
    gameEnsureTimeState($hero);

    if ($staminaCost <= 0) {
        return;
    }

    $hero['stamina'] = (int) max(0, (int) $hero['stamina'] - $staminaCost);
    $hero['quarter_stamina_spent'] += $staminaCost;

    $staminaPerQuarter = gameStaminaPerQuarter();
    while ((int) $hero['quarter_stamina_spent'] >= $staminaPerQuarter) {
        $hero['quarter_stamina_spent'] -= $staminaPerQuarter;
        gameAdvanceTimeQuarters($hero, 1);
    }
}

function gameGetWorldTimeLabel(array $hero): string
{
    $day = max(1, (int) ($hero['day'] ?? 1));
    $quarter = (int) ($hero['day_quarter'] ?? 0);
    $quarterNames = gameTimeQuarterNames();
    $quarterName = $quarterNames[$quarter] ?? $quarterNames[0];

    return 'Day ' . $day . ' - ' . $quarterName;
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
        $hero['stamina'] = gameBaseStamina();
        $hero['max_stamina'] = gameBaseStamina();
        $hero['day'] = 1;
        $hero['day_quarter'] = 0;
        $hero['quarter_stamina_spent'] = 0;
        $hero['bag_capacity'] = gameBaseBagCapacity();
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

    gameEnsureStaminaState($hero);
    gameEnsureTimeState($hero);
    gameEnsureBagState($hero);

    $actionStaminaCost = [
        'hunt' => 3,
        'sell' => 2,
        'buy' => 1,
        'expand_bag' => 1,
        'rest' => 0,
    ];

    $staminaCost = $actionStaminaCost[$action] ?? null;
    if ($staminaCost !== null && $hero['stamina'] < $staminaCost) {
        gameAppendLog('Not enough stamina to perform this action.');
        return;
    }

    if ($staminaCost !== null && $staminaCost > 0) {
        gameSpendStaminaAndAdvanceTime($hero, $staminaCost);
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

            $lootAdded = false;
            if (count($hero['inventory']) < (int) $hero['bag_capacity']) {
                $hero['inventory'][] = [
                    'name' => $loot,
                    'type' => 'loot',
                    'value' => gameRandomRange([2, 7]),
                ];
                $lootAdded = true;
            }

            $leveledUp = gameGainExperience($hero, $xpGain);
            $hero['hp'] = max(1, $hero['hp'] - max(1, intdiv($damageTaken, 2)));

            if ($lootAdded) {
                gameAppendLog('Victory over ' . $monster['name'] . '. +' . $goldGain . ' gold, +' . $xpGain . ' XP, item found: ' . $loot . '.');
            } else {
                gameAppendLog('Victory over ' . $monster['name'] . '. +' . $goldGain . ' gold, +' . $xpGain . ' XP. Bag is full, loot left behind.');
            }
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
        $bardRestBonus = 2;
        if ($hero['gold'] >= $cost) {
            $hero['gold'] -= $cost;
            $hero['hp'] = $hero['max_hp'];
            $hero['stamina'] = $hero['max_stamina'];
            $hero['quarter_stamina_spent'] = 0;
            gameAdvanceTimeQuarters($hero, 1);
            gameAppendLog('You rested in town and fully recovered.');

            if (($hero['class'] ?? '') === 'bard') {
                $hero['gold'] += $bardRestBonus;
                gameAppendLog('Bard bonus: +' . $bardRestBonus . ' gold from your performance in town.');
            }
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

    if ($action === 'expand_bag') {
        $upgradeStep = gameBagUpgradeStep();
        $upgradeCost = gameGetBagUpgradeCost($hero);

        if ($hero['gold'] >= $upgradeCost) {
            $hero['gold'] -= $upgradeCost;
            $hero['bag_capacity'] += $upgradeStep;
            gameAppendLog('Bag expanded to ' . $hero['bag_capacity'] . ' slots for ' . $upgradeCost . ' gold.');
        } else {
            gameAppendLog('Not enough gold to expand bag. Need ' . $upgradeCost . ' gold.');
        }

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
