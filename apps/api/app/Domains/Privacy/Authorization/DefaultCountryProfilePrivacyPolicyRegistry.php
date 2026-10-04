<?php

declare(strict_types=1);

namespace App\Domains\Privacy\Authorization;

use App\Domains\Privacy\Data\PrivacyDecisionContext;
use App\Domains\Privacy\Data\PrivacyDecisionData;

/**
 * Core registry intentionally adds no jurisdiction-specific rule.
 *
 * Country-profile implementations may replace this binding and may only
 * preserve or tighten the base decision; they must never bypass core consent.
 */
final readonly class DefaultCountryProfilePrivacyPolicyRegistry implements CountryProfilePrivacyPolicyRegistry
{
    public function tighten(
        string $profileCode,
        string $profileVersion,
        PrivacyDecisionContext $context,
        PrivacyDecisionData $baseDecision,
    ): PrivacyDecisionData {
        return $baseDecision;
    }
}
