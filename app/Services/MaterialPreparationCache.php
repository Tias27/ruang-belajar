<?php

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\Cache;

class MaterialPreparationCache
{
    public function remember(string $operation, string $input, Closure $prepare): mixed
    {
        // Content-addressed keys invalidate automatically when material changes.
        $key = 'material-preparation:v1:'.hash('sha256', $operation."\0".$input);
        try {
            $cached = Cache::get($key);
            if ($cached !== null) {
                return $cached;
            }
        } catch (\Throwable $exception) {
            report($exception);
        }

        $value = $prepare();
        try {
            Cache::put($key, $value, now()->addHours(6));
        } catch (\Throwable $exception) {
            report($exception);
        }

        return $value;
    }
}
