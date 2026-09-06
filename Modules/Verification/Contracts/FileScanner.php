<?php

namespace Modules\Verification\Contracts;

use Modules\Verification\Exceptions\InfectedFileException;
use Modules\Verification\Providers\VerificationServiceProvider;

/**
 * Malware check run on an uploaded verification document before it is stored or
 * associated with a request (SEC-NFR-05 — "validated by type and size, scanned
 * before publish"). The concrete backend (ClamAV daemon, a hosted scanning API)
 * is deployment configuration bound per {@see VerificationServiceProvider};
 * a delivery/availability problem should surface as a thrown exception, never a
 * silent pass.
 */
interface FileScanner
{
    /**
     * @param  string  $absolutePath  the uploaded file's temp path on disk
     *
     * @throws InfectedFileException when the file is flagged
     */
    public function scan(string $absolutePath): void;
}
