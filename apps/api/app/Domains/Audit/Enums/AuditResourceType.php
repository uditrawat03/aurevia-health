<?php

declare(strict_types=1);

namespace App\Domains\Audit\Enums;

enum AuditResourceType: string
{
    case ORGANIZATION = 'ORGANIZATION';
    case FACILITY = 'FACILITY';
    case OPERATIONAL_SETTINGS = 'OPERATIONAL_SETTINGS';
    case ORGANIZATION_MEMBERSHIP = 'ORGANIZATION_MEMBERSHIP';
    case AUDIT_EVENT = 'AUDIT_EVENT';
    case PATIENT = 'PATIENT';
    case PATIENT_MERGE_REVIEW = 'PATIENT_MERGE_REVIEW';
}
