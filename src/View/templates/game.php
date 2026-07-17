<?php
declare(strict_types=1);

function renderItemStats(array $stats): string
{
    $parts = [];

    foreach ($stats as $stat => $value) {
        $parts[] = ucfirst($stat) . ' +' . $value;
    }

    return implode(' | ', $parts);
}

$timePhaseByQuarter = [
    0 => 'morning',
    1 => 'day',
    2 => 'afternoon',
    3 => 'night',
];

$currentTimePhase = 'morning';
if (!empty($hero['created'])) {
    $currentQuarter = ((int) ($hero['day_quarter'] ?? 0)) % 4;
    if ($currentQuarter < 0) {
        $currentQuarter = 0;
    }

    $currentTimePhase = $timePhaseByQuarter[$currentQuarter] ?? 'morning';
}

$cssVersion = (string) @filemtime(__DIR__ . '/../../../Assets/css/app.css');
if ($cssVersion === '' || $cssVersion === '0') {
    $cssVersion = '1';
}

$jsVersion = (string) @filemtime(__DIR__ . '/../../../Assets/js/app.js');
if ($jsVersion === '' || $jsVersion === '0') {
    $jsVersion = '1';
}
?><!DOCTYPE html>
<html lang="en" data-current-time-phase="<?= htmlspecialchars($currentTimePhase) ?>" data-time-phase="<?= htmlspecialchars($currentTimePhase) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Isekai Idle Life</title>
    <link rel="stylesheet" href="Assets/css/app.css?v=<?= htmlspecialchars($cssVersion) ?>">
</head>
<body>
<div class="wrap">
    <section class="hero-banner">
        <div class="hero-banner-row">
            <h1>Isekai Idle Life</h1>
            <?php if (!empty($hero['created'])): ?>
                <div class="world-time-banner" aria-label="World time">
                    <span>TIME</span>
                    <strong><?= htmlspecialchars((string) ($worldTimeLabel ?? 'Day 1 - Morning')) ?></strong>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php if (empty($hero['created'])): ?>
        <section class="card panel">
            <h2>Create Character</h2>
            <?php $defaultClassId = (string) (array_key_first($classDefinitions) ?? 'fencer'); ?>
            <form method="post" class="panel">
                <input type="hidden" name="action" value="create">
                <div class="form-row">
                    <label>
                        Hero Name
                        <input type="text" name="name" maxlength="20" placeholder="Example: Ryu, Aira, Kael">
                    </label>
                    <div class="class-selected-chip">
                        Starting Class
                        <strong>Click a class card below</strong>
                    </div>
                </div>

                <div class="two-col">
                    <?php foreach ($classDefinitions as $id => $class): ?>
                    <?php
                        $classFaceImage = (string) ($class['image'] ?? '');
                        $classPreviewImage = str_replace('_Face.png', '.png', $classFaceImage);
                    ?>
                    <label class="item-card class-card class-card-select" for="class-<?= htmlspecialchars($id) ?>">
                        <input
                            class="class-radio"
                            type="radio"
                            name="class"
                            id="class-<?= htmlspecialchars($id) ?>"
                            value="<?= htmlspecialchars($id) ?>"
                            <?= $id === $defaultClassId ? 'checked' : '' ?>
                        >
                        <div class="class-portrait" aria-hidden="true">
                            <img src="<?= htmlspecialchars($classPreviewImage) ?>" alt="<?= htmlspecialchars((string) ($class['name'] ?? 'Class')) ?> portrait" onerror="this.onerror=null;this.src='<?= htmlspecialchars($classFaceImage) ?>';">
                        </div>
                        <div class="class-copy">
                            <strong class="class-name"><?= htmlspecialchars($class['name']) ?></strong>
                            <small class="class-desc"><?= htmlspecialchars($class['description']) ?></small>
                            <small class="class-base">
                                <strong>Base attributes</strong>
                                <span class="class-attr"><strong>Attack:</strong> <?= (int) $class['stats']['attack'] ?></span>
                                <span class="class-attr"><strong>Defense:</strong> <?= (int) $class['stats']['defense'] ?></span>
                                <span class="class-attr"><strong>Magic:</strong> <?= (int) $class['stats']['magic'] ?></span>
                                <span class="class-attr"><strong>Speed:</strong> <?= (int) $class['stats']['speed'] ?></span>
                            </small>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>

                <div class="btn-row">
                    <button type="submit">Start Adventure</button>
                </div>
            </form>
        </section>
    <?php else: ?>
        <section class="dashboard">
            <div class="card panel">
                <div class="hero-head">
                    <div class="class-portrait class-portrait-hero" aria-hidden="true">
                        <img src="<?= htmlspecialchars((string) ($classInfo['image'] ?? '')) ?>" alt="<?= htmlspecialchars((string) ($classInfo['name'] ?? 'Class')) ?> portrait" onerror="this.style.display='none'">
                    </div>
                    <h2 class="hero-identity">
                        <span class="hero-name"><?= htmlspecialchars((string) ($hero['name'] ?? 'Adventurer')) ?></span>
                        <span class="hero-class">Class: <?= htmlspecialchars((string) ($classInfo['name'] ?? 'Fencer')) ?></span>
                        <span class="hero-title">Title: <?= htmlspecialchars((string) ($heroTitle ?? 'Novice Adventurer')) ?></span>
                        <small class="hero-desc"><?= htmlspecialchars((string) ($classInfo['description'] ?? '')) ?></small>
                    </h2>
                </div>
                <div class="status-block">
                    <div class="status-primary">
                        <div class="hud-bar dark" style="--fill: <?= $hpPercent ?>%;">
                            <div class="hud-fill"></div>
                            <div class="hud-meta"><span>HP</span><strong><?= (int) $hero['hp'] ?>/<?= (int) $hero['max_hp'] ?></strong></div>
                        </div>
                        <div class="hud-bar dark" style="--fill: <?= $xpPercent ?>%;">
                            <div class="hud-fill"></div>
                            <div class="hud-meta"><span>XP</span><strong><?= (int) $hero['exp'] ?>/<?= $xpToNext ?></strong></div>
                        </div>
                        <div class="hud-bar dark" style="--fill: <?= $staminaPercent ?>%;">
                            <div class="hud-fill"></div>
                            <div class="hud-meta"><span>STAMINA</span><strong><?= (int) $hero['stamina'] ?>/<?= (int) $hero['max_stamina'] ?></strong></div>
                        </div>
                    </div>
                    <div class="status-meta">
                        <div class="meta-chip"><span>LEVEL</span><strong><?= (int) $hero['level'] ?></strong></div>
                        <div class="meta-chip"><span>GOLD</span><strong><?= (int) $hero['gold'] ?></strong></div>
                        <div class="meta-chip"><span>BAG</span><strong><?= $inventoryCount ?>/<?= (int) ($bagCapacity ?? gameBaseBagCapacity()) ?></strong></div>
                    </div>
                </div>

                <div class="stats">
                    <div class="stat"><span>Attack</span><strong><?= (int) $heroStats['attack'] ?></strong></div>
                    <div class="stat"><span>Defense</span><strong><?= (int) $heroStats['defense'] ?></strong></div>
                    <div class="stat"><span>Magic</span><strong><?= (int) $heroStats['magic'] ?></strong></div>
                    <div class="stat"><span>Speed</span><strong><?= (int) $heroStats['speed'] ?></strong></div>
                </div>

                <div class="panel">
                    <h3>Equipment</h3>
                    <div class="two-col">
                        <div class="item-card">
                            <strong>Weapon</strong>
                            <small><?= $weaponInfo ? htmlspecialchars($weaponInfo['name']) : 'None equipped' ?></small>
                        </div>
                        <div class="item-card">
                            <strong>Armor</strong>
                            <small><?= $armorInfo ? htmlspecialchars($armorInfo['name']) : 'None equipped' ?></small>
                        </div>
                    </div>
                </div>

                <div class="btn-row">
                    <form method="post">
                        <input type="hidden" name="action" value="hunt">
                        <button type="submit">Hunt</button>
                    </form>
                    <form method="post">
                        <input type="hidden" name="action" value="rest">
                        <button type="submit" class="secondary">Rest</button>
                    </form>
                    <form method="post">
                        <input type="hidden" name="action" value="sell">
                        <button type="submit" class="ghost">Sell</button>
                    </form>
                    <form method="post">
                        <input type="hidden" name="action" value="reset">
                        <button type="submit" class="ghost">Restart</button>
                    </form>
                </div>
            </div>

            <div class="card scene-card">
                <h2>Field Viewer</h2>
                <div class="scene-tabs" role="tablist" aria-label="Scene views">
                    <button type="button" class="scene-tab <?= ($activeSceneTab ?? 'scene') === 'scene' ? 'active' : '' ?>" data-scene-tab="scene">Scenario</button>
                    <button type="button" class="scene-tab <?= ($activeSceneTab ?? 'scene') === 'monster' ? 'active' : '' ?>" data-scene-tab="monster">Monster</button>
                    <button type="button" class="scene-tab <?= ($activeSceneTab ?? 'scene') === 'map' ? 'active' : '' ?>" data-scene-tab="map">Map</button>
                </div>
                <div class="scene-viewport">
                    <div class="scene-layer <?= ($activeSceneTab ?? 'scene') === 'scene' ? 'active' : '' ?>" data-scene-panel="scene">
                        <div>
                            <h3 class="scene-title"><?= htmlspecialchars($sceneLabel) ?></h3>
                            <p class="scene-subtitle">A compact view of where the run is currently unfolding.</p>
                        </div>
                    </div>
                    <div class="scene-layer <?= ($activeSceneTab ?? 'scene') === 'monster' ? 'active' : '' ?>" data-scene-panel="monster">
                        <div>
                            <h3 class="scene-title"><?= htmlspecialchars($currentMonster ?? 'No active monster') ?></h3>
                            <p class="scene-subtitle">Last notable encounter detected from your adventure log.</p>
                            <?php if ($currentMonsterData !== null): ?>
                                <div class="monster-portrait">
                                    <img src="<?= htmlspecialchars((string) ($currentMonsterData['image'] ?? '')) ?>" alt="<?= htmlspecialchars((string) ($currentMonsterData['name'] ?? 'Monster')) ?>" />
                                </div>
                                <div class="monster-hud">
                                    <div class="hud-bar dark monster-bar" style="--fill: <?= $monsterHpPercent ?>%;">
                                        <div class="hud-fill monster-fill"></div>
                                        <div class="hud-meta"><span>ENEMY HP</span><strong><?= $monsterHpCurrent ?>/<?= $monsterHpMax ?></strong></div>
                                    </div>
                                    <div class="monster-stats">
                                        <span><strong>Attack</strong> <?= (int) ($currentMonsterData['attack'] ?? 0) ?></span>
                                        <span><strong>Defense</strong> <?= (int) ($currentMonsterData['defense'] ?? 0) ?></span>
                                        <span><strong>Speed</strong> <?= (int) ($currentMonsterData['speed'] ?? 0) ?></span>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="monster-portrait monster-portrait-empty">
                                    <span>No monster image available yet.</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="scene-layer <?= ($activeSceneTab ?? 'scene') === 'map' ? 'active' : '' ?>" data-scene-panel="map">
                        <div>
                            <h3 class="scene-title">Route Grid</h3>
                            <div class="pixel-map" aria-hidden="true">
                                <span></span><span></span><span class="path"></span><span class="path"></span><span class="path"></span><span></span><span></span><span></span>
                                <span></span><span></span><span class="path"></span><span></span><span class="path"></span><span></span><span></span><span></span>
                                <span class="path"></span><span class="path"></span><span class="path"></span><span></span><span class="path"></span><span class="path"></span><span></span><span></span>
                                <span class="path"></span><span></span><span></span><span></span><span></span><span class="path"></span><span class="path"></span><span></span>
                                <span class="path"></span><span class="path"></span><span class="path player"></span><span></span><span></span><span></span><span class="path"></span><span></span>
                                <span></span><span></span><span class="path"></span><span class="path"></span><span class="path"></span><span></span><span class="path"></span><span class="path"></span>
                            </div>
                            <p class="scene-subtitle">Player marker shows the current position in the hunting route.</p>
                        </div>
                    </div>
                </div>
                <div class="log">
                    <?php foreach ($hero['log'] as $entry): ?>
                        <div class="log-entry"><?= htmlspecialchars($entry) ?></div>
                    <?php endforeach; ?>
                </div>
            </div>

            <aside class="card panel split-body">
                <h2>Market and Bag</h2>
                <?php $rightColumnDefaultTab = !empty($isInTown) ? 'market' : 'inventory'; ?>
                <div class="tabs" role="tablist" aria-label="Right column views">
                    <?php if (!empty($isInTown)): ?>
                        <button type="button" class="tab-btn <?= $rightColumnDefaultTab === 'market' ? 'active' : '' ?>" data-tab="market">Market</button>
                    <?php endif; ?>
                    <button type="button" class="tab-btn <?= $rightColumnDefaultTab === 'inventory' ? 'active' : '' ?>" data-tab="inventory">Inventory</button>
                </div>

                <?php if (!empty($isInTown)): ?>
                <div class="tab-panel <?= $rightColumnDefaultTab === 'market' ? 'active' : '' ?>" data-panel="market">
                    <div class="shop">
                        <h3>Utility</h3>
                        <div class="shop-grid">
                            <article class="item-card">
                                <strong>Bag Upgrade</strong>
                                <small>Current capacity: <?= (int) ($bagCapacity ?? gameBaseBagCapacity()) ?> slots</small>
                                <small>Upgrade: +<?= gameBagUpgradeStep() ?> slots</small>
                                <small>Price: <?= (int) ($bagUpgradeCost ?? gameGetBagUpgradeCost($hero)) ?> gold</small>
                                <form method="post">
                                    <input type="hidden" name="action" value="expand_bag">
                                    <button type="submit">Expand Bag</button>
                                </form>
                            </article>
                        </div>

                        <h3>Weapons</h3>
                        <div class="shop-grid">
                        <?php foreach ($equipmentCatalog['weapon'] as $id => $item): ?>
                            <article class="item-card">
                                <strong><?= htmlspecialchars($item['name']) ?></strong>
                                <small>Price: <?= (int) $item['price'] ?> gold</small>
                                <small>Bonus: <?= htmlspecialchars(renderItemStats($item['stats'])) ?></small>
                                <form method="post">
                                    <input type="hidden" name="action" value="buy">
                                    <input type="hidden" name="item_id" value="<?= htmlspecialchars($id) ?>">
                                    <button type="submit">Buy</button>
                                </form>
                            </article>
                        <?php endforeach; ?>
                        </div>

                        <h3>Armor</h3>
                        <div class="shop-grid">
                        <?php foreach ($equipmentCatalog['armor'] as $id => $item): ?>
                            <article class="item-card">
                                <strong><?= htmlspecialchars($item['name']) ?></strong>
                                <small>Price: <?= (int) $item['price'] ?> gold</small>
                                <small>Bonus: <?= htmlspecialchars(renderItemStats($item['stats'])) ?></small>
                                <form method="post">
                                    <input type="hidden" name="action" value="buy">
                                    <input type="hidden" name="item_id" value="<?= htmlspecialchars($id) ?>">
                                    <button type="submit">Buy</button>
                                </form>
                            </article>
                        <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="tab-panel <?= $rightColumnDefaultTab === 'inventory' ? 'active' : '' ?>" data-panel="inventory">
                    <div class="inventory">
                        <?php if ($inventoryCount === 0): ?>
                            <div class="item-card">No loot yet. Go hunt monsters outside town.</div>
                        <?php else: ?>
                            <table class="inventory-table">
                                <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Type</th>
                                    <th>Sell value</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($hero['inventory'] as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars((string) ($item['name'] ?? 'Item')) ?></td>
                                        <td><?= htmlspecialchars((string) ($item['type'] ?? 'unknown')) ?></td>
                                        <td><?= (int) ($item['value'] ?? 0) ?> gold</td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </aside>
        </section>
    <?php endif; ?>

    <p class="footer-note">Prototype baseline: session-based PHP, no database, separated by domain files for classes, equipment, monsters, and the game engine.</p>
</div>
<script src="Assets/js/app.js?v=<?= htmlspecialchars($jsVersion) ?>"></script>
</body>
</html>
