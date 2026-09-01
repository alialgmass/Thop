<?php

namespace Modules\Auth\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Enums\OtpPurpose;
use Modules\Auth\Exceptions\InvalidOtpException;
use Modules\Auth\Models\OtpRequest;
use Modules\Auth\Services\OtpService;
use Modules\Auth\Tests\Support\FakeOtpSender;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OtpServiceTest extends TestCase
{
    use RefreshDatabase;

    private OtpService $service;

    private FakeOtpSender $sender;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sender = new FakeOtpSender;
        $this->service = new OtpService($this->sender);
    }

    #[Test]
    public function issuing_stores_a_hash_not_the_plaintext(): void
    {
        $this->service->issue('+201012345678', OtpPurpose::Registration);

        $code = $this->sender->lastCodeFor('+201012345678');
        $row = OtpRequest::query()->firstOrFail();

        $this->assertNotSame($code, $row->code_hash);
        $this->assertSame(6, strlen($code));
    }

    #[Test]
    public function verifying_a_correct_code_consumes_the_request(): void
    {
        $this->service->issue('+201012345678', OtpPurpose::Registration);
        $code = $this->sender->lastCodeFor('+201012345678');

        $this->service->verify('+201012345678', $code, OtpPurpose::Registration);

        $this->assertNotNull(OtpRequest::query()->firstOrFail()->consumed_at);
    }

    #[Test]
    public function a_consumed_code_cannot_be_reused(): void
    {
        $this->service->issue('+201012345678', OtpPurpose::Registration);
        $code = $this->sender->lastCodeFor('+201012345678');
        $this->service->verify('+201012345678', $code, OtpPurpose::Registration);

        $this->expectException(InvalidOtpException::class);
        $this->service->verify('+201012345678', $code, OtpPurpose::Registration);
    }
}
