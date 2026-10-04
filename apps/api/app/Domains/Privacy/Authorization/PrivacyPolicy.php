<?php

declare(strict_types=1);

namespace App\Domains\Privacy\Authorization;

use App\Domains\Privacy\Data\PrivacyDecisionContext;
use App\Domains\Privacy\Data\PrivacyDecisionData;
use DateTimeInterface;

interface PrivacyPolicy
{
    public function decide(
        PrivacyDecisionContext $context,
        DateTimeInterface $at,
    ): PrivacyDecisionData;
}
