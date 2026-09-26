<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Batch extends Model
{
    protected $fillable = [
        'go_group_id',
        'country_id',
        'warehouse_id',
        'code',
        'name',
        'description',
        'status',
        'tracking_number',
        'arrived_gbu_at',
    ];

    protected function casts(): array
    {
        return [
            'arrived_gbu_at' => 'datetime',
        ];
    }

    /**
     * Kode Batch untuk data yang dibuat langsung dari web.
     *
     * Format: COUNTRY-CONTEXT-SEQUENCE
     * Contoh: KR-ENHYPEN-001, US-KRJASTIP-002.
     * CONTEXT memakai nama GO bila tersedia, lalu nama Batch sebagai fallback.
     * Kode legacy hasil import tetap memakai format legacy dan tidak diubah.
     */
    public static function generateCode(string $countryCode, ?string $context = null): string
    {
        $countryCode = strtoupper(trim($countryCode));
        $context = trim((string) $context);
        $token = Str::upper(Str::slug($context !== '' ? $context : 'GBUKR', '-'));
        $token = preg_replace('/[^A-Z0-9-]+/', '', $token) ?: 'GBUKR';
        $token = trim($token, '-');
        $token = substr($token, 0, 14) ?: 'GBUKR';

        $prefix = $countryCode . '-' . $token . '-';

        $last = static::query()
            ->where('code', 'like', $prefix . '%')
            ->orderByDesc('code')
            ->value('code');

        $next = 1;
        if ($last && preg_match('/-(\d+)$/', $last, $matches)) {
            $next = ((int) $matches[1]) + 1;
        }

        do {
            $code = $prefix . str_pad((string) $next, 3, '0', STR_PAD_LEFT);
            $next++;
        } while (static::where('code', $code)->exists());

        return $code;
    }

    public function goGroup()
    {
        return $this->belongsTo(GoGroup::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function shipment()
    {
        return $this->hasOne(Shipment::class, 'source_id')
            ->where('source_type', 'batch');
    }
}
