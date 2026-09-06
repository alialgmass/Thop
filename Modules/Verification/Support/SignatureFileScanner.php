<?php

namespace Modules\Verification\Support;

use Modules\Verification\Contracts\FileScanner;
use Modules\Verification\Exceptions\InfectedFileException;

/**
 * A cheap, dependency-free content check — the default when no AV daemon is
 * configured. It is NOT a real antivirus: it flags a file carrying the EICAR
 * test signature (the industry-standard "does your scanning path work?" probe).
 * Disguised executables are already rejected by the upload request's
 * `mimetypes:` rule. Production should bind a real ClamAV / hosted scanner via
 * `VERIFICATION_SCANNER` (SEC-NFR-05); this keeps the scanning path exercised
 * and lets the QA EICAR case pass without installing anything.
 */
class SignatureFileScanner implements FileScanner
{
    /** The EICAR standard antivirus test string. */
    private const EICAR = 'X5O!P%@AP[4\\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*';

    public function scan(string $absolutePath): void
    {
        if (! is_file($absolutePath) || ! is_readable($absolutePath)) {
            return;
        }

        // The upload request's `max:` size rule has already run, so reading the
        // file whole is bounded.
        $contents = (string) @file_get_contents($absolutePath);

        if (str_contains($contents, self::EICAR)) {
            throw new InfectedFileException;
        }
    }
}
