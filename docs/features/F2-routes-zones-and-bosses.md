# Feature F2: Routes, Zones, and Bosses

Priority: P1

## Implementation Goal

Turn the Map from decorative into gameplay selection with route-specific encounter pools and progression rewards.

## Dependencies

- Requires F1 route-aware expedition state.

## Player Problem

The Map is decorative and all monsters come from one random pool. Players cannot choose difficulty, target loot, or pursue a destination.

## Data Model

Create location definitions with stable IDs, unlock levels, route monster pools, elite, boss, and route material identifiers.

## State Contract

- route_id: string
- route_progress: map<route_id, { elite_wins: int, boss_defeated: bool }>

## Action Contract

### select_route

- Preconditions: in town, route unlocked.
- Postconditions: route id updated only.

### hunt

- Behavior extension: selects monster from current route pool only.

## Rules

- Route unlocks at levels 1, 4, and 8 for the first balancing pass.
- Each route owns 2-3 common monsters, one Elite, and one boss.
- Boss appears after three Elite victories on that route.
- First boss victory grants a permanent route milestone, not raw stat inflation.

## Validation Rules

- Crafted request with locked route id must fail safely.
- Boss rewards must be one-time idempotent grants.

## Acceptance Criteria

- Locked routes cannot be selected through crafted requests.
- Route selection changes only the eligible encounter pool.
- Boss progress persists across reload and cookie fallback.
- Defeating a boss unlocks its reward once.

## Definition of Done

- Route pool isolation validated in playtests.
- Boss progress survives reload and cookie fallback.
