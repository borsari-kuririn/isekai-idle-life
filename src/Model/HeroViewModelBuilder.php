<?php
declare(strict_types=1);

final class HeroViewModelBuilder
{
    public function getCurrentBattleMonster(array $hero, array $monsterPool): ?array
    {
        $battleMonster = $hero['battle']['monster'] ?? null;
        if (is_array($battleMonster) && isset($battleMonster['name'])) {
            return [
                'monster' => $battleMonster,
                'current_hp' => (int) ($hero['battle']['current_hp'] ?? $battleMonster['hp'] ?? 0),
                'max_hp' => (int) ($hero['battle']['max_hp'] ?? $battleMonster['hp'] ?? 0),
            ];
        }

        $legacyMonsterName = $this->detectCurrentMonster($hero['log'] ?? [], $monsterPool);
        $legacyMonster = $this->findMonsterByName($monsterPool, $legacyMonsterName);
        if ($legacyMonster !== null) {
            return [
                'monster' => $legacyMonster,
                'current_hp' => (int) ($legacyMonster['hp'] ?? 0),
                'max_hp' => (int) ($legacyMonster['hp'] ?? 0),
            ];
        }

        return null;
    }

    public function detectSceneLabel(array $log): string
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

    private function detectCurrentMonster(array $log, array $monsterPool): ?string
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

        if (stripos($latest, 'wolf') !== false) {
            return 'Giant Rat';
        }

        return null;
    }

    private function findMonsterByName(array $monsterPool, ?string $monsterName): ?array
    {
        if ($monsterName === null) {
            return null;
        }

        foreach ($monsterPool as $monster) {
            if ((string) ($monster['name'] ?? '') === $monsterName) {
                return $monster;
            }
        }

        return null;
    }
}
