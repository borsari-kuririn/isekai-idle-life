---
name: game-design-mechanics
description: "Design and evolve gameplay mechanics focused on loop quality, progression, economy, and class identity while preserving the current static session-based PHP architecture."
argument-hint: "Describe the mechanic goal, affected systems, constraints, and success criteria."
user-invocable: true
---

# Game Design Mechanics Skill

## Purpose
Design, prototype, and tune new gameplay mechanics for Isekai Idle Life with practical, testable changes.

## When To Use
- Create new core loops (for example: exploration, risk-reward, timed events).
- Add progression mechanics (milestones, unlocks, modifiers, mastery).
- Add economy mechanics (cost scaling, sinks, rewards, scarcity controls).
- Reinforce class identity through unique passives and trade-offs.
- Improve pacing and player decisions without adding backend complexity.

## Do Not Use For
- Pure visual polish without gameplay impact.
- Infrastructure refactors unrelated to mechanics.
- Database-driven systems or online service dependencies.

## Project Constraints
- Keep user-facing text and documentation in English.
- Keep the project static and session-based (no database).
- Respect domain split: classes, equipment, monsters, game engine.
- Keep edits small and compatible with current procedural PHP style.
- Preserve the light Game Boy Color inspired presentation direction.

## Design Workflow
1. Define the player problem and target outcome.
2. Propose 2-3 mechanic options with trade-offs.
3. Choose one option and map exact rule changes.
4. Implement minimal code changes in domain-appropriate files.
5. Validate edge cases and regressions in the main loop.
6. Document balance assumptions and tuning knobs.

## Mechanics Checklist
- Clarity: player can understand the rule from UI/log feedback.
- Agency: mechanic creates meaningful choices.
- Balance: rewards and costs scale predictably.
- Compatibility: works with existing classes and session persistence.
- Safety: invalid actions fail gracefully with clear messages.

## Output Expectations
- Brief design rationale and expected player impact.
- Concrete rule specification (numbers, triggers, limits).
- Implemented code edits scoped to relevant domains.
- Validation notes with observed behavior and follow-up tuning ideas.
