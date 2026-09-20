<?php

declare(strict_types=1);

namespace Skilltree\Http;

use Closure;
use InvalidArgumentException;
use PDOException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Skilltree\Domain\Analysis\AnalysisDataset;
use Skilltree\Domain\Analysis\InvalidDataset;
use Skilltree\Domain\Analysis\SimulationAnalysis;

final class AnalysisAction
{
    /** @param Closure(): AnalysisDataset $loadDataset */
    public function __construct(private readonly Closure $loadDataset)
    {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $status = 200;
        try {
            $query = $request->getQueryParams();
            if (array_diff(array_keys($query), ['excluded_tasks', 'excluded_employees']) !== []) {
                throw new InvalidArgumentException('Unbekannter Analyseparameter.');
            }
            $tasks = $this->parseIds($query['excluded_tasks'] ?? '');
            $employees = $this->parseIds($query['excluded_employees'] ?? '');
            $payload = (new SimulationAnalysis())->calculate(($this->loadDataset)(), $tasks, $employees);
        } catch (InvalidArgumentException $exception) {
            $status = 400;
            $payload = ['error' => ['code' => 'invalid_filters', 'message' => $exception->getMessage()]];
        } catch (InvalidDataset $exception) {
            $status = 409;
            $payload = ['error' => ['code' => 'invalid_dataset', 'message' => $exception->getMessage()]];
        } catch (PDOException | RuntimeException $exception) {
            $status = 503;
            $payload = ['error' => [
                'code' => 'database_unavailable',
                'message' => 'Die Datenbasis konnte nicht geladen werden.',
            ]];
        }

        $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        return $response->withStatus($status)
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withHeader('Cache-Control', 'no-store');
    }

    private function parseIds(mixed $value): array
    {
        if ($value === '') return [];
        if (!is_string($value) || !preg_match('/^[1-9][0-9]*(,[1-9][0-9]*)*$/D', $value)) {
            throw new InvalidArgumentException('Simulationsfilter müssen positive, kommagetrennte IDs enthalten.');
        }
        $ids = [];
        foreach (explode(',', $value) as $part) {
            $id = filter_var($part, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($id === false) throw new InvalidArgumentException('Ungültige Simulations-ID.');
            $ids[] = $id;
        }
        return $ids;
    }
}
