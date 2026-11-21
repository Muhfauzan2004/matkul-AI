<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

abstract class Controller
{
    /**
     * Return case-insensitive LIKE operator for the active database driver.
     */
    protected function likeOperator(): string
    {
        return DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
    }

    /**
     * Apply case-insensitive multi-term search across the given columns.
     */
    protected function applySearchFilter($query, string $keyword, array $columns): void
    {
        $terms = preg_split('/\s+/u', trim($keyword), -1, PREG_SPLIT_NO_EMPTY);
        if (empty($terms) || empty($columns)) {
            return;
        }

        $op = $this->likeOperator();

        foreach ($terms as $term) {
            $pattern = '%' . $term . '%';
            $query->where(function ($sub) use ($columns, $op, $pattern) {
                foreach ($columns as $idx => $col) {
                    if ($idx === 0) {
                        $sub->where($col, $op, $pattern);
                    } else {
                        $sub->orWhere($col, $op, $pattern);
                    }
                }
            });
        }
    }
}
