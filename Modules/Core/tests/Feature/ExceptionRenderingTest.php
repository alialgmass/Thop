<?php

namespace Modules\Core\Tests\Feature;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class ExceptionRenderingTest extends TestCase
{
    #[Test]
    public function a_missing_model_keeps_the_framework_not_found_handling(): void
    {
        Route::get('/core-test/exceptions/not-found', function () {
            throw (new ModelNotFoundException)->setModel('Modules\\Core\\Models\\StateLog');
        });

        $this->getJson('/core-test/exceptions/not-found')->assertStatus(404);
    }

    #[Test]
    public function a_not_found_http_exception_keeps_the_framework_handling(): void
    {
        Route::get('/core-test/exceptions/404', function () {
            throw new NotFoundHttpException;
        });

        $this->getJson('/core-test/exceptions/404')->assertStatus(404);
    }

    #[Test]
    public function a_json_validation_exception_keeps_the_framework_422_shape(): void
    {
        Route::get('/core-test/exceptions/validation', function () {
            throw ValidationException::withMessages(['email' => ['The email field is required.']]);
        });

        $this->getJson('/core-test/exceptions/validation')
            ->assertStatus(422)
            ->assertJsonPath('message', 'The email field is required.')
            ->assertJsonPath('errors.email.0', 'The email field is required.');
    }

    #[Test]
    public function a_method_not_allowed_keeps_the_framework_405_handling(): void
    {
        Route::get('/core-test/exceptions/get-only', fn () => response()->json(['ok' => true]));

        $this->postJson('/core-test/exceptions/get-only')->assertStatus(405);
    }

    #[Test]
    public function an_unauthenticated_json_request_gets_the_login_first_envelope(): void
    {
        Route::middleware('auth:sanctum')->get('/core-test/exceptions/auth', fn () => response()->json(['ok' => true]));

        $this->getJson('/core-test/exceptions/auth')
            ->assertStatus(401)
            ->assertJsonPath('custom_code', 4001)
            ->assertJsonPath('status', false)
            ->assertJsonPath('message', __('app.messages.please-log-in-first'));
    }

    #[Test]
    public function unhandled_exceptions_still_use_the_framework_default(): void
    {
        Route::get('/core-test/exceptions/generic', function () {
            throw new RuntimeException('Boom');
        });

        $this->getJson('/core-test/exceptions/generic')->assertStatus(500);
    }

    #[Test]
    public function unauthenticated_web_requests_redirect_to_the_admin_login(): void
    {
        Route::middleware('auth:sanctum')->get('/core-test/exceptions/web-auth', fn () => response('ok'));

        $this->get('/core-test/exceptions/web-auth')->assertRedirect(route('filament.admin.auth.login'));
    }
}
