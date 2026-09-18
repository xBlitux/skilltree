<?php

declare(strict_types=1);

namespace Skilltree\Infrastructure\Database;

use PDO;
use RuntimeException;

final class ConnectionFactory
{
    public static function fromEnvironment(): PDO
    {
        $host = self::required('DB_HOST');
        $port = self::required('DB_PORT');
        $database = self::required('DB_NAME');
        $user = self::required('DB_USER');
        $password = self::required('DB_PASSWORD');
        $charset = self::value('DB_CHARSET') ?: 'utf8mb4';

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $host,
            $port,
            $database,
            $charset,
        );

        return new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    private static function required(string $name): string
    {
        $value = self::value($name);

        if ($value === null || $value === '') {
            throw new RuntimeException(sprintf('Die Umgebungsvariable %s fehlt.', $name));
        }

        return $value;
    }

    private static function value(string $name): ?string
    {
        $value = $_ENV[$name] ?? $_SERVER[$name] ?? getenv($name);

        return is_string($value) ? $value : null;
    }
}
