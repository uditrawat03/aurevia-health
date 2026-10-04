<?php

declare(strict_types=1);

namespace App\Domains\Privacy\Authorization;

use App\Domains\Privacy\Data\PrivacyDecisionContext;
use App\Domains\Privacy\Data\PrivacyDecisionData;

interface CountryProfilePrivacyPolicyRegistry
{
    public function tighten(
        string $profileCode,
        string $profileVersion,
        PrivacyDecisionContext $context,
        PrivacyDecisionData $baseDecision,
    ): PrivacyDecisionData;
}
