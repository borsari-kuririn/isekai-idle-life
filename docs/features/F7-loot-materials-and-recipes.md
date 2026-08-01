# Feature F7: Loot Materials and Recipes

Priority: P2

## Implementation Goal

Replace sell-everything dominance with recipe and material decisions.

## Dependencies

- Works standalone but compounds value with F4 item progression.

## Player Problem

All loot differs only by name and random sell value, so Sell All is always correct.

## Data Contract

Loot catalog entries use stable IDs with fields for route source, rarity, sell value, and recipe tags.

## State Contract

- Inventory entries store stable loot IDs.
- Optional compact recipe progress markers may be added later.

## Action Contract

### sell

- Quick action to sell only common loot.

### sell_item

- Sells selected item id and quantity.

### craft_item

- Atomic validate-then-consume behavior for materials and gold.

## Validation Rules

- Loot values are catalog-defined and not rerolled after acquisition.
- Crafting validates all ingredients before consuming any state.
- Failed crafting consumes nothing.
- Inventory mutations are atomic and deterministic.
- Bag capacity checks include crafted and acquired items.

## Acceptance Criteria

- Loot values are catalog-defined, not rerolled when acquired.
- Crafting validates all ingredients before consuming any state.
- Failed crafting is atomic and consumes nothing.
- Inventory remains within bag capacity and cookie budget.

## Definition of Done

- Crafting path and selective sell path both pass matrix tests.
