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

function gameExpeditionThreatCap(): int
{
    return 5;
}

function gameExpeditionEliteInterval(): int
{
    return 5;
}

function gameRouteDefinitions(): array
{
    return [
        'meadow' => [
            'name' => 'Crossroad Meadow',
            'material_id' => 'wild_essence',
        ],
        'ruins' => [
            'name' => 'Sunken Ruins',
            'material_id' => 'ruin_fragment',
        ],
    ];
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
        'location' => 'town',
        'expedition' => [
            'active' => false,
            'route_id' => 'meadow',
            'threat' => 0,
            'wins' => 0,
            'pending_gold' => 0,
            'pending_xp' => 0,
            'pending_loot' => [],
        ],
        'inventory' => [],
        'owned_equipment' => [],
        'equipment_upgrades' => [],
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

function gameLootIdFromName(string $name): ?string
{
    $target = strtolower(trim($name));
    if ($target === '') {
        return null;
    }

    foreach (gameLootCatalog() as $lootId => $loot) {
        if (strtolower((string) ($loot['name'] ?? '')) === $target) {
            return (string) $lootId;
        }
    }

    return null;
}

function gameBuildLootEntry(string $lootId): ?array
{
    $catalog = gameLootCatalog();
    if (!isset($catalog[$lootId])) {
        return null;
    }

    $loot = $catalog[$lootId];

    return [
        'item_id' => $lootId,
        'name' => (string) ($loot['name'] ?? $lootId),
        'type' => (string) ($loot['type'] ?? 'common'),
        'value' => (int) ($loot['sell_value'] ?? 0),
    ];
}

function gameEnsureInventoryState(array &$hero): void
{
    if (!isset($hero['inventory']) || !is_array($hero['inventory'])) {
        $hero['inventory'] = [];
    }

    $normalized = [];
    foreach ($hero['inventory'] as $item) {
        if (!is_array($item)) {
            continue;
        }

        $itemId = (string) ($item['item_id'] ?? '');
        if ($itemId === '') {
            $itemId = (string) (gameLootIdFromName((string) ($item['name'] ?? '')) ?? '');
        }

        if ($itemId !== '') {
            $entry = gameBuildLootEntry($itemId);
            if ($entry !== null) {
                $normalized[] = $entry;
                continue;
            }
        }

        $normalized[] = [
            'item_id' => null,
            'name' => (string) ($item['name'] ?? 'Unknown Item'),
            'type' => (string) ($item['type'] ?? 'common'),
            'value' => (int) max(0, (int) ($item['value'] ?? 0)),
        ];
    }

    $hero['inventory'] = array_slice($normalized, 0, max(0, (int) ($hero['bag_capacity'] ?? gameBaseBagCapacity())));
}

function gameEnsureEquipmentProgressionState(array &$hero): void
{
    if (!isset($hero['owned_equipment']) || !is_array($hero['owned_equipment'])) {
        $hero['owned_equipment'] = [];
    }
    if (!isset($hero['equipment_upgrades']) || !is_array($hero['equipment_upgrades'])) {
        $hero['equipment_upgrades'] = [];
    }

    $catalog = gameEquipmentCatalog();
    $validIds = [];
    foreach ($catalog as $slotItems) {
        foreach ($slotItems as $itemId => $item) {
            $validIds[(string) $itemId] = true;
        }
    }

    $owned = [];
    foreach ($hero['owned_equipment'] as $itemId) {
        $id = (string) $itemId;
        if (isset($validIds[$id])) {
            $owned[] = $id;
        }
    }
    $hero['owned_equipment'] = array_values(array_unique($owned));

    $normalizedUpgrades = [];
    foreach ($hero['equipment_upgrades'] as $itemId => $level) {
        $id = (string) $itemId;
        if (!isset($validIds[$id])) {
            continue;
        }
        $normalizedUpgrades[$id] = (int) max(0, min(3, (int) $level));
    }
    $hero['equipment_upgrades'] = $normalizedUpgrades;

    foreach (['weapon', 'armor'] as $slot) {
        $equippedId = (string) ($hero['equipped'][$slot] ?? '');
        if ($equippedId === '' || !isset($validIds[$equippedId])) {
            $hero['equipped'][$slot] = null;
            continue;
        }

        if (!in_array($equippedId, $hero['owned_equipment'], true)) {
            $hero['owned_equipment'][] = $equippedId;
        }
    }
}

function gameGetInventoryCounts(array $hero): array
{
    $counts = [];
    foreach (($hero['inventory'] ?? []) as $item) {
        $itemId = (string) ($item['item_id'] ?? '');
        if ($itemId === '') {
            continue;
        }
        $counts[$itemId] = ($counts[$itemId] ?? 0) + 1;
    }

    return $counts;
}

function gameHasRequiredMaterials(array $hero, array $requirements): bool
{
    $counts = gameGetInventoryCounts($hero);
    foreach ($requirements as $itemId => $qty) {
        if (($counts[(string) $itemId] ?? 0) < (int) $qty) {
            return false;
        }
    }

    return true;
}

function gameConsumeInventoryItems(array &$hero, array $requirements): bool
{
    $inventory = $hero['inventory'] ?? [];
    $indexesToRemove = [];

    foreach ($requirements as $itemId => $qty) {
        $needed = (int) $qty;
        if ($needed <= 0) {
            continue;
        }

        foreach ($inventory as $index => $entry) {
            if ($needed <= 0) {
                break;
            }
            if ((string) ($entry['item_id'] ?? '') === (string) $itemId) {
                $indexesToRemove[] = $index;
                $needed--;
            }
        }

        if ($needed > 0) {
            return false;
        }
    }

    foreach (array_unique($indexesToRemove) as $index) {
        unset($inventory[$index]);
    }
    $hero['inventory'] = array_values($inventory);

    return true;
}

function gameGetEquipmentUpgradeLevel(array $hero, string $itemId): int
{
    return (int) ($hero['equipment_upgrades'][$itemId] ?? 0);
}

function gameGetEquipmentUpgradeCost(array $item, int $currentLevel): int
{
    $basePrice = (int) ($item['price'] ?? 0);
    $nextTier = max(1, $currentLevel + 1);

    return $basePrice * $nextTier;
}

function gameGetEquipmentTotalStats(array $item, int $upgradeLevel): array
{
    $baseStats = (array) ($item['stats'] ?? []);
    $stepStats = (array) ($item['upgrade_step'] ?? []);
    $keys = ['attack', 'defense', 'magic', 'speed'];
    $finalStats = [];

    foreach ($keys as $key) {
        $finalStats[$key] = (int) ($baseStats[$key] ?? 0) + ((int) ($stepStats[$key] ?? 0) * $upgradeLevel);
    }

    return $finalStats;
}

function gameEnsureExpeditionState(array &$hero): void
{
    if (!isset($hero['expedition']) || !is_array($hero['expedition'])) {
        $hero['expedition'] = [];
    }

    $routes = gameRouteDefinitions();
    $defaultRoute = (string) (array_key_first($routes) ?? 'meadow');

    $hero['expedition']['active'] = !empty($hero['expedition']['active']);

    $routeId = (string) ($hero['expedition']['route_id'] ?? $defaultRoute);
    if (!isset($routes[$routeId])) {
        $routeId = $defaultRoute;
    }
    $hero['expedition']['route_id'] = $routeId;

    $hero['expedition']['threat'] = (int) max(0, min(gameExpeditionThreatCap(), (int) ($hero['expedition']['threat'] ?? 0)));
    $hero['expedition']['wins'] = (int) max(0, (int) ($hero['expedition']['wins'] ?? 0));
    $hero['expedition']['pending_gold'] = (int) max(0, (int) ($hero['expedition']['pending_gold'] ?? 0));
    $hero['expedition']['pending_xp'] = (int) max(0, (int) ($hero['expedition']['pending_xp'] ?? 0));

    if (!isset($hero['expedition']['pending_loot']) || !is_array($hero['expedition']['pending_loot'])) {
        $hero['expedition']['pending_loot'] = [];
    }

    $normalizedLoot = [];
    foreach ($hero['expedition']['pending_loot'] as $item) {
        if (!is_array($item)) {
            continue;
        }

        $itemId = (string) ($item['item_id'] ?? '');
        if ($itemId === '') {
            $itemId = (string) (gameLootIdFromName((string) ($item['name'] ?? '')) ?? '');
        }
        if ($itemId !== '') {
            $entry = gameBuildLootEntry($itemId);
            if ($entry !== null) {
                $normalizedLoot[] = $entry;
                continue;
            }
        }

        $normalizedLoot[] = [
            'item_id' => null,
            'name' => (string) ($item['name'] ?? 'Unknown Material'),
            'type' => (string) ($item['type'] ?? 'common'),
            'value' => (int) max(0, (int) ($item['value'] ?? 0)),
        ];
    }

    $hero['expedition']['pending_loot'] = array_slice($normalizedLoot, 0, max(0, (int) ($hero['bag_capacity'] ?? gameBaseBagCapacity())));
}

function gameIsInTown(array $hero): bool
{
    return (string) ($hero['location'] ?? 'town') === 'town';
}

function gameGetTotalCarriedLootCount(array $hero): int
{
    $inventoryCount = is_array($hero['inventory'] ?? null) ? count($hero['inventory']) : 0;
    $pendingCount = is_array($hero['expedition']['pending_loot'] ?? null) ? count($hero['expedition']['pending_loot']) : 0;

    return $inventoryCount + $pendingCount;
}

function gameStartExpedition(array &$hero, string $routeId): void
{
    gameEnsureExpeditionState($hero);
    $routes = gameRouteDefinitions();
    if (!isset($routes[$routeId])) {
        $routeId = (string) (array_key_first($routes) ?? 'meadow');
    }

    $hero['location'] = 'expedition';
    $hero['expedition']['active'] = true;
    $hero['expedition']['route_id'] = $routeId;
}

function gameGetThreatMultiplier(array $hero): float
{
    $threat = (int) ($hero['expedition']['threat'] ?? 0);

    return 1 + (0.08 * $threat);
}

function gameGetRewardMultiplier(array $hero): float
{
    $threat = (int) ($hero['expedition']['threat'] ?? 0);

    return 1 + (0.10 * $threat);
}

function gameAddPendingRewards(array &$hero, array $rewards): void
{
    gameEnsureExpeditionState($hero);
    $hero['expedition']['pending_gold'] += (int) max(0, (int) ($rewards['gold'] ?? 0));
    $hero['expedition']['pending_xp'] += (int) max(0, (int) ($rewards['xp'] ?? 0));

    $loot = $rewards['loot'] ?? null;
    if (is_array($loot)) {
        $itemId = (string) ($loot['item_id'] ?? '');
        if ($itemId !== '') {
            $entry = gameBuildLootEntry($itemId);
            if ($entry !== null) {
                $hero['expedition']['pending_loot'][] = $entry;
                return;
            }
        }

        $hero['expedition']['pending_loot'][] = [
            'item_id' => null,
            'name' => (string) ($loot['name'] ?? 'Unknown Loot'),
            'type' => (string) ($loot['type'] ?? 'common'),
            'value' => (int) max(0, (int) ($loot['value'] ?? 0)),
        ];
    }
}

function gameBankExpeditionRewards(array &$hero): void
{
    gameEnsureExpeditionState($hero);
    $pendingGold = (int) ($hero['expedition']['pending_gold'] ?? 0);
    $pendingXp = (int) ($hero['expedition']['pending_xp'] ?? 0);
    $pendingLoot = $hero['expedition']['pending_loot'] ?? [];

    $hero['gold'] += $pendingGold;
    $leveledUp = false;
    if ($pendingXp > 0) {
        $leveledUp = gameGainExperience($hero, $pendingXp);
    }

    if (is_array($pendingLoot) && !empty($pendingLoot)) {
        foreach ($pendingLoot as $item) {
            $hero['inventory'][] = $item;
        }
    }

    $hero['expedition']['pending_gold'] = 0;
    $hero['expedition']['pending_xp'] = 0;
    $hero['expedition']['pending_loot'] = [];

    if ($leveledUp) {
        gameAppendLog('You leveled up. The isekai is getting less hostile.');
    }
}

function gameReturnToTown(array &$hero): void
{
    gameEnsureExpeditionState($hero);

    $bankGold = (int) ($hero['expedition']['pending_gold'] ?? 0);
    $bankXp = (int) ($hero['expedition']['pending_xp'] ?? 0);
    $bankLoot = is_array($hero['expedition']['pending_loot'] ?? null) ? count($hero['expedition']['pending_loot']) : 0;

    gameBankExpeditionRewards($hero);

    $hero['expedition']['active'] = false;
    $hero['expedition']['threat'] = 0;
    $hero['expedition']['wins'] = 0;
    $hero['location'] = 'town';

    gameAppendLog('You returned to town. Banked ' . $bankGold . ' gold, ' . $bankXp . ' XP, and ' . $bankLoot . ' item(s).');
}

function gameFailExpedition(array &$hero): void
{
    gameEnsureExpeditionState($hero);

    $pendingGold = (int) ($hero['expedition']['pending_gold'] ?? 0);
    $lostGold = (int) floor($pendingGold * 0.5);
    $keptGold = $pendingGold - $lostGold;

    $pendingLoot = $hero['expedition']['pending_loot'] ?? [];
    $lostLootName = null;
    if (is_array($pendingLoot) && count($pendingLoot) > 0) {
        $lostLootIndex = array_rand($pendingLoot);
        $lostLootName = (string) ($pendingLoot[$lostLootIndex]['name'] ?? 'Loot');
        unset($pendingLoot[$lostLootIndex]);
        $pendingLoot = array_values($pendingLoot);
    }

    $hero['gold'] += max(0, $keptGold);
    $pendingXp = (int) ($hero['expedition']['pending_xp'] ?? 0);
    $leveledUp = false;
    if ($pendingXp > 0) {
        $leveledUp = gameGainExperience($hero, $pendingXp);
    }

    foreach ($pendingLoot as $item) {
        $hero['inventory'][] = $item;
    }

    $hero['expedition']['active'] = false;
    $hero['expedition']['threat'] = 0;
    $hero['expedition']['wins'] = 0;
    $hero['expedition']['pending_gold'] = 0;
    $hero['expedition']['pending_xp'] = 0;
    $hero['expedition']['pending_loot'] = [];
    $hero['location'] = 'town';

    $message = 'Expedition failed. Lost ' . $lostGold . ' pending gold';
    if ($lostLootName !== null) {
        $message .= ' and dropped ' . $lostLootName;
    }
    $message .= '. You kept ' . $pendingXp . ' pending XP.';
    gameAppendLog($message);

    if ($leveledUp) {
        gameAppendLog('You leveled up. The isekai is getting less hostile.');
    }
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
            $item = (array) $slotItems[$itemId];
            $upgradeLevel = (int) ($_SESSION['hero']['equipment_upgrades'][$itemId] ?? 0);

            return gameGetEquipmentTotalStats($item, $upgradeLevel);
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
        $hero['location'] = 'town';
        $hero['expedition'] = [
            'active' => false,
            'route_id' => 'meadow',
            'threat' => 0,
            'wins' => 0,
            'pending_gold' => 0,
            'pending_xp' => 0,
            'pending_loot' => [],
        ];
        $hero['inventory'] = [];
        $hero['owned_equipment'] = [];
        $hero['equipment_upgrades'] = [];
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
    gameEnsureInventoryState($hero);
    gameEnsureExpeditionState($hero);
    gameEnsureEquipmentProgressionState($hero);

    if ($action === 'select_route') {
        if (!gameIsInTown($hero)) {
            gameAppendLog('You can only change route while in town.');
            return;
        }

        $routeId = (string) ($request['route_id'] ?? 'meadow');
        $routes = gameRouteDefinitions();
        if (!isset($routes[$routeId])) {
            gameAppendLog('Invalid route selected.');
            return;
        }

        $hero['expedition']['route_id'] = $routeId;
        gameAppendLog('Route selected: ' . $routes[$routeId]['name'] . '.');
        return;
    }

    if ($action === 'return_town') {
        if (!($hero['expedition']['active'] ?? false)) {
            gameAppendLog('You are already in town.');
            return;
        }

        gameReturnToTown($hero);
        return;
    }

    if (in_array($action, ['rest', 'sell', 'sell_item', 'buy', 'equip', 'upgrade_equipment', 'craft_item', 'expand_bag'], true) && !gameIsInTown($hero)) {
        gameAppendLog('This action is only available in town. Return to town first.');
        return;
    }

    $actionStaminaCost = [
        'hunt' => 3,
        'sell' => 2,
        'sell_item' => 2,
        'buy' => 1,
        'equip' => 0,
        'upgrade_equipment' => 1,
        'craft_item' => 1,
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
        if (!($hero['expedition']['active'] ?? false)) {
            gameStartExpedition($hero, (string) ($hero['expedition']['route_id'] ?? 'meadow'));
            $routes = gameRouteDefinitions();
            $routeId = (string) ($hero['expedition']['route_id'] ?? 'meadow');
            $routeName = $routes[$routeId]['name'] ?? 'Unknown Route';
            gameAppendLog('Expedition started at ' . $routeName . '.');
        }

        $hero['location'] = 'expedition';

        $isEliteEncounter = ((int) ($hero['expedition']['wins'] ?? 0)) >= (gameExpeditionEliteInterval() - 1);
        $monster = $monsterPool[array_rand($monsterPool)];

        if ($isEliteEncounter) {
            usort($monsterPool, static function (array $a, array $b): int {
                $aPower = ((int) ($a['attack'] ?? 0) * 2) + (int) ($a['defense'] ?? 0) + (int) ($a['magic'] ?? 0) + (int) ($a['speed'] ?? 0);
                $bPower = ((int) ($b['attack'] ?? 0) * 2) + (int) ($b['defense'] ?? 0) + (int) ($b['magic'] ?? 0) + (int) ($b['speed'] ?? 0);

                return $bPower <=> $aPower;
            });
            $monster = $monsterPool[0];
            $monster['name'] = 'Elite ' . $monster['name'];
            $monster['hp'] = (int) floor((int) $monster['hp'] * 1.2);
        }

        $threatMultiplier = gameGetThreatMultiplier($hero);
        $monster['attack'] = (int) max(1, floor((int) $monster['attack'] * $threatMultiplier));
        $monster['defense'] = (int) max(1, floor((int) $monster['defense'] * $threatMultiplier));
        $monster['magic'] = (int) max(0, floor((int) $monster['magic'] * $threatMultiplier));
        $monster['speed'] = (int) max(1, floor((int) $monster['speed'] * $threatMultiplier));
        $monster['hp'] = (int) max(1, floor((int) $monster['hp'] * $threatMultiplier));

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

            $rewardMultiplier = gameGetRewardMultiplier($hero);
            $goldGain = (int) max(1, floor($goldGain * $rewardMultiplier));
            $xpGain = (int) max(1, floor($xpGain * $rewardMultiplier));

            $lootId = (string) ($monster['loot'][array_rand($monster['loot'])] ?? '');
            $lootEntry = gameBuildLootEntry($lootId);
            $lootName = (string) ($lootEntry['name'] ?? 'Unknown Loot');

            $lootAdded = false;
            if (gameGetTotalCarriedLootCount($hero) < (int) $hero['bag_capacity']) {
                gameAddPendingRewards($hero, [
                    'gold' => $goldGain,
                    'xp' => $xpGain,
                    'loot' => [
                        'item_id' => $lootId,
                        'name' => $lootName,
                    ],
                ]);
                $lootAdded = true;
            } else {
                gameAddPendingRewards($hero, [
                    'gold' => $goldGain,
                    'xp' => $xpGain,
                ]);
            }

            $eliteMaterialAdded = false;
            if ($isEliteEncounter && gameGetTotalCarriedLootCount($hero) < (int) $hero['bag_capacity']) {
                $routeId = (string) ($hero['expedition']['route_id'] ?? 'meadow');
                $routes = gameRouteDefinitions();
                $materialId = (string) ($routes[$routeId]['material_id'] ?? 'wild_essence');
                gameAddPendingRewards($hero, [
                    'loot' => [
                        'item_id' => $materialId,
                    ],
                ]);
                $eliteMaterialAdded = true;
            }

            $hero['expedition']['wins'] = (int) ($hero['expedition']['wins'] ?? 0) + 1;
            $hero['expedition']['threat'] = (int) min(gameExpeditionThreatCap(), (int) ($hero['expedition']['threat'] ?? 0) + 1);

            $hero['hp'] = max(1, $hero['hp'] - max(1, intdiv($damageTaken, 2)));

            $pendingGold = (int) ($hero['expedition']['pending_gold'] ?? 0);
            $pendingXp = (int) ($hero['expedition']['pending_xp'] ?? 0);
            $pendingLootCount = count($hero['expedition']['pending_loot'] ?? []);

            if ($lootAdded) {
                gameAppendLog('Victory over ' . $monster['name'] . '. +' . $goldGain . ' gold and +' . $xpGain . ' XP are now pending. Loot found: ' . $lootName . '.');
            } else {
                gameAppendLog('Victory over ' . $monster['name'] . '. +' . $goldGain . ' gold and +' . $xpGain . ' XP are now pending. Bag is full, loot left behind.');
            }

            if ($isEliteEncounter) {
                if ($eliteMaterialAdded) {
                    gameAppendLog('Elite reward secured: route material added to pending loot.');
                } else {
                    gameAppendLog('Elite reward found, but bag is full and material was left behind.');
                }
            }

            gameAppendLog('Threat is now ' . (int) $hero['expedition']['threat'] . '/' . gameExpeditionThreatCap() . '. Pending: ' . $pendingGold . ' gold, ' . $pendingXp . ' XP, ' . $pendingLootCount . ' item(s).');
        } else {
            $hero['hp'] = max(1, $hero['hp'] - max(3, $damageTaken));
            gameAppendLog('Partial defeat against ' . $monster['name'] . '. You escaped with ' . $hero['hp'] . ' HP.');
            gameFailExpedition($hero);
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
            if (($item['type'] ?? '') === 'common') {
                $sold++;
                $goldEarned += (int) ($item['value'] ?? 0);
            } else {
                $remainingInventory[] = $item;
            }
        }

        $hero['inventory'] = $remainingInventory;
        $hero['gold'] += $goldEarned;
        gameAppendLog('Sold ' . $sold . ' common loot items for ' . $goldEarned . ' gold in town.');
        return;
    }

    if ($action === 'sell_item') {
        $itemId = (string) ($request['item_id'] ?? '');
        $quantity = (int) max(1, (int) ($request['quantity'] ?? 1));
        if ($itemId === '') {
            gameAppendLog('Could not sell item: invalid item selection.');
            return;
        }

        $catalog = gameLootCatalog();
        if (!isset($catalog[$itemId])) {
            gameAppendLog('Could not sell item: unknown item id.');
            return;
        }

        $removed = 0;
        $kept = [];
        foreach ($hero['inventory'] as $entry) {
            if ($removed < $quantity && (string) ($entry['item_id'] ?? '') === $itemId) {
                $removed++;
                continue;
            }
            $kept[] = $entry;
        }

        if ($removed <= 0) {
            gameAppendLog('You do not have this item in your bag.');
            return;
        }

        $hero['inventory'] = $kept;
        $unitValue = (int) ($catalog[$itemId]['sell_value'] ?? 0);
        $totalValue = $unitValue * $removed;
        $hero['gold'] += $totalValue;
        gameAppendLog('Sold ' . $removed . 'x ' . $catalog[$itemId]['name'] . ' for ' . $totalValue . ' gold.');
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

        if ($item === null) {
            gameAppendLog('Could not buy the selected equipment.');
            return;
        }

        if (in_array($itemId, $hero['owned_equipment'], true)) {
            gameAppendLog('You already own ' . $item['name'] . '. Use Equip or Upgrade instead.');
            return;
        }

        $unlockLevel = (int) ($item['unlock_level'] ?? 1);
        if ((int) $hero['level'] < $unlockLevel) {
            gameAppendLog('You need level ' . $unlockLevel . ' to buy ' . $item['name'] . '.');
            return;
        }

        $materialCost = (array) ($item['material_cost'] ?? []);
        if (!gameHasRequiredMaterials($hero, $materialCost)) {
            gameAppendLog('Missing required materials to buy ' . $item['name'] . '.');
            return;
        }

        if ($hero['gold'] >= $item['price']) {
            $hero['gold'] -= $item['price'];
            if (!empty($materialCost)) {
                gameConsumeInventoryItems($hero, $materialCost);
            }
            $hero['owned_equipment'][] = $itemId;
            $hero['equipment_upgrades'][$itemId] = (int) ($hero['equipment_upgrades'][$itemId] ?? 0);
            if (empty($hero['equipped'][$item['slot']])) {
                $hero['equipped'][$item['slot']] = $itemId;
            }
            gameAppendLog('Bought ' . $item['name'] . ' for ' . (int) $item['price'] . ' gold.');
        } else {
            gameAppendLog('Not enough gold to buy the selected equipment.');
        }
        return;
    }

    if ($action === 'equip') {
        $itemId = (string) ($request['item_id'] ?? '');
        $item = gameFindEquipment($itemId, $equipmentCatalog);
        if ($item === null || !in_array($itemId, $hero['owned_equipment'], true)) {
            gameAppendLog('Could not equip the selected item.');
            return;
        }

        $hero['equipped'][$item['slot']] = $itemId;
        gameAppendLog('Equipped ' . $item['name'] . ' in the ' . $item['slot'] . ' slot.');
        return;
    }

    if ($action === 'upgrade_equipment') {
        $itemId = (string) ($request['item_id'] ?? '');
        $item = gameFindEquipment($itemId, $equipmentCatalog);
        if ($item === null || !in_array($itemId, $hero['owned_equipment'], true)) {
            gameAppendLog('Could not upgrade the selected equipment.');
            return;
        }

        $currentLevel = gameGetEquipmentUpgradeLevel($hero, $itemId);
        if ($currentLevel >= 3) {
            gameAppendLog($item['name'] . ' is already at maximum upgrade level (+3).');
            return;
        }

        $upgradeCost = gameGetEquipmentUpgradeCost($item, $currentLevel);
        if ($hero['gold'] < $upgradeCost) {
            gameAppendLog('Not enough gold to upgrade ' . $item['name'] . '. Need ' . $upgradeCost . ' gold.');
            return;
        }

        $hero['gold'] -= $upgradeCost;
        $hero['equipment_upgrades'][$itemId] = $currentLevel + 1;
        gameAppendLog('Upgraded ' . $item['name'] . ' to +' . ($currentLevel + 1) . ' for ' . $upgradeCost . ' gold.');
        return;
    }

    if ($action === 'craft_item') {
        $recipeId = (string) ($request['recipe_id'] ?? '');
        $recipes = gameCraftingRecipes();
        if (!isset($recipes[$recipeId])) {
            gameAppendLog('Unknown recipe selected.');
            return;
        }

        $recipe = $recipes[$recipeId];
        $goldCost = (int) ($recipe['gold_cost'] ?? 0);
        $ingredients = (array) ($recipe['ingredients'] ?? []);

        if ($hero['gold'] < $goldCost) {
            gameAppendLog('Not enough gold to craft ' . $recipe['name'] . '.');
            return;
        }

        if (!gameHasRequiredMaterials($hero, $ingredients)) {
            gameAppendLog('Missing ingredients for ' . $recipe['name'] . '.');
            return;
        }

        $reward = (array) ($recipe['reward'] ?? []);
        if (($reward['type'] ?? '') === 'equipment') {
            $craftedItemId = (string) ($reward['item_id'] ?? '');
            $craftedItem = gameFindEquipment($craftedItemId, $equipmentCatalog);
            if ($craftedItem === null) {
                gameAppendLog('Recipe reward is invalid.');
                return;
            }
            if (in_array($craftedItemId, $hero['owned_equipment'], true)) {
                gameAppendLog('You already own ' . $craftedItem['name'] . '.');
                return;
            }
        }

        $hero['gold'] -= $goldCost;
        gameConsumeInventoryItems($hero, $ingredients);

        if (($reward['type'] ?? '') === 'equipment') {
            $craftedItemId = (string) ($reward['item_id'] ?? '');
            $craftedItem = gameFindEquipment($craftedItemId, $equipmentCatalog);
            $hero['owned_equipment'][] = $craftedItemId;
            $hero['equipment_upgrades'][$craftedItemId] = (int) ($hero['equipment_upgrades'][$craftedItemId] ?? 0);
            if (!empty($craftedItem['slot']) && empty($hero['equipped'][$craftedItem['slot']])) {
                $hero['equipped'][$craftedItem['slot']] = $craftedItemId;
            }
            gameAppendLog('Crafted ' . $craftedItem['name'] . ' using ' . $recipe['name'] . '.');
            return;
        }

        if (($reward['type'] ?? '') === 'recovery') {
            $restore = (int) max(0, (int) ($reward['stamina_restore'] ?? 0));
            $hero['stamina'] = (int) min((int) $hero['max_stamina'], (int) $hero['stamina'] + $restore);
            gameAppendLog('Crafted ' . $recipe['name'] . '. Restored ' . $restore . ' stamina.');
            return;
        }

        gameAppendLog('Craft completed: ' . $recipe['name'] . '.');
        return;
    }
}
