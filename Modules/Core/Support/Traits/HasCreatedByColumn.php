<?php

namespace Modules\Core\Support\Traits;

use Illuminate\Database\Eloquent\Model;
use Modules\Admin\Models\Admin;

trait HasCreatedByColumn
{
    public function createdBy()
    {
        return $this->belongsTo(Admin::class, 'created_by', 'id');
    }

    public static function bootHasCreatedByColumn(): void
    {

        static::creating(function (Model $model) {
            $model->created_by = user('id');
        });

    }
}
