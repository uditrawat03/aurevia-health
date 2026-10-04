<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Patient;

use App\Domains\Patient\Data\PatientAddressData;
use App\Domains\Patient\Data\PatientContactData;
use App\Domains\Patient\Data\PatientData;
use App\Domains\Patient\Data\PatientDuplicateCriteriaData;
use App\Domains\Patient\Data\PatientIdentifierData;
use App\Domains\Patient\Data\PatientMergeReviewData;
use App\Domains\Patient\Data\PatientRelationshipData;
use App\Domains\Patient\Data\PatientSearchCriteriaData;
use App\Domains\Patient\Data\PatientSearchResultData;
use App\Domains\Patient\Data\PersistPatientData;
use App\Domains\Patient\Data\RequestPatientMergeReviewData;
use App\Domains\Patient\Enums\PatientMergeReviewStatus;
use App\Domains\Patient\Repositories\PatientRepo;
use App\Models\Patient;
use App\Models\PatientAddress;
use App\Models\PatientContact;
use App\Models\PatientIdentifier;
use App\Models\PatientMergeReview;
use App\Models\PatientRelationship;
use Carbon\CarbonInterface;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Builder;
use RuntimeException;

final readonly class EloquentPatientRepo implements PatientRepo
{
    private const int MAX_DUPLICATE_CANDIDATES = 25;

    /** @var list<string> */
    private const array PATIENT_RELATIONS = [
        'identifiers',
        'contacts',
        'addresses',
        'relationships',
    ];

    public function __construct(private DatabaseManager $database) {}

    public function create(PersistPatientData $data): PatientData
    {
        return $this->database->connection()->transaction(function () use ($data): PatientData {
            $patient = Patient::query()->create([
                'organization_id' => $data->organizationId,
                'registration_facility_id' => $data->registrationFacilityId,
                'given_name' => $data->givenName,
                'normalized_given_name' => $data->normalizedGivenName,
                'middle_name' => $data->middleName,
                'family_name' => $data->familyName,
                'normalized_family_name' => $data->normalizedFamilyName,
                'preferred_name' => $data->preferredName,
                'date_of_birth' => $data->dateOfBirth,
                'sex_at_birth' => $data->sexAtBirth->value,
            ]);

            foreach ($data->identifiers as $identifier) {
                PatientIdentifier::query()->create([
                    'organization_id' => $data->organizationId,
                    'patient_id' => $patient->getKey(),
                    'type' => $identifier->type->value,
                    'system' => $identifier->system,
                    'value' => $identifier->value,
                    'normalized_value' => $identifier->normalizedValue,
                ]);
            }

            foreach ($data->contacts as $contact) {
                PatientContact::query()->create([
                    'patient_id' => $patient->getKey(),
                    'type' => $contact->type->value,
                    'value' => trim($contact->value),
                    'preferred' => $contact->preferred,
                ]);
            }

            foreach ($data->addresses as $address) {
                PatientAddress::query()->create([
                    'patient_id' => $patient->getKey(),
                    'use' => $address->use->value,
                    'line1' => trim($address->line1),
                    'line2' => $address->line2 === null ? null : trim($address->line2),
                    'city' => trim($address->city),
                    'region' => $address->region === null ? null : trim($address->region),
                    'postal_code' => $address->postalCode === null ? null : trim($address->postalCode),
                    'country_code' => mb_strtoupper(trim($address->countryCode)),
                    'preferred' => $address->preferred,
                ]);
            }

            foreach ($data->relationships as $relationship) {
                PatientRelationship::query()->create([
                    'patient_id' => $patient->getKey(),
                    'type' => $relationship->type->value,
                    'name' => trim($relationship->name),
                    'phone' => $relationship->phone === null ? null : trim($relationship->phone),
                    'email' => $relationship->email === null ? null : mb_strtolower(trim($relationship->email)),
                    'legal_guardian' => $relationship->legalGuardian,
                    'emergency_contact' => $relationship->emergencyContact,
                ]);
            }

            $patient->load(self::PATIENT_RELATIONS);

            return $this->mapPatient($patient);
        });
    }

    public function find(string $organizationId, string $patientId): ?PatientData
    {
        $patient = Patient::query()
            ->with(self::PATIENT_RELATIONS)
            ->where('organization_id', $organizationId)
            ->whereKey($patientId)
            ->first();

        return $patient instanceof Patient ? $this->mapPatient($patient) : null;
    }

    public function search(PatientSearchCriteriaData $criteria): PatientSearchResultData
    {
        $query = Patient::query()
            ->with(self::PATIENT_RELATIONS)
            ->where('organization_id', $criteria->organizationId);

        if ($criteria->facilityId !== null) {
            $query->where('registration_facility_id', $criteria->facilityId);
        }

        $query->where(function (Builder $builder) use ($criteria): void {
            $namePattern = '%'.$criteria->normalizedText.'%';
            $identifierPattern = '%'.$criteria->normalizedIdentifier.'%';

            $builder
                ->where('normalized_given_name', 'like', $namePattern)
                ->orWhere('normalized_family_name', 'like', $namePattern)
                ->orWhereHas('identifiers', static function (Builder $identifierQuery) use ($identifierPattern): void {
                    $identifierQuery->where('normalized_value', 'like', $identifierPattern);
                });
        });

        $total = (clone $query)->count();

        $models = $query
            ->orderBy('normalized_family_name')
            ->orderBy('normalized_given_name')
            ->limit($criteria->limit)
            ->get();

        $items = [];
        foreach ($models as $model) {
            if ($model instanceof Patient) {
                $items[] = $this->mapPatient($model);
            }
        }

        return new PatientSearchResultData(items: $items, total: $total);
    }

    public function possibleDuplicates(PatientDuplicateCriteriaData $criteria): array
    {
        $query = Patient::query()
            ->with(self::PATIENT_RELATIONS)
            ->where('organization_id', $criteria->organizationId)
            ->where(function (Builder $builder) use ($criteria): void {
                $builder->where(function (Builder $nameQuery) use ($criteria): void {
                    $nameQuery
                        ->whereDate('date_of_birth', '=', $criteria->dateOfBirth)
                        ->where('normalized_family_name', $criteria->normalizedFamilyName);
                });

                if ($criteria->normalizedIdentifiers !== []) {
                    $builder->orWhereHas(
                        'identifiers',
                        static function (Builder $identifierQuery) use ($criteria): void {
                            $identifierQuery->whereIn(
                                'normalized_value',
                                $criteria->normalizedIdentifiers,
                            );
                        },
                    );
                }
            })
            ->limit(self::MAX_DUPLICATE_CANDIDATES)
            ->get();

        $patients = [];
        foreach ($query as $model) {
            if ($model instanceof Patient) {
                $patients[] = $this->mapPatient($model);
            }
        }

        return $patients;
    }

    public function createMergeReview(RequestPatientMergeReviewData $data): PatientMergeReviewData
    {
        $review = PatientMergeReview::query()->create([
            'organization_id' => $data->organizationId,
            'source_patient_id' => $data->sourcePatientId,
            'target_patient_id' => $data->targetPatientId,
            'status' => PatientMergeReviewStatus::PENDING->value,
            'requested_by_user_id' => $data->requestedByUserId,
            'reviewed_by_user_id' => null,
            'reason' => $data->reason,
        ]);

        return $this->mapMergeReview($review);
    }

    private function mapPatient(Patient $patient): PatientData
    {
        $dateOfBirth = $patient->getAttribute('date_of_birth');
        if (! $dateOfBirth instanceof CarbonInterface) {
            throw new RuntimeException('Patient date of birth is invalid.');
        }

        $identifiers = [];
        foreach ($patient->identifiers as $identifier) {
            if ($identifier instanceof PatientIdentifier) {
                $identifiers[] = new PatientIdentifierData(
                    id: (string) $identifier->getKey(),
                    type: (string) $identifier->getAttribute('type'),
                    system: (string) $identifier->getAttribute('system'),
                    value: (string) $identifier->getAttribute('value'),
                    normalizedValue: (string) $identifier->getAttribute('normalized_value'),
                );
            }
        }

        $contacts = [];
        foreach ($patient->contacts as $contact) {
            if ($contact instanceof PatientContact) {
                $contacts[] = new PatientContactData(
                    id: (string) $contact->getKey(),
                    type: (string) $contact->getAttribute('type'),
                    value: (string) $contact->getAttribute('value'),
                    preferred: (bool) $contact->getAttribute('preferred'),
                );
            }
        }

        $addresses = [];
        foreach ($patient->addresses as $address) {
            if ($address instanceof PatientAddress) {
                $addresses[] = new PatientAddressData(
                    id: (string) $address->getKey(),
                    use: (string) $address->getAttribute('use'),
                    line1: (string) $address->getAttribute('line1'),
                    line2: $this->nullableString($address->getAttribute('line2')),
                    city: (string) $address->getAttribute('city'),
                    region: $this->nullableString($address->getAttribute('region')),
                    postalCode: $this->nullableString($address->getAttribute('postal_code')),
                    countryCode: (string) $address->getAttribute('country_code'),
                    preferred: (bool) $address->getAttribute('preferred'),
                );
            }
        }

        $relationships = [];
        foreach ($patient->relationships as $relationship) {
            if ($relationship instanceof PatientRelationship) {
                $relationships[] = new PatientRelationshipData(
                    id: (string) $relationship->getKey(),
                    type: (string) $relationship->getAttribute('type'),
                    name: (string) $relationship->getAttribute('name'),
                    phone: $this->nullableString($relationship->getAttribute('phone')),
                    email: $this->nullableString($relationship->getAttribute('email')),
                    legalGuardian: (bool) $relationship->getAttribute('legal_guardian'),
                    emergencyContact: (bool) $relationship->getAttribute('emergency_contact'),
                );
            }
        }

        return new PatientData(
            id: (string) $patient->getKey(),
            organizationId: (string) $patient->getAttribute('organization_id'),
            registrationFacilityId: (string) $patient->getAttribute('registration_facility_id'),
            givenName: (string) $patient->getAttribute('given_name'),
            normalizedGivenName: (string) $patient->getAttribute('normalized_given_name'),
            middleName: $this->nullableString($patient->getAttribute('middle_name')),
            familyName: (string) $patient->getAttribute('family_name'),
            normalizedFamilyName: (string) $patient->getAttribute('normalized_family_name'),
            preferredName: $this->nullableString($patient->getAttribute('preferred_name')),
            dateOfBirth: $dateOfBirth->format('Y-m-d'),
            sexAtBirth: (string) $patient->getAttribute('sex_at_birth'),
            identifiers: $identifiers,
            contacts: $contacts,
            addresses: $addresses,
            relationships: $relationships,
        );
    }

    private function mapMergeReview(PatientMergeReview $review): PatientMergeReviewData
    {
        $createdAt = $review->getAttribute('created_at');
        if (! $createdAt instanceof CarbonInterface) {
            throw new RuntimeException('Patient merge-review timestamp is invalid.');
        }

        $reviewedByUserId = $review->getAttribute('reviewed_by_user_id');

        return new PatientMergeReviewData(
            id: (string) $review->getKey(),
            organizationId: (string) $review->getAttribute('organization_id'),
            sourcePatientId: (string) $review->getAttribute('source_patient_id'),
            targetPatientId: (string) $review->getAttribute('target_patient_id'),
            status: (string) $review->getAttribute('status'),
            requestedByUserId: (int) $review->getAttribute('requested_by_user_id'),
            reviewedByUserId: is_int($reviewedByUserId) ? $reviewedByUserId : null,
            reason: $this->nullableString($review->getAttribute('reason')),
            createdAt: $createdAt->toIso8601String(),
        );
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) ? $value : null;
    }
}
