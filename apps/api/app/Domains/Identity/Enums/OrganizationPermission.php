<?php

declare(strict_types=1);

namespace App\Domains\Identity\Enums;

enum OrganizationPermission: string
{
    case VIEW_ORGANIZATION = 'VIEW_ORGANIZATION';
    case MANAGE_ORGANIZATION = 'MANAGE_ORGANIZATION';
    case VIEW_SETTINGS = 'VIEW_SETTINGS';
    case MANAGE_SETTINGS = 'MANAGE_SETTINGS';
    case MANAGE_MEMBERSHIPS = 'MANAGE_MEMBERSHIPS';
}
