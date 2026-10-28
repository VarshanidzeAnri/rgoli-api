<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum FileType: string
{
    use HasLabel;

    case Pdf = 'pdf';
    case Dwg = 'dwg';
    case Image = 'image';
    case Doc = 'doc';
    case Zip = 'zip';
    case Other = 'other';

    public static function translationKey(): string
    {
        return 'file_type';
    }

    /**
     * Detect the type from the server-side MIME type and extension.
     */
    public static function detect(string $mime, string $extension): self
    {
        $extension = strtolower($extension);

        return match (true) {
            $mime === 'application/pdf' => self::Pdf,
            $extension === 'dwg' || in_array($mime, ['image/vnd.dwg', 'application/acad', 'application/x-dwg'], true) => self::Dwg,
            str_starts_with($mime, 'image/') => self::Image,
            in_array($extension, ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'txt', 'csv'], true) => self::Doc,
            in_array($mime, ['application/zip', 'application/x-zip-compressed', 'application/x-7z-compressed', 'application/x-rar-compressed', 'application/vnd.rar'], true) => self::Zip,
            default => self::Other,
        };
    }
}
