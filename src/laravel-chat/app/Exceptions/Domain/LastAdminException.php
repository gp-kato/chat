<?php

namespace App\Exceptions\Domain;

use RuntimeException;

class LastAdminException extends RuntimeException
{
    public function __construct(
        string $message = 'At least one admin must remain in the group.'
    ) {
        parent::__construct($message);
    }
}
