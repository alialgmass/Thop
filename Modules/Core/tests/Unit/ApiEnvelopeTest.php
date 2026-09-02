<?php

namespace Modules\Core\Tests\Unit;

use Illuminate\Http\JsonResponse;
use Modules\Core\Support\Api\ApiResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApiResponder
{
    use ApiResponse;

    public function respond(): JsonResponse
    {
        return $this->apiResponse();
    }

    public function respondWith(array $body, int $code = 200, int $customCode = 2000, string $message = ''): JsonResponse
    {
        return $this
            ->apiBody($body)
            ->apiCode($code)
            ->apiCustomCode($customCode)
            ->apiMessage($message)
            ->apiResponse();
    }
}

class ApiEnvelopeTest extends TestCase
{
    #[Test]
    public function the_default_envelope_is_a_success(): void
    {
        $response = (new ApiResponder)->respond();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertSame(2000, $response->getData()->custom_code);
        $this->assertTrue($response->getData()->status);
        $this->assertSame(__('app.messages.data_retrieved_successfully'), $response->getData()->message);
        $this->assertSame([], (array) $response->getData()->body);
    }

    #[Test]
    public function any_2xx_status_is_reported_as_success(): void
    {
        $response = (new ApiResponder)->respondWith(
            ['token' => 'abc'],
            code: 201,
            customCode: 2000,
            message: 'Created.',
        );

        $this->assertSame(201, $response->getStatusCode());
        $this->assertTrue($response->getData()->status);
        $this->assertSame('abc', $response->getData()->body->token);
    }

    #[Test]
    public function body_code_and_message_can_be_customized(): void
    {
        $response = (new ApiResponder)->respondWith(
            ['user' => ['id' => 1]],
            code: 422,
            customCode: 4006,
            message: 'Something went wrong.',
        );

        $this->assertSame(422, $response->getStatusCode());
        $this->assertFalse($response->getData()->status);
        $this->assertSame(4006, $response->getData()->custom_code);
        $this->assertSame('Something went wrong.', $response->getData()->message);
        $this->assertSame(1, $response->getData()->body->user->id);
    }
}
