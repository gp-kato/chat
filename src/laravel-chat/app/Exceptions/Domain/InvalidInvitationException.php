<?php

namespace App\Exceptions\Domain;

use RuntimeException;

class InvalidInvitationException extends RuntimeException
{
    public function __construct(
        string $message = '無効な招待リンクです'
    ) {
        parent::__construct($message);
    }
}
