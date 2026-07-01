<?php

final class View
{
    public static function render(string $template, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $viewFile = TEMPLATES_PATH . '/' . $template . '.php';

        if (!is_file($viewFile)) {
            http_response_code(500);
            echo "View not found: {$template}";
            return;
        }

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        require TEMPLATES_PATH . '/layout.php';
    }

    public static function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}
