<?php

namespace Modules\Auth\Tests\Support;

use Modules\Auth\Contracts\OtpSender;
use Modules\Auth\Exceptions\OtpDeliveryException;
use PHPUnit\Framework\Assert;

/**
 * Test double for {@see OtpSender}. Keeps the codes that "would have been sent"
 * in memory so tests can read them without the plaintext ever being persisted
 * or exposed by the API. Can be told to fail to simulate a provider outage.
 */
class FakeOtpSender implements OtpSender
{
    /** @var list<array{phone: string, code: string}> */
    private array $sent = [];

    private bool $shouldFail = false;

    public function send(string $phone, string $code): void
    {
        if ($this->shouldFail) {
            throw new OtpDeliveryException('Simulated OTP provider failure.');
        }

        $this->sent[] = ['phone' => $phone, 'code' => $code];
    }

    /**
     * Make every subsequent send() throw, as an unavailable provider would.
     */
    public function fail(): void
    {
        $this->shouldFail = true;
    }

    /**
     * The most recent code issued for the given phone number.
     */
    public function lastCodeFor(string $phone): ?string
    {
        for ($i = count($this->sent) - 1; $i >= 0; $i--) {
            if ($this->sent[$i]['phone'] === $phone) {
                return $this->sent[$i]['code'];
            }
        }

        return null;
    }

    public function assertSentTo(string $phone): void
    {
        Assert::assertNotNull(
            $this->lastCodeFor($phone),
            "Expected an OTP to have been sent to [{$phone}], but none was.",
        );
    }

    public function assertNothingSent(): void
    {
        Assert::assertSame([], $this->sent, 'Expected no OTP to have been sent.');
    }

    public function assertNothingSentTo(string $phone): void
    {
        Assert::assertNull(
            $this->lastCodeFor($phone),
            "Expected no OTP to have been sent to [{$phone}].",
        );
    }
}
