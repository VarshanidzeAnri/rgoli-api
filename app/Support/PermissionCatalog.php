<?php

namespace App\Support;

use RuntimeException;

/**
 * Reads resources/permissions.json, the single source of truth for permissions and default roles.
 */
final class PermissionCatalog
{
    public const OWNER = 'owner';

    /** @var array{groups: array<string, list<string>>, roles: array<string, array{name: string, permissions: list<string>|string}>}|null */
    private static ?array $data = null;

    /**
     * @return array{groups: array<string, list<string>>, roles: array<string, array{name: string, permissions: list<string>|string}>}
     */
    private static function data(): array
    {
        if (self::$data === null) {
            $raw = file_get_contents(resource_path('permissions.json'));
            if ($raw === false) {
                throw new RuntimeException('permissions.json is missing.');
            }

            /** @var array{groups: array<string, list<string>>, roles: array<string, array{name: string, permissions: list<string>|string}>} $decoded */
            $decoded = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
            self::$data = $decoded;
        }

        return self::$data;
    }

    /**
     * @return array<string, list<string>>
     */
    public static function groups(): array
    {
        return self::data()['groups'];
    }

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return array_values(array_merge(...array_values(self::groups())));
    }

    /**
     * @return array<string, array{name: string, permissions: list<string>}>
     */
    public static function roles(): array
    {
        $roles = [];
        foreach (self::data()['roles'] as $slug => $role) {
            $roles[$slug] = [
                'name' => $role['name'],
                'permissions' => $role['permissions'] === '*' ? self::all() : (array) $role['permissions'],
            ];
        }

        return $roles;
    }
}
