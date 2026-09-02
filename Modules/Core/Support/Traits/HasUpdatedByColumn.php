<?php

namespace Modules\Core\Support\Traits;

use Illuminate\Database\Eloquent\Model;
use Modules\Admin\Models\Admin;

trait HasUpdatedByColumn
{
    public function updatedBy()
    {
        return $this->belongsTo(Admin::class, 'updated_by', 'id');
    }

    public static function bootHasUpdatedByColumn(): void
    {

        static::updating(function (Model $model) {
            $model->updated_by = user('id');
        });

    }
}
