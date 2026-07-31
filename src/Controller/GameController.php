<?php
declare(strict_types=1);

final class GameController
{
    public function __construct(
        private GameDataProvider $dataProvider,
        private HeroViewModelBuilder $viewModelBuilder,
    ) {
    }

    public function handle(array $get, array $post): array
    {
        $classDefinitions = $this->dataProvider->classes();
        $equipmentCatalog = $this->dataProvider->equipment();
        $monsterPool = $this->dataProvider->monsters();

        $action = $post['action'] ?? $get['action'] ?? null;
        $isHtmxRequest = (!empty($_SERVER['HTTP_HX_REQUEST']) && $_SERVER['HTTP_HX_REQUEST'] === 'true')
            || (!empty($post['_hx_request']) && $post['_hx_request'] === 'true')
            || (!empty($get['_hx_request']) && $get['_hx_request'] === 'true');

        if (in_array($action, ['set_scene_tab', 'set_right_tab'], true)) {
            $uiState = $_SESSION['ui'] ?? [];
            if ($action === 'set_scene_tab') {
                $uiState['scene_tab'] = $post['scene_tab'] ?? $get['scene_tab'] ?? 'scene';
            }
            if ($action === 'set_right_tab') {
                $uiState['right_tab'] = $post['right_tab'] ?? $get['right_tab'] ?? 'inventory';
            }
            $_SESSION['ui'] = $uiState;
        } else {
            gameHandleAction($action, array_merge($get, $post), $classDefinitions, $equipmentCatalog, $monsterPool);
        }

        gameEnsureHero();
        $hero = $_SESSION['hero'];
        $hero['class'] = gameNormalizeClassKey((string) ($hero['class'] ?? 'fencer'));
        if (!isset($classDefinitions[$hero['class']])) {
            $hero['class'] = 'fencer';
        }

        $_SESSION['hero']['class'] = $hero['class'];
        gameEnsureStaminaState($hero);
        gameEnsureTimeState($hero);
        gameEnsureBagState($hero);
        gameEnsureExpeditionState($hero);
        $_SESSION['hero'] = $hero;

        // Session is the primary state storage; cookie keeps a fallback copy
        // for hosts where PHP session storage is unstable between requests.
        gamePersistHeroToCookie($_SESSION['hero']);

        $heroStats = gameCalculateHeroStats($hero, $classDefinitions, $equipmentCatalog);
        $classInfo = $classDefinitions[$hero['class']] ?? $classDefinitions['fencer'];
        $heroTitle = $this->resolveHeroTitle($hero, $classInfo);
        $weaponInfo = $hero['equipped']['weapon'] ? gameFindEquipment($hero['equipped']['weapon'], $equipmentCatalog) : null;
        $armorInfo = $hero['equipped']['armor'] ? gameFindEquipment($hero['equipped']['armor'], $equipmentCatalog) : null;
        $inventoryCount = count($hero['inventory']);
        $bagCapacity = (int) ($hero['bag_capacity'] ?? gameBaseBagCapacity());
        $bagUpgradeCost = gameGetBagUpgradeCost($hero);
        $expedition = $hero['expedition'] ?? [];
        $routeDefinitions = gameRouteDefinitions();
        $selectedRouteId = (string) ($expedition['route_id'] ?? 'meadow');
        $selectedRoute = $routeDefinitions[$selectedRouteId] ?? ($routeDefinitions['meadow'] ?? ['name' => 'Unknown Route']);
        $isExpeditionActive = !empty($expedition['active']);

        $battleView = $this->viewModelBuilder->getCurrentBattleMonster($hero, $monsterPool);
        $currentMonster = $battleView['monster']['name'] ?? null;
        $currentMonsterData = $battleView['monster'] ?? null;

        $hpPercent = (int) max(0, min(100, (($hero['max_hp'] > 0 ? $hero['hp'] / $hero['max_hp'] : 0) * 100)));
        $xpToNext = max(1, (int) $hero['level'] * 12);
        $xpPercent = (int) max(0, min(100, (($hero['exp'] / $xpToNext) * 100)));
        $staminaPercent = (int) max(0, min(100, (($hero['max_stamina'] > 0 ? $hero['stamina'] / $hero['max_stamina'] : 0) * 100)));
        $worldTimeLabel = gameGetWorldTimeLabel($hero);

        $sceneLabels = [
            'crossroad' => 'Crossroad Fields',
            'hunting' => 'Hunting Grounds',
            'expedition' => 'Expedition Route',
            'town' => 'Town District',
        ];
        $location = (string) ($hero['location'] ?? '');
        $sceneLabel = $sceneLabels[$location] ?? $this->viewModelBuilder->detectSceneLabel($hero['log']);
        $isInTown = $location === 'town' || str_starts_with($sceneLabel, 'Town');
        $uiState = $_SESSION['ui'] ?? [];
        $activeSceneTab = $uiState['scene_tab'] ?? ($action === 'hunt' ? 'monster' : 'scene');
        $activeRightTab = $uiState['right_tab'] ?? ($isInTown ? 'market' : 'inventory');

        $monsterHpMax = (int) ($battleView['max_hp'] ?? ($currentMonsterData['hp'] ?? 0));
        $monsterHpCurrent = (int) ($battleView['current_hp'] ?? $monsterHpMax);
        $monsterHpPercent = $monsterHpMax > 0
            ? (int) max(0, min(100, ($monsterHpCurrent / $monsterHpMax) * 100))
            : 0;

        return [
            'classDefinitions' => $classDefinitions,
            'equipmentCatalog' => $equipmentCatalog,
            'monsterPool' => $monsterPool,
            'hero' => $hero,
            'heroStats' => $heroStats,
            'classInfo' => $classInfo,
            'heroTitle' => $heroTitle,
            'weaponInfo' => $weaponInfo,
            'armorInfo' => $armorInfo,
            'inventoryCount' => $inventoryCount,
            'bagCapacity' => $bagCapacity,
            'bagUpgradeCost' => $bagUpgradeCost,
            'expedition' => $expedition,
            'routeDefinitions' => $routeDefinitions,
            'selectedRoute' => $selectedRoute,
            'selectedRouteId' => $selectedRouteId,
            'isExpeditionActive' => $isExpeditionActive,
            'sceneLabel' => $sceneLabel,
            'isInTown' => $isInTown,
            'currentMonster' => $currentMonster,
            'currentMonsterData' => $currentMonsterData,
            'hpPercent' => $hpPercent,
            'xpToNext' => $xpToNext,
            'xpPercent' => $xpPercent,
            'staminaPercent' => $staminaPercent,
            'worldTimeLabel' => $worldTimeLabel,
            'monsterHpMax' => $monsterHpMax,
            'monsterHpCurrent' => $monsterHpCurrent,
            'monsterHpPercent' => $monsterHpPercent,
            'activeSceneTab' => $activeSceneTab,
            'activeRightTab' => $activeRightTab,
            'isHtmxRequest' => $isHtmxRequest,
        ];
    }

    private function resolveHeroTitle(array $hero, array $classInfo): string
    {
        $className = (string) ($classInfo['name'] ?? 'Adventurer');
        $level = (int) ($hero['level'] ?? 1);

        if ($level >= 12) {
            return 'Legendary ' . $className;
        }

        if ($level >= 8) {
            return 'Veteran ' . $className;
        }

        if ($level >= 4) {
            return 'Rising ' . $className;
        }

        return 'Novice ' . $className;
    }
}
