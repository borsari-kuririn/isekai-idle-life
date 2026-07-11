<?php
declare(strict_types=1);

function gameClassDefinitions(): array
{
    return [
        'fencer' => [
            'name' => 'Fencer',
            'description' => 'A fast duelist focused on precise strikes and quick escapes.',
            'stats' => ['attack' => 4, 'defense' => 2, 'magic' => 1, 'speed' => 4],
        ],
        'brawler' => [
            'name' => 'Brawler',
            'description' => 'A close-range fighter with solid defense and raw strength.',
            'stats' => ['attack' => 5, 'defense' => 4, 'magic' => 0, 'speed' => 2],
        ],
        'scholar' => [
            'name' => 'Scholar',
            'description' => 'A learned traveler from another world who wins with magic and knowledge.',
            'stats' => ['attack' => 1, 'defense' => 1, 'magic' => 6, 'speed' => 2],
        ],
        'priest' => [
            'name' => 'Priest',
            'description' => 'A sacred support class with strong magic and reliable survival.',
            'stats' => ['attack' => 1, 'defense' => 3, 'magic' => 4, 'speed' => 3],
        ],
    ];
}

function gameNormalizeClassKey(string $classKey): string
{
    if ($classKey === 'schollar') {
        return 'scholar';
    }

    return $classKey;
}
