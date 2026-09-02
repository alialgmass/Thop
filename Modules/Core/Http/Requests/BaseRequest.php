<?php

namespace Modules\Core\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Core\Support\Traits\Request\ValidationRequest;

abstract class BaseRequest extends FormRequest
{
    use ValidationRequest;
}
