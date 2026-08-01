# Feature F5: Class Passives and Mastery

Priority: P1

## Implementation Goal

Make classes mechanically distinct in repeated loops.

## Dependencies

- Strongest synergy after F1 and F2.

## Player Problem

Classes primarily differ by base stats. Only Bard currently changes a named action outcome.

## Passive Set

- Fencer: Tempo. Every third consecutive victory reduces the next Hunt stamina cost by 1.
- Brawler: Grit. First defeat in each expedition loses no pending loot.
- Scholar: Analysis. Shows the next encounter family and gains +10% XP from Elite victories.
- Priest: Sanctuary. Expedition defeat preserves an additional 25% pending gold.
- Hunter: Trail Sense. Route material drop chance +15%.
- Bard: Performance. Existing +2 Rest gold; Elite return also grants +2 gold per Threat.

## Data Contract

Class definitions include passive metadata with id, name, and description. Passive resolution stays in named engine helpers.

## Mastery Contract

- Gain 1 mastery point after a successful expedition return with Threat >= 3.
- Cap mastery at 20.
- Milestones at 5, 10, and 20 improve passives, not base stats.

## State Contract

- mastery_points: int [0..20]
- Optional class-specific passive counters

## Validation Rules

- Every passive must generate observable feedback in logs or HUD.
- Missing legacy passive state defaults safely.
- Bard rest behavior remains compatible.

## Acceptance Criteria

- Every passive has a visible log or preview effect.
- Passive calculations are covered by one focused playtest per class.
- Missing legacy passive state defaults safely.
- Bard existing Rest behavior remains compatible.

## Definition of Done

- One focused playtest pass per class confirms passive behavior.
