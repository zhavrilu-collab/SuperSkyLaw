<?php

namespace App\Exceptions;

use Exception;

class PlatformTwoFactorRequiredException extends Exception
{
    public function __construct(
        public readonly string $challengeToken,
        public readonly string $challengeUrl,
    ) {
        parent::__construct('Potrebna je 2FA potvrda.');
    }
}
