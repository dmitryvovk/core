<?php

namespace Apiato\Core\Exceptions;

use Apiato\Core\Abstracts\Exceptions\Exception;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class UndefinedTransporterException.
 */
class UndefinedTransporterException extends Exception
{
    protected $code = Response::HTTP_INTERNAL_SERVER_ERROR;

    protected $message = 'Default Transporter for Request not defined. Please override $transporter in Ship\Parents\Request\Request.';
}
