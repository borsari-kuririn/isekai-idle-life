# Isekai Idle Life

## Executive Summary

Isekai Idle Life is a session-based browser RPG implemented in PHP, designed as a lightweight, database-free experience centered on character progression, monster hunting, inventory economy, and time-based world simulation.

The project follows a domain-oriented MVC organization and provides a complete gameplay loop with class identity, stat growth, market interactions, stamina management, and quarter-based day progression.

## Functional Scope

### 1. Character System

- Character creation with mouse-selectable class cards.
- Available classes:
	- Fencer
	- Brawler
	- Scholar
	- Priest
	- Hunter
	- Bard
- Core attributes:
	- Attack
	- Defense
	- Magic
	- Speed
- Class portrait support through mapped image assets.

### 2. Combat and Progression

- Hunt action with randomized encounters and battle outcomes.
- Reward model with variable gold, XP, and loot drops.
- Level progression with automatic HP scaling and incremental stat impact.
- Dynamic combat viewer with enemy portrait, HP bar, and enemy stats.

### 3. Economy and Equipment

- Sell flow for loot conversion into gold.
- Market flow for weapon and armor purchases.
- Bag system with default capacity of `20`, occupancy display (`current/max`), and paid expansion in the utility market.
- Equipment slots:
	- Weapon
	- Armor
- Equipment bonuses directly affect combat stat calculations.

### 4. Stamina and World Time

- Fixed stamina baseline: `100` (non-scalable cap).
- Action stamina costs:
	- Hunt: `3`
	- Sell: `2`
	- Buy: `1`
	- Rest: `0` (with recovery effect)
- World time model:
	- Day quarters: `Morning`, `Day`, `Afternoon`, `Night`
	- Every `30` stamina spent advances one quarter.
	- `Rest` advances one full quarter and restores stamina to baseline.

### 5. Persistence and Runtime Resilience

- Primary persistence via PHP session.
- Cookie fallback for hero state to handle environments with unstable session storage.
- Front-controller routing via `.htaccess` to keep request flow consistent through `index.php`.

## Architecture

### Application Entry

- Front controller: `index.php`

### MVC Layers

- Controller:
	- `src/Controller/GameController.php`
- Model:
	- `src/Model/GameDataProvider.php`
	- `src/Model/HeroViewModelBuilder.php`
- View:
	- `src/View/GameView.php`
	- `src/View/templates/game.php`

### Domain and Engine

- Domain data:
	- `src/Data/Classes.php`
	- `src/Data/Equipment.php`
	- `src/Data/Monsters.php`
- Core game rules:
	- `src/Game/Engine.php`

### Frontend Assets

- Styles:
	- `Assets/css/app.css`
- Behavior:
	- `Assets/js/app.js`
- Class portraits:
	- `Assets/Classes/*.png`
- Monster portraits:
	- `Assets/Monsters/*.png`

## Class Image Mapping

Store class icons in `Assets/Classes` with the following filenames:

- `Fencer.png`
- `Brawler.png`
- `Scholar.png`
- `Priest.png`
- `Hunter.png`
- `Bard.png`

## Local Execution

### Prerequisites

- PHP 8+ with web server support.

### Steps

1. Open the repository in your local environment.
2. Start a PHP server from project root:

```bash
php -S localhost:8000
```

3. Access the game:

```text
http://localhost:8000
```

## Deployment Notes

- Ensure Apache rewrite support is enabled (`mod_rewrite`).
- Keep `.htaccess` deployed at project root.
- Ensure PHP session storage is writable in production; cookie fallback is present as an additional safeguard.

## Visual Guidelines

- Light Game Boy Color-inspired interface.
- High-contrast HUD and data readability.
- Pixel-art friendly rendering for character and monster portraits.