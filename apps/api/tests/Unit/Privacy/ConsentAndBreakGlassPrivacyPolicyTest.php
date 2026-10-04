<?php

declare(strict_types=1);

namespace Tests\Unit\Privacy;

use App\Domains\Privacy\Authorization\ConsentAndBreakGlassPrivacyPolicy;
use App\Domains\Privacy\Data\BreakGlassAccessData;
use App\Domains\Privacy\Data\PrivacyDecisionContext;
use App\Domains\Privacy\Enums\ConsentDataCategory;
use App\Domains\Privacy\Enums\ConsentPurpose;
use App\Domains\Privacy\Enums\ConsentRecipientClass;
use App\Domains\Privacy\Enums\PrivacyDecisionReason;
use App\Domains\Privacy\Repositories\PrivacyRepo;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class ConsentAndBreakGlassPrivacyPolicyTest extends TestCase
{
    public function test_effective_consent_allows_access_without_break_glass(): void
    {
        $repo = $this->createMock(PrivacyRepo::class);
        $repo->expects(self::once())
            ->method('hasEffectiveConsent')
            ->willReturn(true);
        $repo->expects(self::never())
            ->method('activeBreakGlass');

        $decision = (new ConsentAndBreakGlassPrivacyPolicy($repo))->decide(
            $this->context(),
            new DateTimeImmutable('2026-10-04T09:00:00+00:00'),
        );

        self::assertTrue($decision->allowed);
        self::assertSame(PrivacyDecisionReason::ACTIVE_CONSENT->value, $decision->reason);
        self::assertNull($decision->breakGlassAccessId);
    }

    public function test_active_break_glass_allows_access_when_consent_is_not_effective(): void
    {
        $repo = $this->createMock(PrivacyRepo::class);
        $repo->expects(self::once())
            ->method('hasEffectiveConsent')
            ->willReturn(false);
        $repo->expects(self::once())
            ->method('activeBreakGlass')
            ->willReturn(new BreakGlassAccessData(
                id: '01JBREAKGLASS',
                organizationId: '01JORG',
                patientId: '01JPATIENT',
                facilityId: '01JFACILITY',
                actorUserId: 7,
                purpose: ConsentPurpose::TREATMENT->value,
                reason: 'Immediate clinical emergency',
                activatedAt: '2026-10-04T08:55:00+00:00',
                expiresAt: '2026-10-04T09:10:00+00:00',
            ));

        $decision = (new ConsentAndBreakGlassPrivacyPolicy($repo))->decide(
            $this->context(),
            new DateTimeImmutable('2026-10-04T09:00:00+00:00'),
        );

        self::assertTrue($decision->allowed);
        self::assertSame(PrivacyDecisionReason::BREAK_GLASS->value, $decision->reason);
        self::assertSame('01JBREAKGLASS', $decision->breakGlassAccessId);
    }

    public function test_missing_consent_and_break_glass_denies_access(): void
    {
        $repo = $this->createMock(PrivacyRepo::class);
        $repo->expects(self::once())
            ->method('hasEffectiveConsent')
            ->willReturn(false);
        $repo->expects(self::once())
            ->method('activeBreakGlass')
            ->willReturn(null);

        $decision = (new ConsentAndBreakGlassPrivacyPolicy($repo))->decide(
            $this->context(),
            new DateTimeImmutable('2026-10-04T09:00:00+00:00'),
        );

        self::assertFalse($decision->allowed);
        self::assertSame(PrivacyDecisionReason::NO_EFFECTIVE_CONSENT->value, $decision->reason);
        self::assertNull($decision->breakGlassAccessId);
    }

    private function context(): PrivacyDecisionContext
    {
        return new PrivacyDecisionContext(
            actorUserId: 7,
            organizationId: '01JORG',
            patientId: '01JPATIENT',
            facilityId: '01JFACILITY',
            dataCategory: ConsentDataCategory::DEMOGRAPHICS,
            purpose: ConsentPurpose::TREATMENT,
            recipientClass: ConsentRecipientClass::CARE_TEAM,
        );
    }
}
