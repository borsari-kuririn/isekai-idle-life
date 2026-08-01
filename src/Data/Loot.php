<?php
declare(strict_types=1);

function gameLootCatalog(): array
{
    return [
        'slime_gel' => [
            'name' => 'Slime Gel',
            'type' => 'common',
            'sell_value' => 4,
            'tags' => ['meadow', 'craft'],
        ],
        'forest_pebble' => [
            'name' => 'Forest Pebble',
            'type' => 'common',
            'sell_value' => 3,
            'tags' => ['meadow', 'craft'],
        ],
        'rat_tail' => [
            'name' => 'Rat Tail',
            'type' => 'common',
            'sell_value' => 5,
            'tags' => ['meadow', 'craft'],
        ],
        'gnawed_fang' => [
            'name' => 'Gnawed Fang',
            'type' => 'common',
            'sell_value' => 6,
            'tags' => ['meadow', 'craft'],
        ],
        'goblin_tooth' => [
            'name' => 'Goblin Tooth',
            'type' => 'common',
            'sell_value' => 7,
            'tags' => ['ruins', 'craft'],
        ],
        'broken_dagger' => [
            'name' => 'Broken Dagger',
            'type' => 'common',
            'sell_value' => 8,
            'tags' => ['ruins', 'craft'],
        ],
        'bone_dust' => [
            'name' => 'Bone Dust',
            'type' => 'common',
            'sell_value' => 8,
            'tags' => ['ruins', 'craft'],
        ],
        'ancient_coin' => [
            'name' => 'Ancient Coin',
            'type' => 'common',
            'sell_value' => 10,
            'tags' => ['ruins', 'craft'],
        ],
        'wild_essence' => [
            'name' => 'Wild Essence',
            'type' => 'material',
            'sell_value' => 12,
            'tags' => ['meadow', 'upgrade'],
        ],
        'ruin_fragment' => [
            'name' => 'Ruin Fragment',
            'type' => 'material',
            'sell_value' => 14,
            'tags' => ['ruins', 'upgrade'],
        ],
    ];
}

function gameCraftingRecipes(): array
{
    return [
        'forge_hunter_blade' => [
            'name' => 'Forge Hunter Blade',
            'gold_cost' => 30,
            'ingredients' => [
                'goblin_tooth' => 2,
                'broken_dagger' => 1,
                'wild_essence' => 1,
            ],
            'reward' => [
                'type' => 'equipment',
                'item_id' => 'hunter_blade',
            ],
        ],
        'forge_iron_mail' => [
            'name' => 'Forge Iron Mail',
            'gold_cost' => 40,
            'ingredients' => [
                'bone_dust' => 2,
                'ancient_coin' => 1,
                'ruin_fragment' => 1,
            ],
            'reward' => [
                'type' => 'equipment',
                'item_id' => 'iron_mail',
            ],
        ],
        'town_ration' => [
            'name' => 'Craft Town Ration',
            'gold_cost' => 12,
            'ingredients' => [
                'slime_gel' => 2,
                'forest_pebble' => 1,
            ],
            'reward' => [
                'type' => 'recovery',
                'stamina_restore' => 15,
            ],
        ],
    ];
}
