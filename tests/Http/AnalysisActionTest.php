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

    public function testIncludesWholeCatalogAndHierarchyEvenWithoutRequiredSkills(): void
    {
        $groups = [
            ['id' => 1, 'name' => 'Wurzel', 'parent_skill_group_id' => null],
            ['id' => 2, 'name' => 'Blatt', 'parent_skill_group_id' => 1],
        ];
        $skills = [7 => ['id' => 7, 'name' => 'Zusatzskill', 'skill_group_id' => 2]];
        $response = $this->request(static fn () => new AnalysisDataset(
            ['id' => 1, 'name' => 'Test'], $skills, [], [], [], $groups,
        ));
        $body = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame([], $body['skills']);
        self::assertSame(['red' => 0, 'yellow' => 0, 'green' => 0], $body['summary']['counts']);
        self::assertSame($groups, $body['taxonomy']['groups']);
        self::assertSame(array_values($skills), $body['taxonomy']['skills']);
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

    public function testSimulationUsesOneSnapshotAndDoesNotPersistBetweenRequests(): void
    {
        $calls = 0;
        $load = static function () use (&$calls): AnalysisDataset {
            $calls++;
            return new AnalysisDataset(['id' => 1, 'name' => 'Test'],
                [1 => ['id' => 1, 'name' => 'Skill', 'skill_group_id' => 1]],
                [['id' => 1, 'name' => 'Aufgabe', 'direct_skill_ids' => [1]]],
                [['id' => 1, 'first_name' => 'Test', 'last_name' => 'Person', 'direct_skill_ids' => [1]]], [],
            );
        };
        $response = $this->request($load, ['excluded_employees' => '1']);
        self::assertSame(1, $calls);
        $body = json_decode((string) $response->getBody(), true);
        self::assertSame(['red' => 1, 'yellow' => 0, 'green' => 0], $body['summary']['counts']);
        self::assertSame([null, null, null], $body['development']['candidates'][0]['slots']);
        self::assertSame([1], $body['simulation']['excluded_employee_ids']);
        self::assertCount(1, $body['simulation']['employees']);
        self::assertSame('no-store', $response->getHeaderLine('Cache-Control'));
        $base = json_decode((string) $this->request($load)->getBody(), true);
        self::assertSame(1, $base['summary']['counts']['yellow']);
        self::assertSame([], $base['simulation']['excluded_employee_ids']);
    }

    public function testMalformedAndUnknownFiltersReturn400WithoutPartialResults(): void
    {
        foreach ([['excluded_tasks' => '-1'], ['excluded_tasks' => '1,'], ['excluded_tasks' => ['1']],
            ['excluded_employees' => '1.5'], ['excluded_employees' => '9999999999999999999999'],
            ['excluded_tasks' => '999'], ['unexpected' => '1'], ['organisation' => '1,2'], ['organisation' => ['1']],
            ['maximum_distance' => '0'], ['maximum_distance' => '6'], ['maximum_distance' => ''], ['maximum_distance' => '1,2']] as $query) {
            $response = $this->request(static fn () => new AnalysisDataset(['id' => 1, 'name' => 'Test'], [], [], [], []), $query);
            self::assertSame(400, $response->getStatusCode());
            $body = json_decode((string) $response->getBody(), true);
            self::assertSame('invalid_filters', $body['error']['code']);
            self::assertArrayNotHasKey('summary', $body);
        }
    }

    private function request(Closure $loadDataset, array $query = []): \Psr\Http\Message\ResponseInterface
    {
        $app = AppFactory::create();
        $app->get('/analysis', new AnalysisAction($loadDataset));

        return $app->handle((new ServerRequestFactory())->createServerRequest('GET', '/analysis')->withQueryParams($query));
    }
}
