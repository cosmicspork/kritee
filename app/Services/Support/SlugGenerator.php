<?php

namespace App\Services\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Generates a slug that is unique within a model's table. The base string is
 * slugified, then a numeric suffix (`-2`, `-3`, ...) is appended until no
 * collision remains. An optional key to ignore lets a model keep its own slug
 * when regenerating during an update.
 */
final class SlugGenerator
{
    /**
     * @param  class-string<Model>  $model
     */
    public function unique(string $model, string $base, string $column = 'slug', int|string|null $ignoreKey = null): string
    {
        $root = Str::slug($base);
        $slug = $root;
        $suffix = 2;

        while ($this->exists($model, $column, $slug, $ignoreKey)) {
            $slug = "{$root}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    /**
     * @param  class-string<Model>  $model
     */
    private function exists(string $model, string $column, string $slug, int|string|null $ignoreKey): bool
    {
        $query = $model::query()->where($column, $slug);

        if ($ignoreKey !== null) {
            $query->whereKeyNot($ignoreKey);
        }

        return $query->exists();
    }
}
