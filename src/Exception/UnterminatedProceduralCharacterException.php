<?php

namespace RemiTheFox\Morse\Exception;

class UnterminatedProceduralCharacterException extends \Exception implements MorseExceptionInterface
{
    public function __construct()
    {
        parent::__construct('Unterminated procedural character');
    }
}