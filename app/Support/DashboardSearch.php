<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

final class DashboardSearch
{
    public static function normalize(string $value): string
    {
        $value = strtr(mb_strtolower($value), self::replacements());
        return trim(preg_replace('/\s+/u', ' ', $value));
    }

    /** Search every token across allowlisted columns and relations before limiting rows. */
    public static function apply(Builder $query, string $section, array $columns, array $relations = []): Builder
    {
        $term = request()->input('dash_search.'.$section, '');
        $term = is_string($term) ? self::normalize(mb_substr($term, 0, 120)) : '';
        foreach (array_slice(preg_split('/\s+/u', $term, -1, PREG_SPLIT_NO_EMPTY), 0, 8) as $token) {
            $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $token).'%';
            $query->where(function (Builder $group) use ($columns, $relations, $pattern): void {
                self::columns($group, $columns, $pattern);
                foreach ($relations as $relation => $fields) {
                    $group->orWhereHas($relation, fn (Builder $related) => $related->where(fn (Builder $nested) => self::columns($nested, $fields, $pattern)));
                }
            });
        }
        return $query;
    }

    private static function columns(Builder $query, array $columns, string $pattern): void
    {
        if ($query->getConnection()->getDriverName() === 'sqlite') {
            $query->getConnection()->getPdo()->sqliteCreateFunction('dashboard_normalize', self::normalize(...), 1);
            foreach ($columns as $column) {
                $wrapped = $query->getQuery()->getGrammar()->wrap($column);
                $query->orWhereRaw("dashboard_normalize(COALESCE($wrapped, '')) LIKE ? ESCAPE '!'", [$pattern]);
            }
            return;
        }
        foreach ($columns as $column) {
            $expression = 'LOWER(COALESCE('.$query->getQuery()->getGrammar()->wrap($column).", ''))";
            $bindings = [];
            foreach (self::replacements() as $from => $to) {
                $expression = 'REPLACE('.$expression.', ?, ?)';
                $bindings[] = $from;
                $bindings[] = $to;
            }
            $query->orWhereRaw($expression." LIKE ? ESCAPE '!'", [...$bindings, $pattern]);
        }
    }

    private static function replacements(): array
    {
        return array_combine(
            preg_split('//u', 'يك۰۱۲۳۴۵۶۷۸۹٠١٢٣٤٥٦٧٨٩', -1, PREG_SPLIT_NO_EMPTY),
            preg_split('//u', 'یک01234567890123456789', -1, PREG_SPLIT_NO_EMPTY)
        ) + ["\u{200c}" => ' ', '٬' => '', ',' => ''];
    }
}
