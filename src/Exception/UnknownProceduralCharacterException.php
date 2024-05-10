<?php

namespace RemiTheFox\Morse\Exception;

class UnknownProceduralCharacterException extends \Exception implements MorseExceptionInterface
{
    /** @var string */
    private string $proceduralCharacter;

    /**
     * @param string $proceduralCharacter
     */
    public function __construct(string $proceduralCharacter)
    {
        $this->proceduralCharacter = $proceduralCharacter;
        parent::__construct('Unknown procedural character "<' . $proceduralCharacter . '>"');
    }

    /**
     * @return string
     */
    public function getProceduralCharacter(): string
    {
        return $this->proceduralCharacter;
    }
}