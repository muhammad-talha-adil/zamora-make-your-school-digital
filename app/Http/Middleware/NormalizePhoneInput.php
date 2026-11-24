<?php

namespace App\Http\Middleware;

use App\Support\Phone;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Normalises every phone field of a submitted form (`phone`, `father_phone`,
 * `guardians.*.phone` ...) before validation, so "+92 333 1112233" and
 * "03331112233" are both stored as 0333-1112233. Reads (GET) are untouched so
 * search boxes keep what was typed.
 */
class NormalizePhoneInput
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethodSafe()) {
            $request->merge($this->normalize($request->input()));
        }

        return $next($request);
    }

    /**
     * @param  array<int|string, mixed>  $data
     * @return array<int|string, mixed>
     */
    private function normalize(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->normalize($value);
            } elseif (is_string($value) && is_string($key) && preg_match('/(^|_)(phone|mobile|whatsapp)$/', $key)) {
                $data[$key] = Phone::normalize($value) ?? '';
            }
        }

        return $data;
    }
}
