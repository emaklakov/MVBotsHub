<?php

declare(strict_types=1);

namespace App\Domain\Flows\Contracts;

use App\Domain\Flows\Entities\FlowSession;

interface SessionStoreInterface
{
    public function findActive(int $subscriberId): ?FlowSession;

    public function create(
        int $subscriberId,
        int $versionId,
        string $startGroupId,
        string $startBlockId,
        array $context
    ): FlowSession;

    public function updatePosition(FlowSession $session, string $groupId, string $blockId): void;

    public function updateContext(FlowSession $session, array $context): void;

    public function complete(int $sessionId): void;

    public function completeAllActive(int $subscriberId): void;
}
