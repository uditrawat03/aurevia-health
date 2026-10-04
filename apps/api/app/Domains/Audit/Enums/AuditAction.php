<?php

declare(strict_types=1);

namespace App\Domains\Audit\Enums;

use App\Domains\Identity\Enums\OrganizationPermission;

/**
 * Stable audit vocabulary for the Version 1 protected organization boundary.
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

    public static function fromOrganizationPermission(OrganizationPermission $permission): self
    {
        return match ($permission) {
            OrganizationPermission::VIEW_ORGANIZATION => self::VIEW_ORGANIZATION,
            OrganizationPermission::MANAGE_ORGANIZATION => self::MANAGE_ORGANIZATION,
            OrganizationPermission::VIEW_SETTINGS => self::VIEW_SETTINGS,
            OrganizationPermission::MANAGE_SETTINGS => self::MANAGE_SETTINGS,
            OrganizationPermission::MANAGE_MEMBERSHIPS => self::MANAGE_MEMBERSHIPS,
            OrganizationPermission::VIEW_AUDIT => self::VIEW_AUDIT,
        };
    }
}
