<?php

declare(strict_types=1);

namespace App\Domains\Patient\Data;

use App\Domains\Patient\Enums\PatientAddressUse;

final readonly class PatientAddressInputData
{
    public function __construct(
        public PatientAddressUse $use,
        public string $line1,
        public ?string $line2,
        public string $city,
        public ?string $region,
        public ?string $postalCode,
        public string $countryCode,
        public bool $preferred,
    ) {}
}
