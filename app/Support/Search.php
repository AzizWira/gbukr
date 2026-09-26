<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

final class Search
{
    public static function term(?string $value): string
    {
        return trim((string) $value);
    }

    public static function compact(string $value): string
    {
        return mb_strtolower((string) preg_replace('/[^\pL\pN]+/u', '', $value));
    }

    public static function code(Builder $query, string $column, string $term): Builder
    {
        $compact = self::compact($term);
        $lower = mb_strtolower($term);

        return $query->where(function (Builder $sub) use ($column, $term, $compact, $lower) {
            $sub->where($column, 'like', '%' . $term . '%')
                ->orWhereRaw('LOWER(' . $column . ') = ?', [$lower]);

            if ($compact !== '') {
                $sub->orWhereRaw(
                    "LOWER(REPLACE(REPLACE(REPLACE($column, '-', ''), ' ', ''), '_', '')) LIKE ?",
                    ['%' . $compact . '%']
                );
            }
        });
    }
}
