<?php

namespace App\Exceptions;

use DomainException;

class CargoManualInvalidoException extends DomainException
{
    public function __construct(string $message, private string $campo = 'formulario')
    {
        parent::__construct($message);
    }

    public function campo(): string
    {
        return $this->campo;
    }
}
