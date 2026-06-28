<?php

declare(strict_types=1);

namespace App\Modules\Creator\Services;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

final class CategoryService
{
    public function listAll(): Collection
    {
        return Category::orderBy('sort_order')->get();
    }
}
