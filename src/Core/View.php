<?php

declare(strict_types=1);

namespace App\Core;

class View
{
    private static string $templateBase = '';

    public static function init(string $base): void
    {
        self::$templateBase = rtrim($base, '/');
    }

    public static function render(string $template, array $data = [], string $layout = 'main'): void
    {
        $content = self::capture($template, $data);
        $title = $data['title'] ?? 'Customer Portal';
        $flash = Security::getFlash();

        require self::$templateBase . '/layouts/' . $layout . '.php';
    }

    public static function renderRaw(string $template, array $data = []): void
    {
        extract($data);
        require self::$templateBase . '/' . $template . '.php';
    }

    private static function capture(string $template, array $data): string
    {
        extract($data);
        ob_start();
        require self::$templateBase . '/' . $template . '.php';
        return (string)ob_get_clean();
    }
}
