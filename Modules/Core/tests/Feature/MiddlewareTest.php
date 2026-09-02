<?php

namespace Modules\Core\Tests\Feature;

use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MiddlewareTest extends TestCase
{
    #[Test]
    public function the_accept_json_middleware_forces_the_json_accept_header(): void
    {
        Route::middleware('accept.json')->get('/core-test/middleware/accept-json', function () {
            return response()->json(['accept' => request()->header('Accept')]);
        });

        $this->get('/core-test/middleware/accept-json')
            ->assertOk()
            ->assertJsonPath('accept', 'application/json');
    }

    #[Test]
    public function the_app_language_middleware_sets_the_locale_from_accepted_languages(): void
    {
        config(['app.supported-languages' => ['en', 'ar']]);

        Route::middleware('api.language')->get('/core-test/middleware/language', function () {
            return response()->json(['locale' => app()->getLocale(), 'lang' => getCurrentLang()]);
        });

        $this->withHeader('Accept-Language', 'ar')
            ->get('/core-test/middleware/language')
            ->assertOk()
            ->assertJsonPath('locale', 'ar')
            ->assertJsonPath('lang', 'ar');
    }

    #[Test]
    public function the_app_language_middleware_ignores_unsupported_languages(): void
    {
        config(['app.supported-languages' => ['en', 'ar']]);

        Route::middleware('api.language')->get('/core-test/middleware/language-ignored', function () {
            return response()->json(['locale' => app()->getLocale()]);
        });

        $this->withHeader('Accept-Language', 'fr')
            ->get('/core-test/middleware/language-ignored')
            ->assertOk()
            ->assertJsonPath('locale', 'en');
    }

    #[Test]
    public function the_timezone_middleware_applies_the_accepted_timezone(): void
    {
        Route::middleware('api.timezone')->get('/core-test/middleware/timezone', function () {
            return response()->json([
                'timezone' => config('app.timezone'),
                'php_timezone' => date_default_timezone_get(),
            ]);
        });

        $this->withHeader('Accept-Timezone', 'Africa/Cairo')
            ->get('/core-test/middleware/timezone')
            ->assertOk()
            ->assertJsonPath('timezone', 'Africa/Cairo')
            ->assertJsonPath('php_timezone', 'Africa/Cairo');
    }

    #[Test]
    public function the_phone_throttle_middleware_returns_the_validation_error_after_the_limit(): void
    {
        Route::middleware('throttle.phone:5,1')->get('/core-test/middleware/throttle', function () {
            return response()->json(['ok' => true]);
        });

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->withHeader('Accept', 'application/json')
                ->get('/core-test/middleware/throttle?phone=0501234567')
                ->assertOk();
        }

        $this->withHeader('Accept', 'application/json')
            ->get('/core-test/middleware/throttle?phone=0501234567')
            ->assertStatus(429)
            ->assertJsonPath('status', false)
            ->assertJsonPath('custom_code', 4291)
            ->assertJsonPath('message', __('validation.attempted too many times.'));
    }
}
