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

        $heroStats = gameCalculateHeroStats($hero, $classDefinitions, $equipmentCatalog);
        $classInfo = $classDefinitions[$hero['class']] ?? $classDefinitions['fencer'];
        $weaponInfo = $hero['equipped']['weapon'] ? gameFindEquipment($hero['equipped']['weapon'], $equipmentCatalog) : null;
        $armorInfo = $hero['equipped']['armor'] ? gameFindEquipment($hero['equipped']['armor'], $equipmentCatalog) : null;
        $inventoryCount = count($hero['inventory']);

        $battleView = $this->viewModelBuilder->getCurrentBattleMonster($hero, $monsterPool);
        $currentMonster = $battleView['monster']['name'] ?? null;
        $currentMonsterData = $battleView['monster'] ?? null;

        $hpPercent = (int) max(0, min(100, (($hero['max_hp'] > 0 ? $hero['hp'] / $hero['max_hp'] : 0) * 100)));
        $xpToNext = max(1, (int) $hero['level'] * 12);
        $xpPercent = (int) max(0, min(100, (($hero['exp'] / $xpToNext) * 100)));

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
            'weaponInfo' => $weaponInfo,
            'armorInfo' => $armorInfo,
            'inventoryCount' => $inventoryCount,
            'sceneLabel' => $this->viewModelBuilder->detectSceneLabel($hero['log']),
            'currentMonster' => $currentMonster,
            'currentMonsterData' => $currentMonsterData,
            'hpPercent' => $hpPercent,
            'xpToNext' => $xpToNext,
            'xpPercent' => $xpPercent,
            'monsterHpMax' => $monsterHpMax,
            'monsterHpCurrent' => $monsterHpCurrent,
            'monsterHpPercent' => $monsterHpPercent,
            'activeSceneTab' => $action === 'hunt' ? 'monster' : 'scene',
        ];
    }
}
