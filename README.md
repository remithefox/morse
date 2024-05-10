# remithefox/wave

A PHP library that generates morse code to wave file

## Installation

### Composer

```bash
$ composer require remithefox/morse
```

## Usage

### Creating wave file object

First you need to have object of writable wave file (package `remithefox/wave`).
You can open existing file or create new by builder e.g.:

```php
use RemiTheFox\Wave\Wave;

$builder = Wave::builder()
    ->setNumberOfChannels(1)
    ->setSampleRate(44100)
    ->setBitsPerSample(8)
    ->setFloatDecorator(true);

$wave = $builder->create(__DIR__ . '/sound.wav');
```

### Creating morse encoder object

Next you need to create `MorseEncoder` object. e.g.:

```php
use RemiTheFox\Morse\MorseEncoder;

// ...

$morse = new MorseEncoder($wave, [0], 20, 700, 1);
```

#### Constructor parameters

| parameter   | type            | default | description                                          |
|:------------|:----------------|--------:|:-----------------------------------------------------|
| `$wave`     | `WaveInterface` |  (none) | wave file object                                     |
| `$channels` | `array`         |  (none) | array of channel numbers which you want to use       |
| `$wpm`      | `float`         |    `20` | speed in WPM (words per minute; see [speed](#speed)) |
| `$tone`     | `float`         |   `700` | signal tone in Hz                                    |
| `$volume`   | `float`         |     `1` | volume (should be in range 0..1)                     |

NOTICE: `wpm`, `tone` and `volume` can be changed after create object (see [setters](#setters)).

### Generating text messages

To generate text messages you can use `MorseEncoder::text()` method. e.g.:

```php
// ...

$morse->text('CQ CQ SO9FOX');
```

On `" "` character, to file will be added moment of silence with length 10 times longer than dot.
You can also use method `MorseEncoder::space()` to add this silence. e.g.:

```php
// ...

$morse->space();
```

### Sending procedural characters

To send procedural characters you can use dedicated methods (see [procedural character table](#procedural-character-table)). e.g.:

```php
// ...

$morse->attention();
```

You can also use shortcut between `<` and `>` to send procedural characters (see [procedural character table](#procedural-character-table)). e.g.

```php
// ...

$morse->text('<SOS> CQ CQ SO9FOX');
```

#### Procedural character table

| shortcut | dedicated method | morse code   | significance                                             |
|:---------|:-----------------|:-------------|:---------------------------------------------------------|
| `<AR>`   | `endOfMessage()` | ╸━╸╸━╸╸      | End of Message                                           |
| `<AS>`   | `wait()`         | ╸━╸╸╸╸       | Wait (I need some time)                                  |
| `<BK>`   | `breakIn()`      | ━╸╸╸╸━╸╸━╸   | Break in                                                 |
| `<EC>`   | `endCopy()`      | ╸━╸╸━╸╸      | End Copy - end of transmission                           |
| `<HH>`   | `correction()`   | ╸╸╸╸╸╸╸╸     | Correction                                               |
| `<KA>`   | `attention()`    | ━╸╸━╸╸━╸     | Attention                                                |
| `<KN>`   | `goAhead()`      | ━╸╸━╸━╸╸     | Go ahead                                                 |
| `<RT>`   | `newLine()`      | ╸━╸╸━╸       | Return - new line                                        |
| `<SK>`   | `silentKey()`    | ╸╸╸━╸╸━╸     | End of contact (after call sign means deceased operator) |
| `<SOS>`  | `sos()`          | ╸╸╸━╸━╸━╸╸╸╸ | SOS, Save Our Souls - distress signal                    |
| `<VE>`   | `verified()`     | ╸╸╸━╸╸       | Verified                                                 |

### Short numbers mode

If you want to use short number signals you can set short numbers mode. e.g.:

```php
// ...

$morse->setShortNumbers(true);
$morse->setShortNumbers(false);
```

NOTICE: some short number signals are same as some letter signals. You can use short number mode when you are sending
lot digits e.g. phone number, but you should avoid to sending short number signals otherwise, especially during sending
call signs or QTH locators. e.g.:

```php
// ...

$morse
    ->text('CQ CQ SO9FOX CALL ME')
    ->space()
    ->setShortNumbers(true)
    ->text('501 234 567')
    ->setShortNumbers(false);
```

| digit | normal code | short code | shor code same as |
|------:|:------------|:-----------|:------------------|
|     1 | ╸━╸━╸━╸━╸   | ╸━╸        | A                 |
|     2 | ╸╸━╸━╸━╸    | ╸╸━╸       | U                 |
|     3 | ╸╸╸━╸━╸     | ╸╸╸━╸      | V                 |
|     4 | ╸╸╸╸━╸      | ╸╸╸╸━╸     | (none)            |
|     5 | ╸╸╸╸╸       | ╸          | E                 |
|     6 | ━╸╸╸╸╸      | ━╸╸╸╸╸     | (none)            |
|     7 | ━╸━╸╸╸╸     | ━╸╸╸╸      | B                 |
|     8 | ━╸━╸━╸╸╸    | ━╸╸╸       | D                 |
|     9 | ━╸━╸━╸━╸╸   | ━╸╸        | N                 |
|     0 | ━╸━╸━╸━╸━╸  | ━╸         | T                 |

### Getters

| getter             | returning type | significance                                         |
|:-------------------|:---------------|:-----------------------------------------------------|
| `getWpm()`         | `float`        | returns transmission speed in WPM (words per minute) |
| `getTone()`        | `float`        | returns tone in Hz                                   |
| `isShortNumbers()` | `bool`         | returns true short numbers mode is set               |
| `getVolume()`      | `float`        | returns volume                                       |

### Setters

| setter              | parametr type | significance                                                           |
|:--------------------|:--------------|:-----------------------------------------------------------------------|
| `setWpm()`          | `float`       | sets transmission speed in WPM (words per minute; see [speed](#speed)) |
| `setTone()`         | `float`       | sets tone in Hz                                                        |
| `setShortNumbers()` | `bool`        | sets or unsets short numbers mode                                      |
| `setVolume()`       | `float`       | sets volume (should be in range 0..1)                                  |

### Exceptions

All exceptions are in namespace `\RemiTheFox\Morse\Exception` implements
`\RemiTheFox\Morse\Exception\MorseExceptionInterface`.

| exception                                  | significance                              |
|:-------------------------------------------|:------------------------------------------|
| `FileIsNotWritableException`               | wave file is not writable                 |
| `UnknownCharacterException`                | unknown character                         |
| `UnknownProceduralCharacterException`      | unknown procedural character              |
| `UnterminatedProceduralCharacterException` | unterminated procedural character in text |

### Speed

Speed in words per minute cannot be specified precisely because it depends on length of words.
Wpm parameter is using to define dot, dash, and silence times.

| element                    |      time rule | 15 WPM | 20 WPM | 25 WPM |
|:---------------------------|---------------:|-------:|-------:|-------:|
| dot                        |  1 200ms / WPM |   80ms |   60ms |   48ms |
| dash                       |  3 600ms / WPM |  240ms |  180ms |  144ms |
| silence inside character   |  1 200ms / WPM |   80ms |   60ms |   48ms |
| silence between characters |  3 600ms / WPM |  240ms |  180ms |  144ms |
| silence between words      | 12 000ms / WPM |  800ms |  600ms |  480ms |


long text? ASCII-fox:

```text
 /\-/\
(=^w^=)
 )   (
```

━╸━╸╸╸╸&nbsp;&nbsp;&nbsp;╸╸╸━╸━╸