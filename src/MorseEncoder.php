<?php

namespace RemiTheFox\Morse;

use RemiTheFox\Morse\Exception\FileIsNotWritableException;
use RemiTheFox\Morse\Exception\UnknownCharacterException;
use RemiTheFox\Morse\Exception\UnknownProceduralCharacterException;
use RemiTheFox\Morse\Exception\UnterminatedProceduralCharacterException;
use RemiTheFox\Wave\AbstractFloatWave;
use RemiTheFox\Wave\Exception\FloatDecoratorNotFound;
use RemiTheFox\Wave\Exception\NotApplicableBitPerSampleException;
use RemiTheFox\Wave\WaveInterface;

class MorseEncoder
{
    private const MORSE_LETTERS = [
        'A' => '.-',
        'B' => '.---',
        'C' => '-.-.',
        'D' => '-..',
        'E' => '.',
        'F' => '..-.',
        'G' => '--.',
        'H' => '....',
        'I' => '..',
        'J' => '.---',
        'K' => '-.-',
        'L' => '.-..',
        'M' => '--',
        'N' => '-.',
        'O' => '---',
        'P' => '.--.',
        'Q' => '--.-',
        'R' => '.-.',
        'S' => '...',
        'T' => '-',
        'U' => '..-',
        'V' => '...-',
        'W' => '.--',
        'X' => '-..-',
        'Y' => '-.--',
        'Z' => '--..',
    ];

    private const MORSE_NUMBERS = [
        '1' => '.----',
        '2' => '..---',
        '3' => '...--',
        '4' => '....-',
        '5' => '.....',
        '6' => '-....',
        '7' => '--...',
        '8' => '---..',
        '9' => '----.',
        '0' => '-----',
    ];

    private const MORSE_NUMBERS_SHORT = [
        '1' => '.-',
        '2' => '..-',
        '3' => '...-',
        '4' => '....-',
        '5' => '.',
        '6' => '-....',
        '7' => '-...',
        '8' => '-..',
        '9' => '-.',
        '0' => '-',
    ];

    private const MORSE_PUNCTUATION = [
        ',' => '--..--',
        '?' => '..--..',
        ':' => '---...',
        '-' => '-....-',
        '"' => '.-..-.',
        '(' => '-.--.',
        '=' => '=..=',
        '.' => '.-.-.-',
        ';' => '-.-.-.',
        '/' => '-..-.',
        '\'' => '.----.',
        '_' => '..--.-',
        ')' => '-.--.-',
        '+' => '.-.-.',
        '@' => '.--.-.',
    ];

    private const MORSE_PROCEDURAL_CHARACTERS = [
        'AR' => '.-.-.', // End of Message
        'AS' => '.-...', // Wait
        'BK' => '-...-.-', // Break in
        'EC' => '.-.-.', // End Copy - end of transmission
        'HH' => '........', // Correction
        'KA' => '-.-.-', // Attention
        'KN' => '-.--.', // Go ahead
        'RT' => '.-.-', // Return - new line
        'SK' => '...-.-', // End of contact
        'SOS' => '...---...', // SOS - distress signal
        'VE' => '...-.', // Verified
    ];

    /** @var WaveInterface */
    private WaveInterface $wave;

    /** @var int */
    private int $sampleRate;

    /** @var int */
    private int $numberOfChannels;

    /** @var array */
    private array $channels;

    /** @var float speed in WPR (words per minute) */
    private float $wpm;

    /** @var float tone in Hz */
    private float $tone;

    /** @var float dot time [s] and silence time in single char */
    private float $dotTime;

    /** @var float dash time [s] and silence time between chars */
    private float $dashTime;

    /** @var float silence time between words */
    private float $spaceTime;

    /** @var float */
    private float $volume;

    /** @var bool */
    private bool $shortNumbers = false;

    /**
     * @param WaveInterface $wave
     * @param array $channels
     * @param float $wpm
     * @param float $tone
     * @param float $volume
     * @throws FileIsNotWritableException
     * @throws FloatDecoratorNotFound
     * @throws NotApplicableBitPerSampleException
     */
    public function __construct(WaveInterface $wave, array $channels, float $wpm = 20, float $tone = 700, float $volume = 1)
    {
        if(!$wave->isWritable()){
            throw new FileIsNotWritableException();
        }
        if (!$wave instanceof AbstractFloatWave) {
            $wave = AbstractFloatWave::decorate($wave);
        }
        $this->wave = $wave;
        $this->sampleRate = $wave->getSampleRate();
        $this->numberOfChannels = $wave->getNumberOfChannels();
        $this->channels = $channels;
        $this->wpm = $wpm;
        $this->tone = $tone;
        $this->volume = $volume;
        $this->recalculateWpmTimes();
    }

    public function getWpm(): float
    {
        return $this->wpm;
    }

    public function setWpm(float $wpm): self
    {
        $this->wpm = $wpm;
        $this->recalculateWpmTimes();
        return $this;
    }

    public function getTone(): float
    {
        return $this->tone;
    }

    public function setTone(float $tone): self
    {
        $this->tone = $tone;
        return $this;
    }

    public function isShortNumbers(): bool
    {
        return $this->shortNumbers;
    }

    public function setShortNumbers(bool $shortNumbers): self
    {
        $this->shortNumbers = $shortNumbers;
        return $this;
    }

    public function getVolume(): float
    {
        return $this->volume;
    }

    public function setVolume(float $volume): self
    {
        $this->volume = $volume;
        return $this;
    }

    public function text(string $text): self
    {
        $text = strtoupper($text);
        while (strlen($text) > 0) {
            $char = substr($text, 0, 1);
            if ('<' == $char) {
                $pos = strpos($text, '>');
                if (false === $pos) {
                    throw new UnterminatedProceduralCharacterException();
                }
                $signal = substr($text, 1, $pos - 1);
                if (!isset(self::MORSE_PROCEDURAL_CHARACTERS[$signal])) {
                    throw new UnknownProceduralCharacterException($signal);
                }
                $this->sendCharCode(self::MORSE_PROCEDURAL_CHARACTERS[$signal]);
                $text = substr($text, $pos + 1);
                continue;
            }
            if (isset(self::MORSE_LETTERS[$char])) {
                $this->sendCharCode(self::MORSE_LETTERS[$char]);
            } else if (isset(self::MORSE_NUMBERS[$char])) {
                $this->sendCharCode(
                    $this->shortNumbers ?
                        self::MORSE_NUMBERS_SHORT[$char] :
                        self::MORSE_NUMBERS[$char]
                );
            } else if (isset(self::MORSE_PUNCTUATION[$char])) {
                $this->sendCharCode(self::MORSE_PUNCTUATION[$char]);
            } else if (' ' === $char) {
                $this->space();
            } else {
                throw new UnknownCharacterException($char);
            }
            $text = substr($text, 1);
        }
        return $this;
    }

    public function space(): self
    {
        $this->sendSilence($this->spaceTime);
        return $this;
    }

    public function endOfMessage(): self
    {
        $this->sendCharCode(self::MORSE_PROCEDURAL_CHARACTERS['AR']);
        return $this;
    }

    public function wait(): self
    {
        $this->sendCharCode(self::MORSE_PROCEDURAL_CHARACTERS['AS']);
        return $this;
    }

    public function breakIn(): self
    {
        $this->sendCharCode(self::MORSE_PROCEDURAL_CHARACTERS['BK']);
        return $this;
    }

    public function endCopy(): self
    {
        $this->sendCharCode(self::MORSE_PROCEDURAL_CHARACTERS['EC']);
        return $this;
    }

    public function correction(): self
    {
        $this->sendCharCode(self::MORSE_PROCEDURAL_CHARACTERS['HH']);
        return $this;
    }

    public function attention(): self
    {
        $this->sendCharCode(self::MORSE_PROCEDURAL_CHARACTERS['KA']);
        return $this;
    }

    public function goAhead(): self
    {
        $this->sendCharCode(self::MORSE_PROCEDURAL_CHARACTERS['KN']);
        return $this;
    }

    public function newLine(): self
    {
        $this->sendCharCode(self::MORSE_PROCEDURAL_CHARACTERS['RT']);
        return $this;
    }

    public function silentKey(): self
    {
        $this->sendCharCode(self::MORSE_PROCEDURAL_CHARACTERS['SK']);
        return $this;
    }

    public function sos(): self
    {
        $this->sendCharCode(self::MORSE_PROCEDURAL_CHARACTERS['SOS']);
        return $this;
    }

    public function verified(): self
    {
        $this->sendCharCode(self::MORSE_PROCEDURAL_CHARACTERS['VE']);
        return $this;
    }

    /**
     * send signals
     * @param string $signals e.g. '.-.-'
     * @return void
     */
    private function sendCharCode(string $signals): void
    {
        $last = strlen($signals) - 1;
        for ($i = 0; $i <= $last; $i++) {
            if (substr($signals, $i, 1) == '.') {
                $this->sendTone($this->dotTime);
            } else {
                $this->sendTone($this->dashTime);
            }
            $this->sendSilence($i == $last ? $this->dashTime : $this->dotTime);
        }
    }

    private function sendTone(float $length): void
    {
        $this->wave->fromGenerator($this->generateTone($length));
    }

    private function sendSilence(float $length): void
    {
        $this->wave->fromGenerator($this->generateSilence($length));
    }

    private function generateSilence(float $length): \Generator
    {
        $lengthInSamples = round($this->sampleRate * $length);
        for ($i = 0; $i < $lengthInSamples; $i++) {
            yield [0];
        }
    }

    private function generateTone(float $length): \Generator
    {
        $lengthInSamples = round($this->sampleRate * $length);
        for ($i = 0; $i < $lengthInSamples; $i++) {
            $level = $this->volume * sin(2 * pi() * $this->tone * $i / $this->sampleRate);
            yield $this->createSample($level);
        }
    }

    private function createSample(float $level): array
    {
        $sample = [];
        for ($i = 0; $i < $this->numberOfChannels; $i++) {
            $sample[] = in_array($i, $this->channels) ? $level : 0;
        }
        return $sample;
    }

    /**
     * calculates dot, dash and space times
     * @return void
     */
    private function recalculateWpmTimes(): void
    {
        $this->dotTime = 1.2 / $this->wpm;
        $this->dashTime = 3.6 / $this->wpm;
        $this->spaceTime = 12 / $this->wpm;
    }
}