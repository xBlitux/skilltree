<?php

declare(strict_types=1);

namespace Skilltree\Tests\Http;

use Closure;
use PDOException;
use PHPUnit\Framework\TestCase;
use Skilltree\Domain\Analysis\AnalysisDataset;
use Skilltree\Domain\Analysis\InvalidDataset;
use Skilltree\Http\AnalysisAction;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

final class AnalysisActionTest extends TestCase
{
    public function testReturnsUtf8JsonAndEmptyListsWithoutCaching(): void
    {
        $response = $this->request(static fn () => new AnalysisDataset(
            ['id' => 1, 'name' => 'Test für Prüfung'], [], [], [], [],
        ));
        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/json; charset=utf-8', $response->getHeaderLine('Content-Type'));
        self::assertSame('no-store', $response->getHeaderLine('Cache-Control'));
        $body = (string) $response->getBody();
        self::assertStringContainsString('Test für Prüfung', $body);
        self::assertSame([], json_decode($body, true, flags: JSON_THROW_ON_ERROR)['skills']);
    }

    public function testInvalidPreparedDatasetReturnsConflictWithoutPartialCalculation(): void
    {
        $response = $this->request(static function (): never {
            throw new InvalidDataset('Es muss genau eine vorbereitete Organisationseinheit vorhanden sein.');
        });
        self::assertSame(409, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame('invalid_dataset', $body['error']['code']);
        self::assertArrayNotHasKey('summary', $body);
    }

    public function testDatabaseFailureNeverExposesConnectionDetails(): void
    {
        $response = $this->request(static function (): never {
            throw new PDOException('Private connection details must not be exposed.');
        });
        self::assertSame(503, $response->getStatusCode());
        self::assertStringNotContainsString('Private connection', (string) $response->getBody());
        self::assertSame('database_unavailable', json_decode(
            (string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR,
        )['error']['code']);
    }

    private function request(Closure $loadDataset): \Psr\Http\Message\ResponseInterface
    {
        $app = AppFactory::create();
        $app->get('/analysis', new AnalysisAction($loadDataset));

        return $app->handle((new ServerRequestFactory())->createServerRequest('GET', '/analysis'));
    }
}
