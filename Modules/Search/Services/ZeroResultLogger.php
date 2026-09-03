<?php

namespace Modules\Search\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Modules\Search\Models\SearchLog;
use Modules\Search\Support\SearchNormalizer;
use Throwable;

/**
 * Writes a {@see SearchLog} row when — and only when — a free-text search
 * returned zero results (US-SRC-11). Logging must never break the response, so
 * every failure is swallowed.
 */
class ZeroResultLogger
{
    public function __construct(private SearchNormalizer $normalizer) {}

    public function record(?string $term, LengthAwarePaginator $results, string $context, ?int $userId): void
    {
        $term = trim((string) $term);

        if ($term === '' || $results->total() > 0) {
            return;
        }

        try {
            SearchLog::create([
                'term' => mb_substr($term, 0, 255),
                'normalized_term' => mb_substr($this->normalizer->normalize($term), 0, 255),
                'result_count' => 0,
                'user_id' => $userId,
                'context' => $context,
            ]);
        } catch (Throwable $e) {
            Log::warning('Failed to record zero-result search', ['exception' => $e->getMessage()]);
        }
    }
}
