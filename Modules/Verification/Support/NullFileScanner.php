<?php

namespace Modules\Verification\Support;

use Modules\Verification\Contracts\FileScanner;

/**
 * The default scanner for environments with no malware backend wired (local
 * dev). It performs no check — production MUST bind a real scanner via
 * `VERIFICATION_SCANNER`. Deliberately does nothing rather than pretending, so
 * the gap is explicit in config, not hidden behind a fake pass.
 */
class NullFileScanner implements FileScanner
{
    public function scan(string $absolutePath): void
    {
        // no-op
    }
}
