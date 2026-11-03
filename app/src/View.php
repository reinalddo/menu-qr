<?php

declare(strict_types=1);

namespace MenuQr;

final class View
{
    public static function render(string $template, array $data = [], ?string $layout = 'layout/simple'): void
    {
        $content = self::capture($template, $data);
        echo $layout === null ? $content : self::capture($layout, ['content' => $content] + $data);
    }

    private static function capture(string $template, array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        require APP_ROOT . '/views/' . $template . '.php';
        return (string) ob_get_clean();
    }
}
