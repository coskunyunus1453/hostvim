<?php

namespace App\Support;

final class DatabaseImportConfirmation
{
    public static function expectedPhrase(): string
    {
        return (string) __('databases.import_confirm_expected');
    }

    /** @return list<string> */
    public static function acceptedPhrases(): array
    {
        return array_values(array_unique([
            self::expectedPhrase(),
            'TÜMVERİSİLİNECEK',
            'TUMVERISILINECEK',
            'REPLACEALLDATA',
            'HOSTVIM_REPLACE_DB',
        ]));
    }

    public static function matches(string $given): bool
    {
        $normalized = self::normalize($given);
        if ($normalized === '') {
            return false;
        }
        foreach (self::acceptedPhrases() as $phrase) {
            if ($normalized === self::normalize($phrase)) {
                return true;
            }
        }

        return false;
    }

    public static function normalize(string $value): string
    {
        $value = mb_strtoupper(trim($value), 'UTF-8');
        $map = [
            'İ' => 'I',
            'I' => 'I',
            'Ü' => 'U',
            'Ö' => 'O',
            'Ş' => 'S',
            'Ğ' => 'G',
            'Ç' => 'C',
        ];

        return strtr($value, $map);
    }

    public static function isSqlUpload(\Illuminate\Http\UploadedFile $upload): bool
    {
        $ext = strtolower((string) $upload->getClientOriginalExtension());
        if ($ext === 'sql') {
            return true;
        }

        $name = strtolower((string) $upload->getClientOriginalName());
        if (str_ends_with($name, '.sql')) {
            return true;
        }

        $mime = strtolower((string) $upload->getMimeType());

        return in_array($mime, [
            'application/sql',
            'text/plain',
            'text/x-sql',
            'application/octet-stream',
            'application/x-sql',
        ], true);
    }
}
