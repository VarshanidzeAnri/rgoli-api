<?php

use App\Enums\FileType;
use App\Enums\Status;
use App\Support\PermissionCatalog;

it('detects file types from MIME and extension', function (string $mime, string $ext, FileType $expected): void {
    expect(FileType::detect($mime, $ext))->toBe($expected);
})->with([
    ['application/pdf', 'pdf', FileType::Pdf],
    ['application/octet-stream', 'DWG', FileType::Dwg],
    ['image/vnd.dwg', 'dwg', FileType::Dwg],
    ['image/png', 'png', FileType::Image],
    ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'docx', FileType::Doc],
    ['application/zip', 'zip', FileType::Zip],
    ['application/x-msdownload', 'exe', FileType::Other],
]);

it('translates enum labels', function (): void {
    app()->setLocale('ka');
    expect(Status::Todo->label())->toBe('დასაწყები');

    app()->setLocale('ru');
    expect(Status::Done->label())->toBe('Готово');
});

it('keeps the permission catalog consistent', function (): void {
    $all = PermissionCatalog::all();

    expect($all)->toHaveCount(31)->and(array_unique($all))->toHaveCount(31);

    foreach (PermissionCatalog::roles() as $slug => $role) {
        expect(array_diff($role['permissions'], $all))->toBe([], "Role {$slug} references unknown permissions");
    }
});

it('translates every permission and group in every language', function (string $locale): void {
    app()->setLocale($locale);

    foreach (PermissionCatalog::groups() as $group => $permissions) {
        expect(__('permissions.groups.'.$group))->not->toBe('permissions.groups.'.$group);
        foreach ($permissions as $permission) {
            expect(__('permissions.names.'.$permission))->not->toBe('permissions.names.'.$permission);
        }
    }
})->with(['ka', 'en', 'ru']);

it('has the same message keys in every language', function (): void {
    $flatten = function (array $array, string $prefix = '') use (&$flatten): array {
        $keys = [];
        foreach ($array as $key => $value) {
            $keys = is_array($value) ? [...$keys, ...$flatten($value, $prefix.$key.'.')] : [...$keys, $prefix.$key];
        }
        sort($keys);

        return $keys;
    };

    foreach (['messages', 'enums', 'permissions', 'attributes'] as $file) {
        $ka = $flatten(require lang_path("ka/{$file}.php"));
        expect($flatten(require lang_path("en/{$file}.php")))->toBe($ka, "en/{$file}.php keys differ")
            ->and($flatten(require lang_path("ru/{$file}.php")))->toBe($ka, "ru/{$file}.php keys differ");
    }
});
