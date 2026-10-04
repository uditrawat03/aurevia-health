<?php

declare(strict_types=1);

namespace App\Domains\Audit\Enums;

use App\Domains\Identity\Enums\OrganizationPermission;

/**
 * Stable audit vocabulary for protected Version 1 application boundaries.
 */
enum AuditAction: string
{
    case CREATE_ORGANIZATION = 'CREATE_ORGANIZATION';
    case VIEW_ORGANIZATION = 'VIEW_ORGANIZATION';
    case MANAGE_ORGANIZATION = 'MANAGE_ORGANIZATION';
    case VIEW_SETTINGS = 'VIEW_SETTINGS';
    case MANAGE_SETTINGS = 'MANAGE_SETTINGS';
    case MANAGE_MEMBERSHIPS = 'MANAGE_MEMBERSHIPS';
    case VIEW_AUDIT = 'VIEW_AUDIT';
    case VIEW_PATIENTS = 'VIEW_PATIENTS';
    case MANAGE_PATIENTS = 'MANAGE_PATIENTS';
    case REVIEW_PATIENT_MERGES = 'REVIEW_PATIENT_MERGES';
    case REGISTER_PATIENT = 'REGISTER_PATIENT';
    case REQUEST_PATIENT_MERGE_REVIEW = 'REQUEST_PATIENT_MERGE_REVIEW';
    case VIEW_CONSENTS = 'VIEW_CONSENTS';
    case MANAGE_CONSENTS = 'MANAGE_CONSENTS';
    case BREAK_GLASS_PATIENT_ACCESS = 'BREAK_GLASS_PATIENT_ACCESS';
    case GRANT_PATIENT_CONSENT = 'GRANT_PATIENT_CONSENT';
    case REVOKE_PATIENT_CONSENT = 'REVOKE_PATIENT_CONSENT';
    case PRIVACY_DECISION = 'PRIVACY_DECISION';
    case ACTIVATE_BREAK_GLASS = 'ACTIVATE_BREAK_GLASS';
    case VIEW_SCHEDULE = 'VIEW_SCHEDULE';
    case MANAGE_SCHEDULE = 'MANAGE_SCHEDULE';
    case MANAGE_SCHEDULING_CONFIGURATION = 'MANAGE_SCHEDULING_CONFIGURATION';
    case BOOK_APPOINTMENT = 'BOOK_APPOINTMENT';
    case RESCHEDULE_APPOINTMENT = 'RESCHEDULE_APPOINTMENT';
    case CANCEL_APPOINTMENT = 'CANCEL_APPOINTMENT';
    case JOIN_WAITLIST = 'JOIN_WAITLIST';
    case CANCEL_WAITLIST = 'CANCEL_WAITLIST';
    case VIEW_ENCOUNTERS = 'VIEW_ENCOUNTERS';
    case MANAGE_ENCOUNTERS = 'MANAGE_ENCOUNTERS';
    case CREATE_ENCOUNTER = 'CREATE_ENCOUNTER';
    case ARRIVE_ENCOUNTER = 'ARRIVE_ENCOUNTER';
    case START_ENCOUNTER = 'START_ENCOUNTER';
    case COMPLETE_ENCOUNTER = 'COMPLETE_ENCOUNTER';
    case CANCEL_ENCOUNTER = 'CANCEL_ENCOUNTER';

    public static function fromOrganizationPermission(OrganizationPermission $permission): self
    {
        return match ($permission) {
            OrganizationPermission::VIEW_ORGANIZATION => self::VIEW_ORGANIZATION,
            OrganizationPermission::MANAGE_ORGANIZATION => self::MANAGE_ORGANIZATION,
            OrganizationPermission::VIEW_SETTINGS => self::VIEW_SETTINGS,
            OrganizationPermission::MANAGE_SETTINGS => self::MANAGE_SETTINGS,
            OrganizationPermission::MANAGE_MEMBERSHIPS => self::MANAGE_MEMBERSHIPS,
            OrganizationPermission::VIEW_AUDIT => self::VIEW_AUDIT,
            OrganizationPermission::VIEW_PATIENTS => self::VIEW_PATIENTS,
            OrganizationPermission::MANAGE_PATIENTS => self::MANAGE_PATIENTS,
            OrganizationPermission::REVIEW_PATIENT_MERGES => self::REVIEW_PATIENT_MERGES,
            OrganizationPermission::VIEW_CONSENTS => self::VIEW_CONSENTS,
            OrganizationPermission::MANAGE_CONSENTS => self::MANAGE_CONSENTS,
            OrganizationPermission::BREAK_GLASS_PATIENT_ACCESS => self::BREAK_GLASS_PATIENT_ACCESS,
            OrganizationPermission::VIEW_SCHEDULE => self::VIEW_SCHEDULE,
            OrganizationPermission::MANAGE_SCHEDULE => self::MANAGE_SCHEDULE,
            OrganizationPermission::MANAGE_SCHEDULING_CONFIGURATION => self::MANAGE_SCHEDULING_CONFIGURATION,
            OrganizationPermission::VIEW_ENCOUNTERS => self::VIEW_ENCOUNTERS,
            OrganizationPermission::MANAGE_ENCOUNTERS => self::MANAGE_ENCOUNTERS,
        };
    }
}
