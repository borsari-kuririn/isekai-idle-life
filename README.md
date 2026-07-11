# Isekai Idle Life

A simple static PHP RPG with a medieval isekai theme, focused on monster farming, town selling, and one-shot style progression.

## Structure

- Classes live in `src/Data/Classes.php`
- Equipment lives in `src/Data/Equipment.php`
- Monsters live in `src/Data/Monsters.php`
- Game rules live in `src/Game/Engine.php`
- The front controller stays in `index.php`

## Features

- Character creation with Fencer, Brawler, Scholar, and Priest.
- Core stats: Attack, Defense, Magic, and Speed.
- Equipment slots for Weapon and Armor.
- Monster farming loop with loot drops.
- Town selling and basic gold management.
- Session-based state, no database required.

## Run Locally

1. Open the project folder.
2. Start PHP's built-in server:

```bash
php -S localhost:8000
```

3. Open `http://localhost:8000` in your browser.

## Visual Direction

- Light Game Boy Color inspired palette.
- Soft green surfaces with stronger accent greens.
- Clear contrast for text and cards.