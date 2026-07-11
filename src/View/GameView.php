<?php
declare(strict_types=1);

final class GameView
{
    public function __construct(private string $templatePath)
    {
    }

    public function render(array $viewModel): void
    {
        extract($viewModel, EXTR_SKIP);
        require $this->templatePath;
    }
}
