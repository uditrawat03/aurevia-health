<?php

declare(strict_types=1);

namespace App\Application\Patient;

use App\Domains\Patient\Data\PatientData;
use App\Domains\Patient\Data\PatientDuplicateCandidateData;
use App\Domains\Patient\Data\PatientDuplicateCriteriaData;
use App\Domains\Patient\Data\PatientIdentifierData;
use App\Domains\Patient\Enums\DuplicateMatchReason;
use App\Domains\Patient\Repositories\PatientRepo;

final readonly class PatientDuplicateService
{
    private const int EXACT_IDENTIFIER_CONFIDENCE = 100;
    private const int NAME_AND_DOB_CONFIDENCE = 90;
    private const int FAMILY_NAME_AND_DOB_CONFIDENCE = 70;

    public function __construct(private PatientRepo $patients) {}

    /** @return list<PatientDuplicateCandidateData> */
    public function candidates(PatientDuplicateCriteriaData $criteria): array
    {
        $candidates = [];

        foreach ($this->patients->possibleDuplicates($criteria) as $patient) {
            $match = $this->match($patient, $criteria);
            if ($match !== null) {
                $candidates[] = $match;
            }
        }

        usort(
            $candidates,
            static fn (PatientDuplicateCandidateData $left, PatientDuplicateCandidateData $right): int =>
                $right->confidence <=> $left->confidence,
        );

        return $candidates;
    }

    private function match(
        PatientData $patient,
        PatientDuplicateCriteriaData $criteria,
    ): ?PatientDuplicateCandidateData {
        $candidateIdentifierKeys = array_map(
            static fn (PatientIdentifierData $identifier): string =>
                $identifier->type.'|'.mb_strtolower(trim($identifier->system)).'|'.$identifier->normalizedValue,
            $patient->identifiers,
        );
        $hasExactIdentifier = array_intersect(
            $criteria->identifierKeys,
            $candidateIdentifierKeys,
        ) !== [];

        if ($hasExactIdentifier) {
            return $this->candidate(
                patient: $patient,
                confidence: self::EXACT_IDENTIFIER_CONFIDENCE,
                reason: DuplicateMatchReason::EXACT_IDENTIFIER,
            );
        }

        $sameDateOfBirth = $patient->dateOfBirth === $criteria->dateOfBirth;
        $sameGivenName = $patient->normalizedGivenName === $criteria->normalizedGivenName;
        $sameFamilyName = $patient->normalizedFamilyName === $criteria->normalizedFamilyName;

        if ($sameDateOfBirth && $sameGivenName && $sameFamilyName) {
            return $this->candidate(
                patient: $patient,
                confidence: self::NAME_AND_DOB_CONFIDENCE,
                reason: DuplicateMatchReason::NAME_AND_DOB,
            );
        }

        if ($sameDateOfBirth && $sameFamilyName) {
            return $this->candidate(
                patient: $patient,
                confidence: self::FAMILY_NAME_AND_DOB_CONFIDENCE,
                reason: DuplicateMatchReason::FAMILY_NAME_AND_DOB,
            );
        }

        return null;
    }

    private function candidate(
        PatientData $patient,
        int $confidence,
        DuplicateMatchReason $reason,
    ): PatientDuplicateCandidateData {
        return new PatientDuplicateCandidateData(
            patientId: $patient->id,
            displayName: trim($patient->givenName.' '.$patient->familyName),
            dateOfBirth: $patient->dateOfBirth,
            confidence: $confidence,
            reason: $reason->value,
        );
    }
}
