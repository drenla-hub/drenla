<?php

namespace App\Support;

/**
 * The fixed catalog of permission keys the codebase actually checks. Roles are
 * fully admin-customizable (see App\Models\Role); permissions are not — a
 * permission only exists here once something in the code gates on it. Add a key
 * here (and wire the check somewhere) before a role's permissions array can
 * meaningfully reference it.
 *
 * Each module has a VIEW and a MANAGE key. "Manage implies view" — routes that
 * only read data accept either key; routes that write require MANAGE specifically.
 * See collaboration-notes.md "Module-level permission system" decision.
 */
class Permission
{
    public const ACCESS_ADMIN = 'access_admin';

    public const VIEW_USERS = 'view_users';

    public const MANAGE_USERS = 'manage_users';

    public const VIEW_CONTENT = 'view_content';

    public const MANAGE_CONTENT = 'manage_content';

    public const VIEW_MEDIA = 'view_media';

    public const MANAGE_MEDIA = 'manage_media';

    public const VIEW_CLIENTS = 'view_clients';

    public const MANAGE_CLIENTS = 'manage_clients';

    public const VIEW_PROPOSALS = 'view_proposals';

    public const MANAGE_PROPOSALS = 'manage_proposals';

    public const VIEW_PROJECTS = 'view_projects';

    public const MANAGE_PROJECTS = 'manage_projects';

    public const VIEW_FINANCE = 'view_finance';

    public const MANAGE_FINANCE = 'manage_finance';

    public const VIEW_SETTINGS = 'view_settings';

    public const MANAGE_SETTINGS = 'manage_settings';

    public const VIEW_CHAT = 'view_chat';

    public const MANAGE_CHAT = 'manage_chat';

    /** @return array<string, string> permission key => human label, for the role-editing UI */
    public static function catalog(): array
    {
        return [
            self::ACCESS_ADMIN => 'Access the admin panel',

            self::VIEW_USERS => 'View staff users and roles',
            self::MANAGE_USERS => 'Manage staff users and roles',

            self::VIEW_CONTENT => 'View content (homepage, case studies, articles, focus areas)',
            self::MANAGE_CONTENT => 'Manage content (homepage, case studies, articles, focus areas)',

            self::VIEW_MEDIA => 'View media library',
            self::MANAGE_MEDIA => 'Manage media library',

            self::VIEW_CLIENTS => 'View clients and inquiries',
            self::MANAGE_CLIENTS => 'Manage clients and inquiries',

            self::VIEW_PROPOSALS => 'View proposals',
            self::MANAGE_PROPOSALS => 'Manage proposals',

            self::VIEW_PROJECTS => 'View projects',
            self::MANAGE_PROJECTS => 'Manage projects',

            self::VIEW_FINANCE => 'View finance documents',
            self::MANAGE_FINANCE => 'Manage finance documents',

            self::VIEW_SETTINGS => 'View site settings',
            self::MANAGE_SETTINGS => 'Manage site settings',

            self::VIEW_CHAT => 'View AI chat conversations',
            self::MANAGE_CHAT => 'Manage (and delete) AI chat conversations',
        ];
    }
}
