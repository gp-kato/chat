<?php

namespace App\Exceptions\Domain;

use RuntimeException;

class AlreadyMemberException extends RuntimeException
{
    public function __construct(
        string $message = '既にグループに参加しています'
    ) {
        parent::__construct($message);
    }
}
