<?php

declare(strict_types=1);

namespace App\Domains\Patient\Data;

final readonly class PatientAddressData
{
    public function __construct(
        public string $id,
        public string $use,
        public string $line1,
        public ?string $line2,
        public string $city,
        public ?string $region,
        public ?string $postalCode,
        public string $countryCode,
        public bool $preferred,
    ) {}
}
