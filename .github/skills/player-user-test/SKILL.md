---
name: player-user-test
description: "Simulate an end-user playing the game through Playwright and register each tested flow in a reusable instruction log."
argument-hint: "Provide the target URL, focus flow, and success criteria for the playtest."
user-invocable: true
---

# Player User Test Skill

## Purpose
Run reproducible player-centric tests and preserve the findings in an instruction log that improves future interactions.

## When To Use
- Validate gameplay loops from the player perspective.
- Reproduce reported user issues.
- Verify if recent changes broke basic game progression.
- Build a reusable history of tested flows.

## Required Inputs
- Target URL for the running game.
- Flow objective (for example: first battle and first sell).
- Expected outcome.

## Playtest Procedure
1. Open the game in Playwright.
2. Execute the chosen player flow step by step.
3. Capture results for each step as PASS, FAIL, or BLOCKED.
4. Record side notes about UI clarity and friction points.
5. Append a new entry to `.github/instructions/player-playtest-log.instructions.md`.

## Log Entry Template
- Date: YYYY-MM-DD
- Agent: Player Simulation Agent
- Environment: URL and runtime details
- Flow Name: short title
- Steps:
  - [status] step description
- Outcome Summary: one paragraph
- Follow-up Actions: numbered list

## Constraints
- Keep all records in English.
- Do not delete previous run history.
- Prefer small, focused flows over broad unchecked coverage.

## Output Expectations
- A concise report of what was tested.
- A persistent log update with structured steps and outcomes.
- Next actions for unresolved failures or blockers.