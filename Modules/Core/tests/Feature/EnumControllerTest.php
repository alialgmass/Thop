<?php

namespace Modules\Core\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\Api\ApiController;
use Modules\Core\Http\Controllers\Api\EnumController;
use Modules\Core\Http\Controllers\Controller;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EnumControllerTest extends TestCase
{
    #[Test]
    public function the_enum_catalog_endpoint_returns_all_registered_enums(): void
    {
        Route::get('/core-test/enums', EnumController::class);

        $this->getJson('/core-test/enums')
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonStructure([
                'custom_code',
                'status',
                'message',
                'info',
                'body' => [
                    'currencies' => ['SAR', 'USD'],
                    'account_types',
                    'user_status',
                    'verification_status',
                    'verification_request_status',
                ],
            ])
            ->assertJsonPath('body.currencies.SAR.code', 'SAR')
            ->assertJsonCount(4, 'body.account_types');
    }

    #[Test]
    public function the_concern_controllers_are_still_instantiable(): void
    {
        $this->assertTrue(class_exists(Controller::class));
        $this->assertTrue(class_exists(ApiController::class));
    }
}
