<?php

namespace Modules\Auth\Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Modules\Auth\Contracts\OtpSender;
use Modules\Auth\Support\CaptureOtpSender;
use Modules\Auth\Support\LogOtpSender;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CaptureOtpSenderTest extends TestCase
{
    #[Test]
    public function it_stashes_the_code_in_the_cache_for_local_retrieval(): void
    {
        (new CaptureOtpSender)->send('+201000000001', '123456');

        $this->assertSame('123456', Cache::get(CaptureOtpSender::cacheKey('+201000000001')));
    }

    #[Test]
    public function the_capture_driver_resolves_in_local_and_testing(): void
    {
        config()->set('auth.otp.driver', 'capture');
        $this->app->forgetInstance(OtpSender::class);

        $this->assertInstanceOf(CaptureOtpSender::class, $this->app->make(OtpSender::class));
    }

    #[Test]
    #[DataProvider('nonLocalEnvironments')]
    public function the_capture_driver_is_refused_outside_local_and_testing(string $environment): void
    {
        config()->set('auth.otp.driver', 'capture');
        app()->instance('env', $environment);
        $this->app->forgetInstance(OtpSender::class);

        $this->expectException(\RuntimeException::class);

        $this->app->make(OtpSender::class);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function nonLocalEnvironments(): array
    {
        return [
            'production' => ['production'],
            'staging' => ['staging'],
        ];
    }

    #[Test]
    public function the_log_driver_is_still_the_default(): void
    {
        config()->set('auth.otp.driver', 'log');
        $this->app->forgetInstance(OtpSender::class);

        $this->assertInstanceOf(LogOtpSender::class, $this->app->make(OtpSender::class));
    }
}
