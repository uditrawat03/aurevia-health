<?php

declare(strict_types=1);

namespace App\Domains\Privacy\Authorization;

use App\Domains\Privacy\Data\PrivacyDecisionContext;
use App\Domains\Privacy\Data\PrivacyDecisionData;
use App\Domains\Privacy\Enums\PrivacyDecisionReason;
use App\Domains\Privacy\Repositories\PrivacyRepo;
use DateTimeInterface;

final readonly class ConsentAndBreakGlassPrivacyPolicy implements PrivacyPolicy
{
    public function __construct(private PrivacyRepo $privacy) {}

    public function decide(
        PrivacyDecisionContext $context,
        DateTimeInterface $at,
    ): PrivacyDecisionData {
        if ($this->privacy->hasEffectiveConsent($context, $at)) {
            return new PrivacyDecisionData(
                allowed: true,
                reason: PrivacyDecisionReason::ACTIVE_CONSENT->value,
            );
        }

        $breakGlass = $this->privacy->activeBreakGlass($context, $at);
        if ($breakGlass !== null) {
            return new PrivacyDecisionData(
                allowed: true,
                reason: PrivacyDecisionReason::BREAK_GLASS->value,
                breakGlassAccessId: $breakGlass->id,
            );
        }

        return new PrivacyDecisionData(
            allowed: false,
            reason: PrivacyDecisionReason::NO_EFFECTIVE_CONSENT->value,
        );
    }
}
