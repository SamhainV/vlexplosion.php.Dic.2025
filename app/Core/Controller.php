<?php
declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected function view(string $view, array $data = []): void
    {
        extract($data);

        $viewFile = __DIR__ . '/../views/' . $view . '.php';
        if (!file_exists($viewFile)) {
            throw new \RuntimeException("View not found: " . $viewFile);
        }

        require __DIR__ . '/../views/layouts/header.php';
        require $viewFile;
        require __DIR__ . '/../views/layouts/footer.php';
    }
}
