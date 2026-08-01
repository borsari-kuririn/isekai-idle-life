# Feature F4: Economy and Equipment Progression

Priority: P1

## Implementation Goal

Delay equipment saturation and create durable sinks through ownership, unlocks, and upgrades.

## Dependencies

- Can be implemented alongside F1, independent from F2 and F3.

## Player Problem

Equipment is saturated too early and repeat purchases of the same item remain possible.

## Rules

- Add equipment tiers: starter, adventurer, veteran.
- Suggested target prices:
  - Starter: 30-50 gold.
  - Adventurer: 120-180 gold.
  - Veteran: 300-450 gold plus one route material.
- Add unlock_level and optional material_cost to items.
- Track ownership separately from equipped slots.
- Block duplicate purchases unless explicitly consumed by an upgrade mechanic.
- Add upgrade levels from +0 to +3.

## State Contract

- owned_equipment: string[]
- equipment_upgrades: map<item_id, level 0..3>
- equipped: { weapon: ?string, armor: ?string }

## Action Contract

### buy

- Preconditions: unlock level met, materials available, not already owned.
- Postconditions: spend once, add ownership, optional auto-equip when slot is empty.

### equip

- Preconditions: item owned.
- Postconditions: slot assignment only.

### upgrade_equipment

- Preconditions: item owned, level < 3, gold available.
- Postconditions: consume gold, increase level by one.

## Upgrade Formula

$$
upgrade\_cost(level) = base\_price \times (level + 1)
$$

## Validation Rules

- Duplicate Buy attempts fail with no stamina/gold spend.
- Locked items cannot be purchased through crafted requests.
- Equip does not re-charge purchase price.
- Upgrade level is clamped to 3.

## Acceptance Criteria

- Duplicate Buy attempts fail without spending gold or stamina.
- Locked items cannot be purchased through crafted requests.
- Equip does not charge the item purchase price again.
- Upgrade costs and stat changes are deterministic and visible before confirmation.

## Definition of Done

- Unlock and duplicate-buy protections pass crafted-request tests.
