<?php
declare(strict_types=1);

function gameEquipmentCatalog(): array
{
    return [
        'weapon' => [
            'rusty_sword' => ['name' => 'Rusty Sword', 'slot' => 'weapon', 'price' => 8, 'stats' => ['attack' => 2, 'speed' => 1]],
            'hunter_blade' => ['name' => 'Hunter Blade', 'slot' => 'weapon', 'price' => 22, 'stats' => ['attack' => 4, 'speed' => 1]],
            'mage_staff' => ['name' => 'Mage Staff', 'slot' => 'weapon', 'price' => 24, 'stats' => ['magic' => 4]],
            'iron_gauntlets' => ['name' => 'Iron Gauntlets', 'slot' => 'weapon', 'price' => 18, 'stats' => ['attack' => 3, 'defense' => 1]],
        ],
        'armor' => [
            'cloth_robe' => ['name' => 'Cloth Robe', 'slot' => 'armor', 'price' => 7, 'stats' => ['magic' => 1, 'defense' => 1]],
            'leather_coat' => ['name' => 'Leather Coat', 'slot' => 'armor', 'price' => 16, 'stats' => ['defense' => 3, 'speed' => 1]],
            'iron_mail' => ['name' => 'Iron Mail', 'slot' => 'armor', 'price' => 28, 'stats' => ['defense' => 5]],
            'priest_vestment' => ['name' => 'Priest Vestment', 'slot' => 'armor', 'price' => 25, 'stats' => ['magic' => 2, 'defense' => 2]],
        ],
    ];
}
