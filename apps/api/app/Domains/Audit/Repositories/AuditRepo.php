<?php

declare(strict_types=1);

namespace App\Domains\Audit\Repositories;

use App\Domains\Audit\Data\AuditEventCollectionData;
use App\Domains\Audit\Data\AuditEventData;
use App\Domains\Audit\Data\AuditRecordData;

interface AuditRepo
{
    public function append(AuditRecordData $record): AuditEventData;

    public function latestForOrganization(string $organizationId, int $limit): AuditEventCollectionData;
}
