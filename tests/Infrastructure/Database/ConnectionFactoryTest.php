<?php

declare(strict_types=1);

namespace Skilltree\Tests\Infrastructure\Database;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Skilltree\Infrastructure\Database\ConnectionFactory;

final class ConnectionFactoryTest extends TestCase
{
    public function testMissingConfigurationIsReportedWithoutOpeningAConnection(): void
    {
        $original = getenv('DB_HOST');
        putenv('DB_HOST');

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('DB_HOST');

            ConnectionFactory::fromEnvironment();
        } finally {
            $original === false ? putenv('DB_HOST') : putenv('DB_HOST=' . $original);
        }
    }
}

