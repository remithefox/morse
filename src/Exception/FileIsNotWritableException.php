<?php

namespace RemiTheFox\Morse\Exception;

use JetBrains\PhpStorm\Pure;

class FileIsNotWritableException extends \Exception implements MorseExceptionInterface
{
    public function __construct()
    {
        parent::__construct('Wave file is not writable');
    }

}