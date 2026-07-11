---
name: ux-frontend
description: 'Design and implement frontend UX improvements. Use for visual hierarchy, layout, responsive design, CSS styling, and JavaScript UI interactions without changing backend game logic.'
argument-hint: 'Describe the target screen, UX problem, and desired visual outcome.'
user-invocable: true
---

# UX Frontend Skill

## Purpose
Apply focused UI/UX improvements to the game interface while keeping backend and gameplay logic unchanged.

## When to Use
- Improve readability and visual hierarchy.
- Add or refine CSS layout and component styling.
- Add frontend JavaScript for UI behavior such as tabs, toggles, transitions, and feedback.
- Make screens responsive for mobile and desktop.
- Align visuals with the light Game Boy Color inspired look.

## Never Do
- Do not change combat systems, class balance, item stats, progression, or economy.
- Do not alter session flow or backend architecture.
- Do not add database dependencies.

## Workflow
1. Identify the target screen and UX objective.
2. Audit current structure and styles.
3. Propose a focused visual approach with clear hierarchy.
4. Implement minimal HTML/CSS/JS changes needed for the requested outcome.
5. Validate responsiveness at small and large viewport sizes.
6. Verify interaction states: hover, focus, active, disabled, and empty states.
7. Keep edits small and avoid unrelated refactors.

## Visual Checklist
- Clear hierarchy between primary and secondary actions.
- Consistent spacing scale and typography rhythm.
- Accessible contrast and readable text sizes.
- Responsive layout that avoids overflow and cramped controls.
- Meaningful micro-interactions only where they improve clarity.

## Output Expectations
- Summarize what changed in visual terms.
- List the files edited.
- Mention any UX tradeoffs or follow-up recommendations.
