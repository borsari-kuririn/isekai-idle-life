<?php
declare(strict_types=1);

final class GameDataProvider
{
    public function classes(): array
    {
        return gameClassDefinitions();
    }

    public function equipment(): array
    {
        return gameEquipmentCatalog();
    }

    public function monsters(): array
    {
        return gameMonsterPool();
    }

    public function lootCatalog(): array
    {
        return gameLootCatalog();
    }

    public function craftRecipes(): array
    {
        return gameCraftingRecipes();
    }
}
