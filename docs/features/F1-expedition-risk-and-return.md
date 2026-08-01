# Feature F1: Expedition Risk and Return

Priority: P0

Status: Conditional go. The core loop is valid, but the concept tests below identify balance and rule-contract revisions required before final acceptance.

Concept validation date: 2026-07-31

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

Recommended monster-power calculation:

$$
power_{monster} = \lceil power_{base} \times M_{threat} \rceil
$$

Apply Threat to aggregate combat power. Do not floor each monster stat independently because low stats can remain unchanged at Threat 1-2 and make early reward increases feel free.

Pending gold multiplier:

$$
M_{gold} = 1 + (0.10 \times threat)
$$

$$
gold_{pending} = \lfloor gold_{base} \times M_{gold} \rfloor
$$

F1 should not multiply XP. Keep `pending_xp = base_xp` so the expedition feature does not worsen the existing early-level acceleration.

Defeat pending gold loss:

$$
gold_{lost} = \lfloor pending\_gold \times 0.5 \rfloor
$$

Defeat settlement:

- Bank the remaining pending gold after the loss.
- Bank 100% of pending XP.
- Lose exactly one uniformly random pending loot item when at least one exists.
- Bank all remaining pending loot.
- Preserve the hero's absolute escaped HP when pending XP causes a level. Increasing `max_hp` during settlement must not heal the hero.

Elite cadence:

```php
$isEliteEncounter = (($wins + 1) % 5) === 0;
```

Encounters 5, 10, 15, and so on are Elite. The encounter immediately after an Elite is normal. For F1, sample the base monster from the normal pool and apply a `1.10` Elite power multiplier instead of forcing one monster species.

Carried bag occupancy:

$$
carried = count(inventory) + count(pending\_loot)
$$

All bag checks and HUD values must use carried occupancy. On Elite victory with one remaining slot, reserve the guaranteed route material before attempting ordinary loot.

## Concept Test Method

The concept was evaluated against the current one-roll combat system:

$$
power = 2 \times attack + defense + magic + speed
$$

Both sides add an independent integer roll from 0 through 8, and ties favor the hero. Exact outcome probabilities were enumerated across every level-1 class, all four current monsters, and Threat 0-5.

The evaluation also traced current session transitions for pending rewards, settlement, bag capacity, stamina, town restrictions, HTMX requests, cookie fallback, and repeat Return requests.

### Level-1 Average Win Probability

The values below average the four current monsters with equal encounter weight.

| Class | Hero power | Threat 0 | Threat 3 | Threat 5 |
|---|---:|---:|---:|---:|
| Brawler | 16 | 79.6% | 67.3% | 57.4% |
| Fencer | 15 | 75.0% | 62.7% | 52.2% |
| Hunter | 15 | 75.0% | 62.7% | 52.2% |
| Priest | 12 | 58.3% | 45.7% | 35.2% |
| Scholar | 11 | 51.5% | 39.5% | 29.9% |
| Bard | 8 | 31.8% | 22.8% | 16.0% |

The average hides severe matchup spikes. Under the original forced-Skeleton concept, a level-1 Hunter's Skeleton win probability falls from 25.9% at Threat 0 to 3.7% at Threat 3 and 0% at Threat 5. Scholar, Priest, and Bard can encounter mathematically unwinnable Elite states earlier.

### Concept Verdict

The Return decision creates useful tension for Fencer, Brawler, and Hunter, but the unmodified rules create an unfair wall for weaker classes and an exploitable farming state for stronger heroes.

The concept is approved only with these revisions:

1. Scale aggregate monster power instead of independently flooring stats.
2. Boost pending gold but not pending XP in F1.
3. Make every fifth encounter Elite rather than every encounter after the fourth win.
4. Do not force Skeleton as the Elite base monster.
5. Preserve escaped HP through settlement level-ups.
6. Show combined banked and pending bag occupancy.
7. Preview the exact defeat settlement before the player chooses Continue.

## Player Scenario Catalogue

### S1: First Expedition Victory

Initial state:

- Fresh hero in town.
- Stamina `100/100`.
- Expedition inactive, Threat 0, no pending rewards.

Player action: `Start Expedition`.

Expected state:

- Hunt spends 3 stamina.
- Location changes to expedition.
- First encounter uses Threat 0.
- On victory, `wins` becomes 1 and Threat becomes 1.
- Gold, XP, and loot are pending; banked gold, XP, and inventory do not change.
- Continue and Return are both visible.

Player situation: the player learns that rewards are at risk and makes the first meaningful safety-versus-growth decision.

### S2: First Expedition Defeat

Initial state: fresh hero with no pending rewards.

Expected state:

- Stamina and combat HP damage apply.
- Expedition ends and location becomes town.
- No pending reward can be lost or duplicated.
- The result log explains that the hero escaped before securing rewards.

Player situation: low-stakes disappointment. This is acceptable only if starting another expedition is immediately available and the failure message is clear.

### S3: Cautious One-Win Return

Initial state: one victory, Threat 1, one pending reward bundle.

Player action: `Return to Town`.

Expected state:

- All pending rewards bank exactly once.
- Threat and wins reset to zero.
- Return costs no stamina and no gold.
- A repeated Return request changes no resource.

Player situation: a risk-averse player discovers a reliable strategy. This strategy should remain valid, but repeated short expeditions should earn less per stamina than a successful Threat 3 return.

Balance risk: if route entry and Return are entirely free, one-win resets may dominate weaker classes. Measure reward per stamina by class before adding an entry fee.

### S4: Confident Push to Threat 3

Initial state: three consecutive victories with visible pending gold, XP, and loot.

Expected preview before the fourth Hunt:

- Current Threat and next-encounter difficulty.
- Pending rewards at risk.
- Exact defeat rule: 50% pending gold loss, one pending item loss, all XP preserved.
- Whether the next encounter is normal or Elite.

Player situation: the player should feel greed and concern, not uncertainty about hidden rules. This is the intended core F1 experience.

### S5: Elite Warning at Four Wins

Initial state: four wins, Threat 4.

Expected state:

- UI says the next encounter is Elite before the player clicks Continue.
- The fifth encounter uses current Threat 4, then the Elite `1.10` power multiplier.
- Elite species comes from the normal eligible pool.
- Victory grants the guaranteed route material and then raises wins/Threat.
- The sixth encounter is normal at capped Threat 5.

Player situation: a prepared player chooses between banking a strong run and accepting a clearly signposted spike.

### S6: Defeat with Valuable Pending Rewards

Initial state:

- Pending gold: 11.
- Pending XP: enough to cross one level.
- Pending loot: two items.
- Escaped HP after combat: 7.

Expected settlement:

- Lose `floor(11 * 0.5) = 5` pending gold and bank 6.
- Bank all pending XP.
- Lose exactly one random pending item and bank the other.
- Apply level/max-HP changes without changing escaped HP 7.
- Return to town with expedition fields reset.
- Log both lost and retained rewards.

Player situation: the loss should sting without erasing the expedition. A summary that only reports losses will feel more punitive than the actual rule.

### S7: Low HP but High Pending Value

Initial state: hero at 1-5 HP, Threat 3+, valuable pending rewards.

Current combat fact: HP does not affect win probability and cannot fall below 1 in the current one-roll system.

Player situation: the UI implies mortal danger, but mechanically low HP only changes the size of the displayed number. This weakens the Return decision.

F1 decision: do not add death in this feature, but disclose projected defeat consequences rather than implying death. A future combat feature may make low HP change escape or defeat severity.

### S8: Insufficient Stamina During Expedition

Initial state: active expedition with stamina below 3.

Expected state:

- Continue Hunt is blocked without mutating Threat, wins, location, or pending rewards.
- Return remains enabled and free.
- The player cannot become trapped outside town.

Player situation: the expedition ends through a safe decision rather than an opaque dead end.

### S9: Bag Nearly Full

Initial state: banked inventory 19, pending loot 0, capacity 20.

Expected state:

- First pending loot fills carried occupancy to `20/20`.
- HUD immediately shows `20/20`, even though the item is pending.
- Further common loot is left behind; gold and XP may still become pending.
- If the next victory is Elite, the guaranteed material has priority over ordinary loot when only one slot was available before resolution.

Player situation: Return/Sell becomes a natural inventory decision. Hidden pending occupancy would instead look like a bug.

### S10: Town Action Attempted During Expedition

Player action: submit Rest, Sell, Buy, Equip, Upgrade, Craft, Expand Bag, or Select Route while expedition is active.

Expected state:

- Request is rejected server-side.
- No stamina, gold, inventory, equipment, route, or pending field changes.
- Log says the player must Return to Town first.

Player situation: location commitment feels real, while crafted requests cannot bypass it.

### S11: Reload and Repeat Return

Initial state: active expedition with pending rewards.

Expected state:

- Reload restores active status, Threat, wins, route, and pending rewards.
- Return banks once.
- Browser retry or double-click cannot bank twice.
- Exactly one `#game-shell` remains after each HTMX response.

Known architectural limit: cookie fallback is client-held and unsigned. F1 can guarantee idempotence within current session state but cannot prevent deliberate old-cookie replay without server-side storage or signed/versioned state.

### S12: Strong Hero Beyond the First Elite

Initial state: veteran hero wins encounter 5 and continues.

Expected state:

- Threat remains capped at 5.
- Encounter 6 is normal.
- Encounter 10 is the next Elite.
- Route material is awarded only on Elite victories.

Player situation: a strong hero can farm at maximum Threat, but cannot convert every post-cap Hunt into an Elite/material reward.

### S13: Route Selection During F1

Initial state: hero in town choosing between valid route IDs.

Expected state:

- Selection persists and costs no stamina.
- Invalid IDs are rejected or normalized without changing resources.
- In F1, routes may change labels and Elite material IDs but must remain combat-equivalent unless route balance is explicitly added to the feature.

Player situation: route choice prepares later features without pretending to offer strategic differences that do not yet exist.

### S14: Weak-Class Early Expedition

Initial state: level-1 Bard or Scholar with no equipment.

Expected player behavior:

- Frequent first- or second-encounter returns.
- Elite attempts should be rare until equipment or levels improve.
- UI should not frame cautious Return as failure.

Balance gate: in a 20-expedition sample, each class must be able to bank at least one victory without requiring an Elite attempt. If Bard cannot produce a viable positive loop, F1 needs a class-aware safety rule or earlier equipment access; do not hide the problem by only testing Hunter/Brawler.

## State Consistency Rules

State normalization must enforce these invariants atomically:

- `active=true` implies `location=expedition`.
- `active=false` implies `location=town`, Threat 0, wins 0, and empty pending rewards.
- Threat is clamped to `[0,5]`.
- Wins is non-negative and may exceed Threat after the cap.
- Route ID references a valid static route.
- Pending loot stores stable item IDs/counts where possible, not duplicated catalog records.
- Raw hero JSON should remain below 3,500 bytes and the final cookie below 4,096 bytes.

If an old or inconsistent save cannot be repaired without risking reward duplication, normalize it to town and log a recovery message.

## Validation Rules

- Town-only actions outside town do not mutate resources and do not consume stamina.
- Repeat Return calls cannot duplicate rewards.
- Threat never exceeds cap 5.
- Elite triggers on encounters 5, 10, 15, and so on; the encounter after an Elite is normal.
- Pending XP is not Threat-multiplied in F1.
- Settlement level-ups preserve absolute escaped HP.
- Bag occupancy includes banked and pending loot.
- Defeat previews and logs lost and retained rewards.

## Acceptance Criteria

- Five consecutive victories increase Threat from 0 to 5.
- Monster and reward calculations use the current Threat before reset.
- Threat difficulty uses aggregate combat power with one documented rounding rule.
- Fixed 10 gold at Threat 2 becomes 12 pending gold; fixed 10 XP remains 10 pending XP.
- Return banks pending rewards exactly once.
- Repeated Return requests cannot duplicate rewards.
- Defeat with 11 pending gold and two pending items loses 5 gold and exactly one item, preserves all pending XP, banks retained rewards, and returns the hero to town.
- XP settlement crossing a level increases level/max HP while preserving escaped absolute HP.
- Town-only actions outside town do not consume stamina or mutate inventory/gold.
- Hunt below 3 stamina does not mutate expedition state and Return remains available.
- Elite occurs on encounters 5 and 10 only within the first ten victories; encounter 6 is normal.
- Inventory 19 plus one pending item displays carried occupancy `20/20`.
- Elite material takes priority over common loot when exactly one bag slot remains.
- Valid route selection persists only in town; invalid route IDs do not mutate resources.
- Existing saved heroes receive a valid default expedition state.
- Active expedition state survives session/cookie round-trip without reward duplication.
- Raw JSON remains below 3,500 bytes and encoded cookie state remains below 4,096 bytes at base bag capacity.

## Scenario Test Matrix

| Test ID | Scenario | Required assertion |
|---|---|---|
| F1-S01 | First victory | Banked resources unchanged; pending resources, wins, and Threat increase |
| F1-S02 | First defeat | Returns to town with no negative or duplicated pending values |
| F1-S03 | One-win Return | Banks once; second Return is resource-idempotent |
| F1-S04 | Threat 3 Continue | Preview shows next risk and exact defeat settlement |
| F1-S05 | Elite cadence | Encounters 5 and 10 Elite; encounter 6 normal |
| F1-S06 | Defeat settlement | 11 pending gold loses 5; one of two items lost; XP retained |
| F1-S07 | Settlement level-up | Escaped HP does not become full HP |
| F1-S08 | Low stamina | Hunt blocked; Return remains valid |
| F1-S09 | Full carried bag | Pending loot counts toward HUD/capacity; Elite material reserved |
| F1-S10 | Crafted town action | No resource or stamina mutation outside town |
| F1-S11 | Reload/double Return | State persists; rewards bank once; one shell remains |
| F1-S12 | Post-Elite continuation | Threat stays capped; normal encounter follows Elite |
| F1-S13 | Route validation | Only valid in-town route IDs persist |
| F1-S14 | All-class viability | Each class completes a positive short expedition loop |

## Balance Evaluation Targets

Run at least 200 deterministic or seeded simulated expeditions per class after the rule changes. Report:

- Return rate by Threat.
- Defeat rate by Threat.
- Average banked gold and XP per stamina.
- Percentage of runs reaching the first Elite.
- Percentage of Elite encounters with mathematically non-zero win probability.
- Average carried bag occupancy at Return.

Initial product targets:

- Approximately 60% of fresh runs should attempt Threat 3.
- Fresh heroes should usually reach level 2-3, not level 4, after ten Hunt attempts.
- No eligible encounter should have 0% win probability for a valid class/level band.
- Threat 3 should improve expected banked gold per stamina by 10-25% over one-win returns while carrying a visibly higher loss rate.
- All six classes must have a viable positive expected-value strategy without being forced into an Elite encounter.

## Definition of Done

- All F1 acceptance criteria pass.
- The full F1-S01 through F1-S14 scenario matrix passes.
- A 20-hunt player sample includes at least one voluntary Return.
- Balance simulation meets the all-class viability and non-zero encounter probability gates.
- Player-facing preview text explains pending rewards, next encounter type, and defeat settlement before Continue.

## Test Scenarios

### Scenario S1: First Expedition Start

Goal:
- Verify the first Hunt starts an expedition and shows pending progression.

Expected:
- `location` changes to `expedition`.
- `expedition.active` becomes `true`.
- `expedition.route_id` is set.
- Pending rewards are shown in expedition state.

### Scenario S2: Short Safe Loop (1-2 Wins Then Return)

Goal:
- Validate low-risk loop closure and single banking behavior.

Expected:
- Threat increments by win count.
- Return banks pending rewards exactly once.
- Threat, wins, and pending values reset after return.
- Repeating Return without a new expedition does not duplicate rewards.

### Scenario S3: Mid-Risk Loop (Threat 3 Decision)

Goal:
- Validate push-your-luck decision point and state consistency.

Expected:
- Threat reaches 3 after three victories.
- Pending reward pool increases each victory.
- Player can either Continue or Return with consistent state transitions.

### Scenario S4: Elite Threshold Loop (Threat 4 -> 5)

Goal:
- Validate elite trigger timing and reward behavior.

Expected:
- Elite warning appears at Threat 4.
- Fifth consecutive victory triggers elite encounter.
- Elite result updates pending state without bypassing return banking.

### Scenario S5: Defeat During Expedition

Goal:
- Validate defeat penalties and recovery path.

Expected:
- `location` returns to `town`.
- Pending gold loss equals `floor(pending_gold * 0.5)`.
- One pending loot item is lost if available.
- Pending XP behavior follows configured rule.

### Scenario S6: Town-Only Action Restriction

Goal:
- Validate invalid actions outside town are blocked safely.

Expected:
- `rest`, `sell`, `buy`, `expand_bag` do not mutate state outside town.
- No stamina is consumed by blocked town-only actions.
- Log shows a clear block reason.

### Scenario S7: Route Selection Behavior

Goal:
- Validate route switching in town and route lock during active expedition.

Expected:
- `select_route` works in town and persists selection.
- Route cannot be changed while expedition is active (or is ignored with message).
- Hunts use selected route encounter pool.

### Scenario S8: Reload/Resume Consistency

Goal:
- Validate session + cookie fallback consistency mid-expedition.

Expected:
- Reload restores expedition active state and pending values.
- No duplicated banking after reload.
- UI and server state remain aligned after next action.

### Scenario S9: Bag Pressure During Expedition

Goal:
- Validate pending loot handling near or at capacity.

Expected:
- Pending loot respects documented bag/overflow rule.
- Overflow produces clear feedback and does not corrupt inventory.
- Return still resolves pending rewards consistently.

### Scenario S10: Long Loop Stability (20 Hunts)

Goal:
- Validate F1 durability under repeated actions.

Expected:
- No state drift across repeated threat growth/reset cycles.
- At least one voluntary Return occurs in sample.
- No duplicated rewards, negative values, or impossible transitions.

## Possible Test Cases (Manual and Automation Candidates)

| ID | Type | Setup | Steps | Assertions |
|---|---|---|---|---|
| F1-T01 | Manual/Auto | Fresh hero in town | Hunt once | Expedition starts, pending rewards visible, location=expedition |
| F1-T02 | Manual/Auto | Active expedition with pending rewards | Return to Town once | Banked values increase once, pending reset to zero |
| F1-T03 | Auto | Same as F1-T02 after return | Return again | No additional banked rewards |
| F1-T04 | Manual/Auto | Fresh expedition | Win 5 consecutive hunts | Threat progression 0->5 and elite trigger at 5th win |
| F1-T05 | Manual/Auto | Active expedition with pending gold and loot | Force/observe defeat | 50% pending gold loss, one pending loot loss, location=town |
| F1-T06 | Auto | Active expedition outside town | Trigger rest/sell/buy/expand_bag | No resource mutation, no stamina cost, clear log block message |
| F1-T07 | Manual/Auto | In town with multiple routes | Select route A, then Hunt | Encounter pool reflects route A |
| F1-T08 | Auto | Mid-expedition | Attempt select_route | Rejected or ignored per spec, state unchanged |
| F1-T09 | Manual/Auto | Mid-expedition with pending state | Reload page, then Hunt/Return | State persists and remains coherent |
| F1-T10 | Auto | Hero with near-full bag | Continue expedition and gain loot | Overflow follows documented behavior, no inventory corruption |
| F1-T11 | Auto | Long-run script | 20 hunts with mixed Continue/Return | No negative/invalid state, no duplicate bank events |
| F1-T12 | Manual | Night phase active | Perform expedition cycle | UI readability and actions remain clear in dark palette |

## Regression Focus

When F1 changes are touched, always re-run at least:
- F1-T01 (expedition starts correctly)
- F1-T02/F1-T03 (single banking guarantee)
- F1-T06 (town restriction safety)
- F1-T09 (reload consistency)

## Suggested Execution Order

1. F1-T01
2. F1-T02
3. F1-T03
4. F1-T06
5. F1-T04
6. F1-T05
7. F1-T07/F1-T08
8. F1-T09
9. F1-T10
10. F1-T11
11. F1-T12
