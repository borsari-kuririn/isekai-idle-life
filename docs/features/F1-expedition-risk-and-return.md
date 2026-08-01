# Feature F1: Expedition Risk and Return

Priority: P0

## Implementation Goal

Introduce commitment and risk to repeated Hunts by moving rewards to a pending pool and forcing explicit Return decisions.

## Dependencies

- None. This is the foundation feature.

## Player Problem

Hunt has no commitment or exit decision. Rewards are immediately banked, town actions are always available, and combat becomes automatic after early level gains.

## State Contract

Required hero keys:

- location: town | expedition
- expedition.active: bool
- expedition.route_id: string
- expedition.threat: int [0..5]
- expedition.wins: int >= 0
- expedition.pending_gold: int >= 0
- expedition.pending_xp: int >= 0
- expedition.pending_loot: array

## Action Contract

### hunt

- Preconditions: hero created, stamina available.
- Behavior: starts expedition if inactive; resolves combat; writes pending rewards.
- Postconditions: updates threat, wins, pending fields, location=expedition.

### return_town

- Preconditions: expedition active.
- Behavior: bank pending rewards once, reset threat/wins.
- Postconditions: pending fields zeroed, location=town, expedition.active=false.

### select_route

- Preconditions: in town.
- Behavior: route update only.
- Postconditions: route id persisted.

## Rule Formulas

Threat multiplier:

$$
M_{threat} = 1 + (0.08 \times threat)
$$

Pending reward multiplier:

$$
M_{reward} = 1 + (0.10 \times threat)
$$

Defeat pending gold loss:

$$
gold_{lost} = \lfloor pending\_gold \times 0.5 \rfloor
$$

## Validation Rules

- Town-only actions outside town do not mutate resources and do not consume stamina.
- Repeat Return calls cannot duplicate rewards.
- Threat never exceeds cap 5.
- Elite trigger at the fifth consecutive victory.

## Acceptance Criteria

- Five consecutive victories increase Threat from 0 to 5.
- Monster and reward calculations use the current Threat before reset.
- Return banks pending rewards exactly once.
- Repeated Return requests cannot duplicate rewards.
- Defeat applies the configured loss and returns the hero to town.
- Town-only actions outside town do not consume stamina or mutate inventory/gold.
- Existing saved heroes receive a valid default expedition state.
- JSON-encoded hero state remains below the agreed cookie budget at base bag capacity.

## Definition of Done

- All F1 acceptance criteria pass.
- 20-hunt sample includes at least one voluntary Return.
