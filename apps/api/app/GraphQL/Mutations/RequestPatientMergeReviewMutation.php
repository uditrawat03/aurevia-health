<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Application\Audit\AuditService;
use App\Application\Identity\OrganizationAuthorizationService;
use App\Application\Patient\PatientMergeReviewService;
use App\Domains\Audit\Enums\AuditAction;
use App\Domains\Audit\Enums\AuditResourceType;
use App\Domains\Identity\Enums\OrganizationPermission;
use App\Domains\Patient\Data\PatientMergeReviewData;
use App\Domains\Patient\Data\RequestPatientMergeReviewData;

final readonly class RequestPatientMergeReviewMutation
{
    public function __construct(
        private PatientMergeReviewService $reviews,
        private OrganizationAuthorizationService $authorization,
        private AuditService $audit,
    ) {}

    /** @param array{input: array{organizationId: string, sourcePatientId: string, targetPatientId: string, reason?: string|null}} $args */
    public function __invoke(mixed $root, array $args): PatientMergeReviewData
    {
        $input = $args['input'];

        $this->authorization->authorize(
            organizationId: $input['organizationId'],
            permission: OrganizationPermission::REVIEW_PATIENT_MERGES,
            requiresAllFacilities: true,
            patientId: $input['targetPatientId'],
        );

        $actorUserId = $this->authorization->authenticatedUserId();
        $review = $this->reviews->request(new RequestPatientMergeReviewData(
            organizationId: $input['organizationId'],
            sourcePatientId: $input['sourcePatientId'],
            targetPatientId: $input['targetPatientId'],
            requestedByUserId: $actorUserId,
            reason: $input['reason'] ?? null,
        ));

        $this->audit->recordPatientOperation(
            actorUserId: $actorUserId,
            organizationId: $input['organizationId'],
            facilityId: null,
            patientId: $input['targetPatientId'],
            action: AuditAction::REQUEST_PATIENT_MERGE_REVIEW,
            resourceType: AuditResourceType::PATIENT_MERGE_REVIEW,
            resourceId: $review->id,
        );

        return $review;
    }
}
