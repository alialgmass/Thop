<?php

namespace Modules\Verification\Exceptions;

use Modules\Core\Exceptions\ApiException\ExceptionResponse;
use Modules\Verification\Contracts\FileScanner;

/**
 * Thrown when a {@see FileScanner} flags an
 * uploaded document (SEC-NFR-05). Rendered by the Core handler as a 422 envelope
 * with the message keyed on `file`; nothing is stored or persisted.
 */
class InfectedFileException extends ExceptionResponse
{
    public function __construct()
    {
        $message = __('verification::messages.file_infected');

        parent::__construct($message, 422);

        $this->setCustomBody(['file' => [$message]]);
    }
}
