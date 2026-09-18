<?php

declare(strict_types=1);

namespace Skilltree\Http;

use Closure;
use PDOException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Skilltree\Domain\Analysis\AnalysisDataset;
use Skilltree\Domain\Analysis\InvalidDataset;
use Skilltree\Domain\Analysis\SkillAnalysis;

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
            $payload = (new SkillAnalysis())->calculate(($this->loadDataset)());
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
}
