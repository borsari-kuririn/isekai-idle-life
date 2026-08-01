# Feature F6: Contract Board

Priority: P2

## Implementation Goal

Provide medium-term objectives after early gearing.

## Dependencies

- Benefits from F2 route variety and F3 phase modifiers.

## Player Problem

After equipment is acquired, the player lacks medium-term objectives.

## Contract Types

- Defeat a specified monster family.
- Return from an expedition at Threat 3 or higher.
- Sell a specified route material quantity.
- Win an Elite encounter during a specified phase.

## Rules

- Generate three contracts per in-game day.
- The player may activate one contract at a time.
- Contract generation is deterministic from day, class, and contract slot.
- Rewards should prefer materials, shop unlocks, and mastery over unrestricted gold.
- Expired contracts remain claimable if completed before day rollover, then clear after claim.

## State Contract

- contracts.day: int
- contracts.active_id: ?string
- contracts.items: array<{ id, progress, target, complete }>

## Validation Rules

- Contract completion and claim are idempotent.
- Repeated reloads cannot reroll contracts for the same day.

## Acceptance Criteria

- Repeated reloads keep identical contract set for a given day.
- A completed contract can be claimed once.
- Progress updates only from valid gameplay events.

## Definition of Done

- Deterministic generation and claim idempotency are validated in playtests.
