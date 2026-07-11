<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/src/Data/Classes.php';
require_once __DIR__ . '/src/Data/Equipment.php';
require_once __DIR__ . '/src/Data/Monsters.php';
require_once __DIR__ . '/src/Game/Engine.php';

require_once __DIR__ . '/src/Model/GameDataProvider.php';
require_once __DIR__ . '/src/Model/HeroViewModelBuilder.php';
require_once __DIR__ . '/src/Controller/GameController.php';
require_once __DIR__ . '/src/View/GameView.php';

$controller = new GameController(new GameDataProvider(), new HeroViewModelBuilder());
$viewModel = $controller->handle($_GET, $_POST);

$view = new GameView(__DIR__ . '/src/View/templates/game.php');
$view->render($viewModel);
