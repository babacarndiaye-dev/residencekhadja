<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class PublicHoliday extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['date' => 'date'];

    /**
     * Ensemble des jours fériés (Y-m-d) sur une plage : fêtes fixes de
     * config('hr.fixed_holidays') + fêtes mobiles saisies en base. Mis en cache.
     *
     * @return array<string, string> date => nom
     */
    public static function map(string|Carbon $from, string|Carbon $to): array
    {
        $from = Carbon::parse($from)->startOfDay();
        $to = Carbon::parse($to)->startOfDay();

        $out = [];
        foreach (range((int) $from->year, (int) $to->year) as $year) {
            foreach (config('hr.fixed_holidays', []) as $h) {
                $d = Carbon::create($year, $h['month'], $h['day']);
                if ($d->gte($from) && $d->lte($to)) {
                    $out[$d->toDateString()] = $h['name'];
                }
            }
        }

        if (Schema::hasTable('public_holidays')) {
            $rows = Cache::remember('public_holidays.all', 3600, fn () => static::query()->get(['date', 'name']));
            foreach ($rows as $row) {
                $d = $row->date->toDateString();
                if ($d >= $from->toDateString() && $d <= $to->toDateString()) {
                    $out[$d] = $row->name;
                }
            }
        }

        return $out;
    }

    public static function isHoliday(string|Carbon $date): bool
    {
        $d = Carbon::parse($date);

        return array_key_exists($d->toDateString(), self::map($d, $d));
    }

    protected static function booted(): void
    {
        $flush = fn () => Cache::forget('public_holidays.all');
        static::saved($flush);
        static::deleted($flush);
    }
}
