<?php

declare(strict_types=1);

use Dotenv\Dotenv;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Skilltree\Infrastructure\Database\ConnectionFactory;
use Skilltree\Infrastructure\Database\AnalysisRepository;
use Skilltree\Http\AnalysisAction;
use Slim\Factory\AppFactory;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$projectRoot = dirname(__DIR__, 2);
if (is_file($projectRoot . '/.env')) {
    Dotenv::createImmutable($projectRoot)->safeLoad();
}

$app = AppFactory::create();
if (PHP_SAPI !== 'cli-server') {
    $scriptDirectory = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    if ($scriptDirectory !== '/' && $scriptDirectory !== '.') {
        $app->setBasePath(rtrim($scriptDirectory, '/'));
    }
}

$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->addErrorMiddleware(
    filter_var(getenv('APP_DEBUG') ?: false, FILTER_VALIDATE_BOOL),
    true,
    true,
);

$json = static function (ResponseInterface $response, array $payload, int $status = 200): ResponseInterface {
    $response->getBody()->write((string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

    return $response
        ->withStatus($status)
        ->withHeader('Content-Type', 'application/json; charset=utf-8');
};

$app->get('/health', static function (
    ServerRequestInterface $request,
    ResponseInterface $response,
) use ($json): ResponseInterface {
    return $json($response, ['status' => 'ok']);
});

$app->get('/health/database', static function (
    ServerRequestInterface $request,
    ResponseInterface $response,
) use ($json): ResponseInterface {
    try {
        $connection = ConnectionFactory::fromEnvironment();
        $database = $connection->query('SELECT DATABASE()')->fetchColumn();

        return $json($response, [
            'status' => 'ok',
            'database' => $database,
        ]);
    } catch (Throwable $exception) {
        return $json($response, [
            'status' => 'error',
            'message' => 'Die Datenbankverbindung konnte nicht hergestellt werden.',
        ], 503);
    }
});

$app->get('/analysis', new AnalysisAction(
    static fn () => (new AnalysisRepository(ConnectionFactory::fromEnvironment()))->load(),
));

$app->run();
