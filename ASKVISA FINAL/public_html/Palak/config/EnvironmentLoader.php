<?php
class EnvironmentLoader
{
    private static $loaded = false;

    public static function load()
    {
        if (self::$loaded) return;


        $paths = [
            dirname(__DIR__, 2) . '/.env',           // Two levels up from public_html
            '/home/u261509590/domains/askvisa.in/.env', // Absolute path
            $_SERVER['DOCUMENT_ROOT'] . '/../.env',  // One level above web root
        ];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                self::loadEnvFile($path);
                self::$loaded = true;
                return;
            }
        }

        throw new Exception('.env file not found!');
    }

    private static function loadEnvFile($path)
    {
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;

            putenv($line);
            list($key, $value) = explode('=', $line, 2);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }

    public static function get($key, $default = null)
    {
        self::load();
        return getenv($key) ?: $default;
    }
}
