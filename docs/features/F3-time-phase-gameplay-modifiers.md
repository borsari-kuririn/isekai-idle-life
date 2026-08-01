# Feature F3: Time-Phase Gameplay Modifiers

Priority: P1

## Implementation Goal

Convert Morning, Day, Afternoon, and Night into strategic windows that change action value.

## Dependencies

- Works best after F1 because pending rewards can be phase-sensitive.

## Player Problem

Time phases are visually distinct but do not change player strategy.

## Modifier Contract

- Morning: XP multiplier 1.10
- Day: Shop discount 10%
- Afternoon: Sell value multiplier 1.15
- Night: Monster power multiplier 1.15 and pending gold multiplier 1.25

## State Contract

- Uses existing day and day_quarter values.
- No additional persisted branch required in first release.

## Calculation Rule

$$
value' = \lfloor value \times multiplier \rfloor
$$

## Technical Contract

- Modifier definitions live in static world rules data.
- Modifiers are computed server-side.
- UI only reflects already-computed values from the server.

## Validation Rules

- Each phase applies exactly one documented modifier set.
- Rest-driven phase changes stay synchronized with HTMX shell metadata.
- Phase transitions do not retroactively change pending rewards.

## Acceptance Criteria

- Each phase applies exactly one documented modifier set.
- HTMX Rest keeps visible time, shell phase metadata, and modifier text synchronized.
- Night difficulty modifies the same combat-power calculation used by Threat.
- Phase transitions do not retroactively change pending rewards.

## Definition of Done

- Each phase has visible effect and verified arithmetic outputs.
