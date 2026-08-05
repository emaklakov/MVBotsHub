<?php

declare(strict_types=1);

namespace App\Application\Flows\Executors;

use App\Domain\Flows\Contracts\BlockExecutorInterface;
use App\Domain\Flows\Contracts\SessionStoreInterface;
use App\Domain\Flows\Dto\BlockExecutionResult;
use App\Domain\Flows\Dto\ExecutionContext;
use App\Domain\Flows\Enums\BlockType;
use Illuminate\Support\Facades\Http;

final class ApiCallBlockExecutor implements BlockExecutorInterface
{
    public function __construct(
        private readonly SessionStoreInterface $sessionStore,
    ) {}

    public function supports(BlockType $type): bool
    {
        return $type === BlockType::API_CALL;
    }

    public function execute(array $block, ExecutionContext $context): BlockExecutionResult
    {
        $config = $block['config'] ?? [];
        $url = $config['url'] ?? '';
        $method = strtoupper($config['method'] ?? 'GET');
        $headers = $config['headers'] ?? [];
        $variable = $config['variable'] ?? 'api_response';

        try {
            $response = Http::withHeaders($headers)->timeout(10)->{$method}($url);
            $result = $response->successful() ? $response->json() : ['error' => $response->body()];
        } catch (\Exception $e) {
            $result = ['error' => $e->getMessage()];
        }

        $context->session->context[$variable] = $result;
        $this->sessionStore->updateContext($context->session, $context->session->context);

        return new BlockExecutionResult(nextBlockId: $block['next_id'] ?? null);
    }
}
