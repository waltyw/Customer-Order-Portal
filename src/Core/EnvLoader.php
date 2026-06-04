<?php

declare(strict_types=1);

namespace App\Core;

class EnvLoader
{
    public static function load(string $path): void
    {
        if (!is_file($path)) {
            die('.env file not found at ' . $path);
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) continue;
            if (!str_contains($line, '=')) continue;

            [$key, $value] = explode('=', $line, 2);
            $key   = trim($key);
            $value = trim($value);

            // Strip surrounding quotes
            if (preg_match('/^"(.*)"$/s', $value, $m) || preg_match("/^'(.*)'$/s", $value, $m)) {
                $value = $m[1];
            }

            $_ENV[$key]    = $value;
            $_SERVER[$key] = $value;
            putenv("{$key}={$value}");
        }
    }

    public static function require(array $keys): void
    {
        $missing = array_filter($keys, fn($k) => empty($_ENV[$k]));
        if ($missing) {
            die('Missing required .env keys: ' . implode(', ', $missing));
        }
    }
}
