<?php

namespace App\Support;

use Illuminate\Http\Request;

final class Listing
{
    public const PER_PAGE = [20, 50, 100];

    public static function perPage(Request $request, int $default = 20): int
    {
        $value = (int) $request->query('per_page', $default);
        return in_array($value, self::PER_PAGE, true) ? $value : $default;
    }
}
