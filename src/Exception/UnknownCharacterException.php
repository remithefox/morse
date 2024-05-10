<?php

namespace RemiTheFox\Morse\Exception;

class UnknownCharacterException extends \Exception implements MorseExceptionInterface
{
    /** @var string */
    private string $char;

    /**
     * @param string $char
     */
    public function __construct(string $char)
    {
        $this->char = $char;
        parent::__construct('Unknown character "' . $char . '"');
    }

    /**
     * @return string
     */
    public function getChar(): string
    {
        return $this->char;
    }
}