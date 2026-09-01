<?php

namespace Modules\Verification\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Middleware\RedirectIfNotAdmin;
use Modules\Verification\Models\VerificationDocument;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams a verification document to an admin inside the Filament panel. Access
 * is gated by the panel's session auth + {@see RedirectIfNotAdmin}
 * on the route group, matching the admin's REST authorization (VerificationPolicy::download).
 */
class AdminDocumentDownloadController extends Controller
{
    public function __invoke(VerificationDocument $document): StreamedResponse
    {
        return $document->downloadResponse();
    }
}
