<?php

namespace Modules\Search\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * The one place free-text matching branches on the database driver. MySQL uses
 * the FULLTEXT index on the given normalized column; every other driver (SQLite
 * in tests) falls back to an AND of per-token LIKE. A future dedicated engine
 * would replace this class and the two search services that call it.
 */
class FullTextMatch
{
    /**
     * Constrain the query to rows whose `$column` matches the normalized term.
     *
     * @param  Builder<Model>  $query
     */
    public function constrain(Builder $query, string $column, string $term): void
    {
        if ($this->usesFullText()) {
            $query->whereRaw("MATCH({$column}) AGAINST (? IN BOOLEAN MODE)", [$this->booleanTerm($term)]);

            return;
        }

        foreach (explode(' ', $term) as $token) {
            $query->where($column, 'like', '%'.$this->escapeLike($token).'%');
        }
    }

    /**
     * Order the query by descending relevance to the term. On non-FULLTEXT
     * drivers this degrades to "prefix hits first".
     *
     * @param  Builder<Model>  $query
     */
    public function orderByRelevance(Builder $query, string $column, string $term): void
    {
        if ($this->usesFullText()) {
            $query->orderByRaw("MATCH({$column}) AGAINST (? IN BOOLEAN MODE) DESC", [$this->booleanTerm($term)]);

            return;
        }

        $query->orderByRaw("CASE WHEN {$column} LIKE ? THEN 0 ELSE 1 END", [$this->escapeLike($term).'%']);
    }

    public function usesFullText(): bool
    {
        return DB::getDriverName() === 'mysql';
    }

    /**
     * Turn a normalized term into a safe FULLTEXT boolean-mode expression:
     * operators stripped, every token a required prefix match.
     */
    private function booleanTerm(string $term): string
    {
        $tokens = array_filter(explode(' ', preg_replace('/[+\-><()~*"@]+/', ' ', $term) ?? $term));

        return implode(' ', array_map(fn (string $token): string => '+'.$token.'*', $tokens));
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
