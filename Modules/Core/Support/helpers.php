<?php

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Modules\Admin\Models\Admin;

if (! function_exists('getCurrentLang')) {
    function getCurrentLang(): string
    {
        return app()->getLocale();
    }
}

if (! function_exists('uploadMedia')) {
    function uploadMedia($name, $files, ?Model $model, $clearMedia = false): void
    {
        if ($clearMedia) {
            $model?->clearMediaCollection($name);
        }
        if (is_array($files)) {
            foreach ($files as $file) {
                uploadMedia($name, $file, $model, $clearMedia);
            }
        }
        if ($files instanceof UploadedFile) {
            $model->addMedia($files)->toMediaCollection($name);
        }

        if (base64_encode(base64_decode($files, true)) === $files) {
            $model->addMediaFromBase64($files)->usingFileName(Str::finish(Str::random(), '.png'))->toMediaCollection($name);
        }
    }
}

if (! function_exists('registeredModules')) {
    function registeredModules(): array
    {
        return [
            'Admin',
            'Auth',
            'Businesses',
            'Taxonomy',
            'Verification',
            'Core',
        ];
    }
}

// Active Guard Function
if (! function_exists('activeGuard')) {
    function activeGuard($guard = null): bool|int|string|null
    {
        if ($guard) {
            return auth($guard)->check();
        }

        foreach (array_keys(config('auth.guards')) as $guard) {
            if (auth($guard)->check()) {
                return $guard;
            }
        }

        return null;
    }
}

// Get auth user
if (! function_exists('user')) {
    /**
     * @param  string|null  $attribute
     * @param  string|null  $guard
     */
    function user($attribute = null, $guard = null): User|Admin|string|null
    {
        if ($attribute) {
            return auth(activeGuard($guard))->user()?->{$attribute};
        }

        return auth(activeGuard($guard))->user();
    }
}
