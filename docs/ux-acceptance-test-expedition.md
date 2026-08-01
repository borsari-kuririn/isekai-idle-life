# UX Acceptance Test Plan - Expedition Risk and Return (F1)

## 1. Goal

Validate that the Expedition Risk and Return feature is understandable, playable, and visually clear under normal and edge-case gameplay loops.

This test plan focuses on UX acceptance, not only game-rule correctness.

## 2. Scope

In scope:
- Threat progression visibility and readability.
- Pending rewards visibility and trust (gold, XP, loot).
- Player decision clarity between Continue and Return to Town.
- Availability and blocking of town-only actions outside town.
- Feedback quality in logs, HUD, and action controls.
- Session resilience through HTMX updates and page reload.

Out of scope:
- Long-term balance tuning for all future features.
- Route content volume tuning (F2+) except where needed for F1 UX checks.

## 3. Preconditions

Environment:
- Local URL running: http://localhost:8000/
- Browser: Chromium or equivalent.
- HTMX updates active for game shell swaps.

Feature assumptions:
- Expedition state exists in hero state.
- Return to Town action is implemented.
- Threat and pending rewards are shown in the UI.
- Town-only actions are blocked outside town.

Data setup:
- Fresh hero (level 1) for baseline runs.
- Optional prepared hero (level 4+) for faster high-risk and defeat scenarios.

## 4. Acceptance Criteria (UX)

A run is accepted only if all items below pass:
- Player can identify current location state (town versus expedition) within 2 seconds.
- Player can identify Threat value and pending rewards without opening extra panels.
- Continue versus Return controls are visible, distinct, and never ambiguous.
- Blocked actions outside town provide immediate, concise, non-technical feedback.
- Return to Town gives one clear confirmation and updates all relevant widgets in one interaction cycle.
- No visual overlap, clipped text, or hidden critical controls on desktop and mobile widths.
- No duplicated game shell, no stale tab highlight after HTMX response.

## 5. Core Validation Checklist

For each flow, validate:
- UI state labels are correct (town or expedition).
- Threat indicator updates by +1 per victory and never exceeds cap.
- Pending rewards increase while expedition is active.
- Banked rewards do not increase before Return.
- Return banks exactly once and resets pending state.
- Defeat applies loss messaging and state transition back to town.
- Town-only actions outside town do not mutate stamina, gold, or inventory.
- Log entries match the visible state change.
- Focus and tab order still reach primary actions.

## 6. Loop Variations Matrix

### Variation A: Conservative Loop (Low Risk)

Purpose:
- Validate early readability and low-threat usability.

Steps:
1. Create hero and start at town.
2. Start expedition.
3. Win 1-2 hunts.
4. Return to Town.

Expected:
- Threat increments correctly.
- Pending rewards are visible before return.
- Return banks once and resets Threat and pending values.
- Player receives clear closure feedback.

### Variation B: Balanced Loop (Threat 3 Decision Point)

Purpose:
- Validate decision UX under moderate pressure.

Steps:
1. Start expedition.
2. Continue until Threat 3.
3. Pause on decision point and evaluate controls.
4. Return to Town.

Expected:
- Continue and Return controls remain visible and distinct.
- Risk information is understandable without guessing.
- Banked rewards match pending snapshot before return.

### Variation C: High-Risk Loop (Threat 5 and Elite)

Purpose:
- Validate elite warning and high-risk readability.

Steps:
1. Start expedition.
2. Continue to Threat 4.
3. Verify elite warning before next hunt.
4. Complete fifth hunt.
5. Return to Town.

Expected:
- Elite warning appears at the right time.
- Elite result feedback is clear.
- Route-specific material feedback is visible when granted.

### Variation D: Defeat Recovery Loop

Purpose:
- Validate failure UX and trust after loss.

Steps:
1. Start expedition.
2. Build pending rewards.
3. Trigger partial defeat.
4. Observe state after defeat.

Expected:
- Hero returns to town state.
- Pending gold loss and pending loot loss are reflected once.
- Messaging explains what was lost and what was preserved.
- Player can continue from stable controls without dead ends.

### Variation E: Town Restriction Loop

Purpose:
- Validate blocked action UX outside town.

Steps:
1. Start expedition.
2. Attempt Rest, Sell, Buy, and Expand Bag.
3. Return to Town and retry same actions.

Expected:
- Outside town: actions blocked with concise reason.
- In town: actions re-enabled and functional.
- No hidden stamina or gold cost on blocked attempts.

### Variation F: Bag Pressure Loop

Purpose:
- Validate inventory pressure feedback while expedition is active.

Steps:
1. Fill bag near capacity.
2. Continue expedition until loot overflow condition.
3. Return to Town.

Expected:
- Overflow behavior is clearly explained.
- Player can still understand pending versus banked loot.
- No table stretch or unreadable inventory row behavior.

### Variation G: Session and Reload Loop

Purpose:
- Validate UX continuity with session and cookie fallback.

Steps:
1. Start expedition and build pending rewards.
2. Reload page.
3. Continue hunting, then Return.

Expected:
- UI restores expedition state correctly.
- No duplicated rewards after reload.
- Active tabs and key labels stay coherent.

### Variation H: Time-Phase Loop (Day to Night)

Purpose:
- Validate readability and contrast changes in night phase.

Steps:
1. Advance time into night.
2. Recheck all key text boxes and action controls.
3. Execute one expedition cycle in night.

Expected:
- Dark backgrounds and light text maintain strong contrast.
- Threat, pending rewards, and actions remain legible.
- No control becomes unreadable in night palette.

## 7. Cross-Class Coverage

Run Variation B with at least these classes:
- Fencer
- Scholar
- Hunter
- Bard

Class acceptance focus:
- Role text and title remain readable.
- Passive-related feedback does not obscure expedition feedback.
- No class-specific layout break.

## 8. UX Defect Severity Guide

- Critical: Player cannot continue loop, or wrong action result is presented as successful.
- High: Core decision signals are unclear or hidden (Threat, pending rewards, Return).
- Medium: Friction exists but loop remains playable (wording, spacing, visual hierarchy).
- Low: Cosmetic issues with no decision impact.

## 9. Evidence To Capture Per Flow

- Start snapshot.
- Mid-loop snapshot at Threat 2-3.
- Decision snapshot (Continue or Return).
- End snapshot (banked or defeat state).
- Last 5 log lines for each variant.

## 10. Pass/Fail Rule

Pass:
- All mandatory acceptance criteria pass.
- No Critical or High defects.

Conditional pass:
- Only Medium or Low issues remain, with clear follow-up tasks.

Fail:
- Any Critical defect.
- Two or more High defects.

## 11. Suggested Execution Order

1. Variation A
2. Variation B
3. Variation E
4. Variation C
5. Variation D
6. Variation G
7. Variation H
8. Variation F

This order front-loads basic trust checks before stress scenarios.

## 12. Notes For Automation Bridge

The same matrix can be converted to browser automation later.

When converting:
- Prefer deterministic selectors for action controls and state chips.
- Assert one game shell instance after each HTMX action.
- Record state deltas for Threat, pending rewards, and banked rewards each step.
