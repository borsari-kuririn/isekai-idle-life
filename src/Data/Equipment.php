<?php
declare(strict_types=1);

function gameEquipmentCatalog(): array
{
    return [
        'weapon' => [
            'rusty_sword' => [
                'name' => 'Rusty Sword',
                'slot' => 'weapon',
                'tier' => 'starter',
                'price' => 35,
                'unlock_level' => 1,
                'stats' => ['attack' => 2, 'speed' => 1],
                'upgrade_step' => ['attack' => 1],
            ],
            'hunter_blade' => [
                'name' => 'Hunter Blade',
                'slot' => 'weapon',
                'tier' => 'adventurer',
                'price' => 140,
                'unlock_level' => 4,
                'material_cost' => ['wild_essence' => 1],
                'stats' => ['attack' => 4, 'speed' => 1],
                'upgrade_step' => ['attack' => 1, 'speed' => 1],
            ],
            'mage_staff' => [
                'name' => 'Mage Staff',
                'slot' => 'weapon',
                'tier' => 'adventurer',
                'price' => 160,
                'unlock_level' => 4,
                'material_cost' => ['ruin_fragment' => 1],
                'stats' => ['magic' => 4],
                'upgrade_step' => ['magic' => 1],
            ],
            'iron_gauntlets' => [
                'name' => 'Iron Gauntlets',
                'slot' => 'weapon',
                'tier' => 'veteran',
                'price' => 330,
                'unlock_level' => 8,
                'material_cost' => ['wild_essence' => 2, 'ruin_fragment' => 1],
                'stats' => ['attack' => 5, 'defense' => 1],
                'upgrade_step' => ['attack' => 1, 'defense' => 1],
            ],
        ],
        'armor' => [
            'cloth_robe' => [
                'name' => 'Cloth Robe',
                'slot' => 'armor',
                'tier' => 'starter',
                'price' => 30,
                'unlock_level' => 1,
                'stats' => ['magic' => 1, 'defense' => 1],
                'upgrade_step' => ['magic' => 1],
            ],
            'leather_coat' => [
                'name' => 'Leather Coat',
                'slot' => 'armor',
                'tier' => 'adventurer',
                'price' => 120,
                'unlock_level' => 4,
                'material_cost' => ['wild_essence' => 1],
                'stats' => ['defense' => 3, 'speed' => 1],
                'upgrade_step' => ['defense' => 1],
            ],
            'iron_mail' => [
                'name' => 'Iron Mail',
                'slot' => 'armor',
                'tier' => 'veteran',
                'price' => 340,
                'unlock_level' => 8,
                'material_cost' => ['ruin_fragment' => 2],
                'stats' => ['defense' => 5],
                'upgrade_step' => ['defense' => 1],
            ],
            'priest_vestment' => [
                'name' => 'Priest Vestment',
                'slot' => 'armor',
                'tier' => 'adventurer',
                'price' => 180,
                'unlock_level' => 4,
                'material_cost' => ['ruin_fragment' => 1],
                'stats' => ['magic' => 2, 'defense' => 2],
                'upgrade_step' => ['magic' => 1],
            ],
        ],
    ];
}
