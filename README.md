# Isekai Idle Life

A simple static PHP RPG with a medieval isekai theme, focused on monster hunting, town selling, and one-shot style progression.

## Structure

- Front controller: `index.php`
- MVC flow in `src/`:
	- `src/Controller/GameController.php`
	- `src/Model/GameDataProvider.php`
	- `src/Model/HeroViewModelBuilder.php`
	- `src/View/GameView.php`
	- `src/View/templates/game.php`
- Domain data:
	- `src/Data/Classes.php`
	- `src/Data/Equipment.php`
	- `src/Data/Monsters.php`
- Game rules engine: `src/Game/Engine.php`
- Frontend assets:
	- `Assets/css/app.css`
	- `Assets/js/app.js`
	- `Assets/Classes/*.png`

## Features

- Character creation with Fencer, Brawler, Scholar, Priest, Hunter, and Bard.
- Core stats: Attack, Defense, Magic, and Speed.
- Equipment slots for Weapon and Armor.
- Monster hunting loop with loot drops.
- Town selling and basic gold management.
- World time cycle with 4 quarters per day (Morning, Day, Afternoon, Night).
- Fixed stamina baseline at 100, with time advancing based on stamina spent and full-quarter rest.
- Session-based state, no database required.

## Class Images

Drop class images in `Assets/Classes` using these mapped filenames:

- `Fencer.png`
- `Brawler.png`
- `Scholar.png`
- `Priest.png`
- `Hunter.png`
- `Bard.png`

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