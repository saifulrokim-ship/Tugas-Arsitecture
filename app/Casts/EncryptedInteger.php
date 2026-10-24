<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use InvalidArgumentException;

/**
 * Menyimpan nilai integer dalam keadaan terenkripsi di database (PRD RES-4).
 *
 * Nilai hanya disimpan (tidak pernah di-log, tidak diserialisasi ke JSON view),
 * dan dibaca kembali sebagai integer utuh.
 *
 * @implements CastsAttributes<int|null, string|null>
 */
class EncryptedInteger implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) Crypt::decryptString((string) $value);
    }

    /**
     * @param  array<string, mixed>  $attributes
     *
     * @throws InvalidArgumentException
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            throw new InvalidArgumentException("Field {$key} harus berupa angka bulat.");
        }

        return Crypt::encryptString((string) (int) $value);
    }
}
