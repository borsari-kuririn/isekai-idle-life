<?php
declare(strict_types=1);

function gameMonsterPool(): array
{
    return [
        ['name' => 'Slime', 'image' => 'Assets/Monsters/slime.png', 'attack' => 2, 'defense' => 1, 'magic' => 0, 'speed' => 1, 'hp' => 10, 'gold' => [2, 5], 'loot' => ['Slime Gel', 'Forest Pebble'], 'xp' => [4, 7]],
        ['name' => 'Goblin', 'image' => 'Assets/Monsters/goblin.png', 'attack' => 4, 'defense' => 2, 'magic' => 0, 'speed' => 2, 'hp' => 14, 'gold' => [4, 8], 'loot' => ['Goblin Tooth', 'Broken Dagger'], 'xp' => [6, 10]],
        ['name' => 'Giant Rat', 'image' => 'Assets/Monsters/giant_rat.png', 'attack' => 3, 'defense' => 1, 'magic' => 0, 'speed' => 3, 'hp' => 12, 'gold' => [3, 6], 'loot' => ['Rat Tail', 'Gnawed Fang'], 'xp' => [5, 8]],
        ['name' => 'Skeleton', 'image' => 'Assets/Monsters/skeleton.png', 'attack' => 6, 'defense' => 4, 'magic' => 1, 'speed' => 1, 'hp' => 20, 'gold' => [6, 12], 'loot' => ['Bone Dust', 'Ancient Coin'], 'xp' => [8, 13]],
    ];
}
