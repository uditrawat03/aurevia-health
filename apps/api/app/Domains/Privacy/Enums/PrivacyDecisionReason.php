<?php

declare(strict_types=1);

namespace App\Domains\Privacy\Enums;

enum PrivacyDecisionReason: string
{
    case ACTIVE_CONSENT = 'ACTIVE_CONSENT';
    case BREAK_GLASS = 'BREAK_GLASS';
    case NO_EFFECTIVE_CONSENT = 'NO_EFFECTIVE_CONSENT';
    case COUNTRY_POLICY_DENIED = 'COUNTRY_POLICY_DENIED';
}
