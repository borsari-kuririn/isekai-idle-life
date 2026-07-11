<?php
declare(strict_types=1);

function gameMonsterPool(): array
{
    return [
        ['name' => 'Slime', 'attack' => 2, 'defense' => 1, 'magic' => 0, 'speed' => 1, 'hp' => 10, 'gold' => [2, 5], 'loot' => ['Slime Gel', 'Forest Pebble'], 'xp' => [4, 7]],
        ['name' => 'Goblin', 'attack' => 4, 'defense' => 2, 'magic' => 0, 'speed' => 2, 'hp' => 14, 'gold' => [4, 8], 'loot' => ['Goblin Tooth', 'Broken Dagger'], 'xp' => [6, 10]],
        ['name' => 'Wolf', 'attack' => 5, 'defense' => 2, 'magic' => 0, 'speed' => 4, 'hp' => 16, 'gold' => [5, 10], 'loot' => ['Wolf Pelt', 'Sharp Fang'], 'xp' => [7, 11]],
        ['name' => 'Skeleton', 'attack' => 6, 'defense' => 4, 'magic' => 1, 'speed' => 1, 'hp' => 20, 'gold' => [6, 12], 'loot' => ['Bone Dust', 'Ancient Coin'], 'xp' => [8, 13]],
    ];
}
