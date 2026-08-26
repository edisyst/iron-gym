<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Impostazioni globali key/value.
 *
 * Usata per i valori che valgono per l'intera palestra e non per singolo
 * utente — in particolare i feature flag globali, che Pennant da solo
 * risolverebbe per-scope memorizzando una riga per utente.
 */
class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    protected $casts = ['value' => 'array'];

    private const CACHE_PREFIX = 'setting:';

    /**
     * Legge un valore; null se la chiave non esiste.
     */
    public static function read(string $key, mixed $default = null): mixed
    {
        $cached = Cache::rememberForever(
            self::CACHE_PREFIX.$key,
            fn () => ['v' => self::query()->find($key)?->value]
        );

        return $cached['v'] ?? $default;
    }

    /**
     * Legge un valore booleano.
     */
    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::read($key);

        return $value === null ? $default : (bool) $value;
    }

    /**
     * Scrive un valore e invalida la cache.
     */
    public static function write(string $key, mixed $value): void
    {
        self::query()->updateOrCreate(['key' => $key], ['value' => $value]);

        Cache::forget(self::CACHE_PREFIX.$key);
    }
}
