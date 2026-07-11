<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/src/Data/Classes.php';
require_once __DIR__ . '/src/Data/Equipment.php';
require_once __DIR__ . '/src/Data/Monsters.php';
require_once __DIR__ . '/src/Game/Engine.php';

$classDefinitions = gameClassDefinitions();
$equipmentCatalog = gameEquipmentCatalog();
$monsterPool = gameMonsterPool();

$action = $_POST['action'] ?? $_GET['action'] ?? null;
gameHandleAction($action, array_merge($_GET, $_POST), $classDefinitions, $equipmentCatalog, $monsterPool);

gameEnsureHero();
$hero = $_SESSION['hero'];
$hero['class'] = gameNormalizeClassKey((string) ($hero['class'] ?? 'fencer'));
if (!isset($classDefinitions[$hero['class']])) {
    $hero['class'] = 'fencer';
}
$heroStats = gameCalculateHeroStats($hero, $classDefinitions, $equipmentCatalog);
$classInfo = $classDefinitions[$hero['class']] ?? $classDefinitions['fencer'];
$weaponInfo = $hero['equipped']['weapon'] ? gameFindEquipment($hero['equipped']['weapon'], $equipmentCatalog) : null;
$armorInfo = $hero['equipped']['armor'] ? gameFindEquipment($hero['equipped']['armor'], $equipmentCatalog) : null;
$inventoryCount = count($hero['inventory']);

$_SESSION['hero']['class'] = $hero['class'];

function renderItemStats(array $stats): string
{
    $parts = [];

    foreach ($stats as $stat => $value) {
        $parts[] = ucfirst($stat) . ' +' . $value;
    }

    return implode(' | ', $parts);
}

function detectCurrentMonster(array $log, array $monsterPool): ?string
{
    $latest = (string) ($log[0] ?? '');
    if ($latest === '') {
        return null;
    }

    foreach ($monsterPool as $monster) {
        $name = (string) ($monster['name'] ?? '');
        if ($name !== '' && stripos($latest, $name) !== false) {
            return $name;
        }
    }

    return null;
}

function detectSceneLabel(array $log): string
{
    $latest = strtolower((string) ($log[0] ?? ''));

    if ($latest === '') {
        return 'Town Gate';
    }

    if (str_contains($latest, 'rested') || str_contains($latest, 'sold') || str_contains($latest, 'buy') || str_contains($latest, 'equipped')) {
        return 'Town District';
    }

    if (str_contains($latest, 'victory') || str_contains($latest, 'defeat') || str_contains($latest, 'escaped')) {
        return 'Hunting Grounds';
    }

    return 'Crossroad Fields';
}

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Isekai Idle Life</title>
    <style>
        :root {
            color-scheme: light;
            --bg-start: #eef7d8;
            --bg-end: #d7efbe;
            --panel: #f7ffd6;
            --panel-alt: #dcefb0;
            --panel-deep: #9fc87a;
            --text: #22311f;
            --muted: #5d7655;
            --accent: #3f6f4d;
            --accent-2: #7b9b48;
            --highlight: #f2f8a1;
            --border: #7aa15d;
            --shadow: rgba(42, 65, 31, 0.18);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            height: 100vh;
            font-family: Georgia, 'Times New Roman', serif;
            color: var(--text);
            background:
                radial-gradient(circle at top, rgba(255, 255, 255, 0.74), transparent 38%),
                linear-gradient(180deg, var(--bg-start) 0%, var(--bg-end) 100%);
            overflow: hidden;
        }

        .wrap {
            max-width: 1220px;
            margin: 0 auto;
            padding: 12px;
            height: 100%;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .hero-banner {
            border: 2px solid var(--border);
            border-radius: 16px;
            padding: 12px 14px;
            background: linear-gradient(135deg, var(--panel) 0%, var(--panel-alt) 100%);
            box-shadow: 0 8px 18px var(--shadow);
            flex: 0 0 auto;
        }

        .hero-banner h1 {
            margin: 0 0 4px;
            font-size: clamp(1.4rem, 3vw, 2rem);
            color: var(--accent);
            letter-spacing: 0.02em;
        }

        .hero-banner p {
            margin: 0;
            max-width: 96ch;
            line-height: 1.25;
            font-size: 0.9rem;
            color: var(--muted);
        }

        .grid {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 10px;
        }

        .dashboard {
            display: grid;
            grid-template-columns: 1.05fr 1.1fr 1fr;
            gap: 10px;
            flex: 1 1 auto;
            min-height: 0;
        }

        .card {
            border: 2px solid var(--border);
            border-radius: 14px;
            background: rgba(247, 255, 214, 0.94);
            box-shadow: 0 8px 18px var(--shadow);
            padding: 10px;
            min-height: 0;
        }

        .card h2,
        .card h3 {
            margin: 0;
            color: var(--accent);
        }

        .card h2 {
            font-size: 1.1rem;
        }

        .card h3 {
            font-size: 0.98rem;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }

        .stat {
            padding: 8px;
            border-radius: 10px;
            background: linear-gradient(180deg, #ffffff 0%, #eef7c7 100%);
            border: 1px solid #b5cf7d;
        }

        .stat span {
            display: block;
            margin-bottom: 2px;
            color: var(--muted);
            font-size: 0.78rem;
        }

        .stat strong {
            font-size: 1.02rem;
        }

        .badge-row {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin: 8px 0 2px;
        }

        .badge {
            padding: 4px 8px;
            border-radius: 999px;
            border: 1px solid var(--border);
            background: var(--highlight);
            font-size: 0.78rem;
        }

        .panel {
            display: grid;
            gap: 8px;
            min-height: 0;
        }

        .form-row,
        .two-col {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }

        label {
            display: grid;
            gap: 4px;
            font-size: 0.86rem;
        }

        input,
        select,
        button {
            font: inherit;
        }

        input,
        select {
            padding: 8px 10px;
            border: 1px solid var(--border);
            border-radius: 9px;
            background: #fff;
            color: var(--text);
        }

        .btn-row {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .btn-row form {
            margin: 0;
        }

        button {
            border: 0;
            border-radius: 10px;
            padding: 8px 10px;
            font-weight: 700;
            cursor: pointer;
            color: #f8ffd8;
            background: var(--accent);
            font-size: 0.82rem;
        }

        button.secondary {
            background: var(--accent-2);
        }

        button.ghost {
            background: #6c8f45;
        }

        .shop,
        .log,
        .inventory {
            display: grid;
            gap: 6px;
            min-height: 0;
            overflow: auto;
            padding-right: 2px;
        }

        .item-card {
            display: grid;
            gap: 5px;
            padding: 8px;
            border-radius: 10px;
            border: 1px solid #b5cf7d;
            background: #fff;
        }

        .item-card small,
        .footer-note {
            color: var(--muted);
            font-size: 0.78rem;
        }

        .log-entry {
            padding: 7px 8px;
            border-left: 4px solid var(--accent);
            border-radius: 0 8px 8px 0;
            background: #fff;
            font-size: 0.8rem;
        }

        .scene-card {
            display: grid;
            grid-template-rows: auto auto 1fr auto;
            gap: 8px;
            min-height: 0;
        }

        .scene-tabs {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .scene-tab {
            background: #88ab5d;
            color: #f6ffd8;
        }

        .scene-tab.active {
            background: var(--accent);
        }

        .scene-viewport {
            border: 1px solid #96b968;
            border-radius: 12px;
            min-height: 180px;
            padding: 12px;
            background:
                linear-gradient(180deg, rgba(255, 255, 255, 0.65), rgba(231, 246, 194, 0.85)),
                repeating-linear-gradient(0deg, rgba(127, 162, 88, 0.12), rgba(127, 162, 88, 0.12) 2px, transparent 2px, transparent 10px);
            position: relative;
            overflow: hidden;
        }

        .scene-layer {
            display: none;
            height: 100%;
            align-content: center;
            text-align: center;
        }

        .scene-layer.active {
            display: grid;
        }

        .scene-title {
            font-size: 1rem;
            margin-bottom: 6px;
            color: var(--accent);
        }

        .scene-subtitle {
            margin: 0;
            font-size: 0.85rem;
            color: var(--muted);
        }

        .pixel-map {
            display: grid;
            grid-template-columns: repeat(8, 1fr);
            gap: 3px;
            max-width: 220px;
            margin: 0 auto;
        }

        .pixel-map span {
            aspect-ratio: 1 / 1;
            border-radius: 2px;
            background: #b9d58d;
            border: 1px solid #9fbe76;
        }

        .pixel-map span.path {
            background: #e4efb7;
        }

        .pixel-map span.player {
            background: #3f6f4d;
        }

        .split-body {
            display: grid;
            gap: 8px;
            min-height: 0;
        }

        .tabs {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .tab-btn {
            background: #7b9b48;
            color: #f6ffd8;
        }

        .tab-btn.active {
            background: var(--accent);
        }

        .tab-panel {
            display: none;
            min-height: 0;
        }

        .tab-panel.active {
            display: grid;
        }

        .footer-note {
            margin: 0;
            font-size: 0.78rem;
            flex: 0 0 auto;
        }

        @media (max-width: 1080px) {
            body {
                overflow: auto;
                height: auto;
                min-height: 100vh;
            }

            .wrap {
                height: auto;
            }

            .dashboard {
                grid-template-columns: 1fr;
            }

            .grid,
            .form-row,
            .two-col,
            .stats {
                grid-template-columns: 1fr;
            }

            .scene-viewport {
                min-height: 150px;
            }
        }
    </style>
</head>
<body>
<div class="wrap">
    <section class="hero-banner">
        <h1>Isekai Idle Life</h1>
        <p>A static PHP RPG focused on monster farming, town selling, and simple medieval progression. No database, no animations, and a Game Boy Color inspired visual direction with a light background.</p>
    </section>

    <?php if (empty($hero['created'])): ?>
        <section class="card panel">
            <h2>Create Character</h2>
            <form method="post" class="panel">
                <input type="hidden" name="action" value="create">
                <div class="form-row">
                    <label>
                        Hero Name
                        <input type="text" name="name" maxlength="20" placeholder="Example: Ryu, Aira, Kael">
                    </label>
                    <label>
                        Starting Class
                        <select name="class">
                            <?php foreach ($classDefinitions as $id => $class): ?>
                                <option value="<?= htmlspecialchars($id) ?>"><?= htmlspecialchars($class['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>
                <div class="btn-row">
                    <button type="submit">Start Adventure</button>
                </div>
            </form>

            <div class="two-col">
                <?php foreach ($classDefinitions as $class): ?>
                    <article class="item-card">
                        <strong><?= htmlspecialchars($class['name']) ?></strong>
                        <small><?= htmlspecialchars($class['description']) ?></small>
                        <small>Attack <?= (int) $class['stats']['attack'] ?> | Defense <?= (int) $class['stats']['defense'] ?> | Magic <?= (int) $class['stats']['magic'] ?> | Speed <?= (int) $class['stats']['speed'] ?></small>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php else: ?>
        <?php
            $currentMonster = detectCurrentMonster($hero['log'], $monsterPool);
            $sceneLabel = detectSceneLabel($hero['log']);
        ?>
        <section class="dashboard">
            <div class="card panel">
                <h2><?= htmlspecialchars((string) ($hero['name'] ?? 'Adventurer')) ?>, <?= htmlspecialchars((string) ($classInfo['name'] ?? 'Fencer')) ?></h2>
                <div class="badge-row">
                    <span class="badge">Level <?= (int) $hero['level'] ?></span>
                    <span class="badge">XP <?= (int) $hero['exp'] ?></span>
                    <span class="badge">Gold <?= (int) $hero['gold'] ?></span>
                    <span class="badge">HP <?= (int) $hero['hp'] ?>/<?= (int) $hero['max_hp'] ?></span>
                    <span class="badge">Inventory <?= $inventoryCount ?></span>
                </div>
                <p><?= htmlspecialchars((string) ($classInfo['description'] ?? '')) ?></p>

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
                        <button type="submit">Farm</button>
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
                    <button type="button" class="scene-tab active" data-scene-tab="scene">Scenario</button>
                    <button type="button" class="scene-tab" data-scene-tab="monster">Monster</button>
                    <button type="button" class="scene-tab" data-scene-tab="map">Map</button>
                </div>
                <div class="scene-viewport">
                    <div class="scene-layer active" data-scene-panel="scene">
                        <div>
                            <h3 class="scene-title"><?= htmlspecialchars($sceneLabel) ?></h3>
                            <p class="scene-subtitle">A compact view of where the run is currently unfolding.</p>
                        </div>
                    </div>
                    <div class="scene-layer" data-scene-panel="monster">
                        <div>
                            <h3 class="scene-title"><?= htmlspecialchars($currentMonster ?? 'No active monster') ?></h3>
                            <p class="scene-subtitle">Last notable encounter detected from your adventure log.</p>
                        </div>
                    </div>
                    <div class="scene-layer" data-scene-panel="map">
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
                <div class="tabs" role="tablist" aria-label="Right column views">
                    <button type="button" class="tab-btn active" data-tab="market">Market</button>
                    <button type="button" class="tab-btn" data-tab="inventory">Inventory</button>
                </div>

                <div class="tab-panel active" data-panel="market">
                    <div class="shop">
                        <h3>Weapons</h3>
                        <?php foreach ($equipmentCatalog['weapon'] as $id => $item): ?>
                            <article class="item-card">
                                <strong><?= htmlspecialchars($item['name']) ?></strong>
                                <small>Price: <?= (int) $item['price'] ?> gold</small>
                                <small>Bonus: <?= htmlspecialchars(renderItemStats($item['stats'])) ?></small>
                                <form method="post">
                                    <input type="hidden" name="action" value="buy">
                                    <input type="hidden" name="item_id" value="<?= htmlspecialchars($id) ?>">
                                    <button type="submit">Buy and Equip</button>
                                </form>
                            </article>
                        <?php endforeach; ?>

                        <h3>Armor</h3>
                        <?php foreach ($equipmentCatalog['armor'] as $id => $item): ?>
                            <article class="item-card">
                                <strong><?= htmlspecialchars($item['name']) ?></strong>
                                <small>Price: <?= (int) $item['price'] ?> gold</small>
                                <small>Bonus: <?= htmlspecialchars(renderItemStats($item['stats'])) ?></small>
                                <form method="post">
                                    <input type="hidden" name="action" value="buy">
                                    <input type="hidden" name="item_id" value="<?= htmlspecialchars($id) ?>">
                                    <button type="submit">Buy and Equip</button>
                                </form>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="tab-panel" data-panel="inventory">
                    <div class="inventory">
                        <?php if ($inventoryCount === 0): ?>
                            <div class="item-card">No loot yet. Go farm monsters outside town.</div>
                        <?php else: ?>
                            <?php foreach ($hero['inventory'] as $item): ?>
                                <div class="item-card">
                                    <strong><?= htmlspecialchars((string) ($item['name'] ?? 'Item')) ?></strong>
                                    <small>Type: <?= htmlspecialchars((string) ($item['type'] ?? 'unknown')) ?></small>
                                    <small>Sell value: <?= (int) ($item['value'] ?? 0) ?> gold</small>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </aside>
        </section>
    <?php endif; ?>

    <p class="footer-note">Prototype baseline: session-based PHP, no database, separated by domain files for classes, equipment, monsters, and the game engine.</p>
</div>
<script>
    (function () {
        const tabs = document.querySelectorAll('[data-tab]');
        const panels = document.querySelectorAll('[data-panel]');
        const sceneTabs = document.querySelectorAll('[data-scene-tab]');
        const scenePanels = document.querySelectorAll('[data-scene-panel]');

        tabs.forEach((tab) => {
            tab.addEventListener('click', () => {
                const target = tab.getAttribute('data-tab');
                tabs.forEach((item) => item.classList.toggle('active', item === tab));
                panels.forEach((panel) => {
                    panel.classList.toggle('active', panel.getAttribute('data-panel') === target);
                });
            });
        });

        sceneTabs.forEach((tab) => {
            tab.addEventListener('click', () => {
                const target = tab.getAttribute('data-scene-tab');
                sceneTabs.forEach((item) => item.classList.toggle('active', item === tab));
                scenePanels.forEach((panel) => {
                    panel.classList.toggle('active', panel.getAttribute('data-scene-panel') === target);
                });
            });
        });
    })();
</script>
</body>
</html>
