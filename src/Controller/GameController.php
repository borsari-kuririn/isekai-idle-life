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
        gameHandleAction($action, array_merge($get, $post), $classDefinitions, $equipmentCatalog, $monsterPool);

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
        $_SESSION['hero']['stamina'] = $hero['stamina'];
        $_SESSION['hero']['max_stamina'] = $hero['max_stamina'];
        $_SESSION['hero']['day'] = $hero['day'];
        $_SESSION['hero']['day_quarter'] = $hero['day_quarter'];
        $_SESSION['hero']['quarter_stamina_spent'] = $hero['quarter_stamina_spent'];
        $_SESSION['hero']['bag_capacity'] = $hero['bag_capacity'];

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

        $battleView = $this->viewModelBuilder->getCurrentBattleMonster($hero, $monsterPool);
        $currentMonster = $battleView['monster']['name'] ?? null;
        $currentMonsterData = $battleView['monster'] ?? null;

        $hpPercent = (int) max(0, min(100, (($hero['max_hp'] > 0 ? $hero['hp'] / $hero['max_hp'] : 0) * 100)));
        $xpToNext = max(1, (int) $hero['level'] * 12);
        $xpPercent = (int) max(0, min(100, (($hero['exp'] / $xpToNext) * 100)));
        $staminaPercent = (int) max(0, min(100, (($hero['max_stamina'] > 0 ? $hero['stamina'] / $hero['max_stamina'] : 0) * 100)));
        $worldTimeLabel = gameGetWorldTimeLabel($hero);

        $sceneLabel = $this->viewModelBuilder->detectSceneLabel($hero['log']);
        $isInTown = str_starts_with($sceneLabel, 'Town');

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
            'activeSceneTab' => $action === 'hunt' ? 'monster' : 'scene',
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
