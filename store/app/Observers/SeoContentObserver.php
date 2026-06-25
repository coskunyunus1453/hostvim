<?php

namespace App\Observers;

use App\Services\CacheInvalidator;
use Illuminate\Database\Eloquent\Model;

class SeoContentObserver
{
    public function saved(Model $model): void
    {
        app(CacheInvalidator::class)->forContentModel($model);
    }

    public function deleted(Model $model): void
    {
        app(CacheInvalidator::class)->forContentModel($model);
    }
}
