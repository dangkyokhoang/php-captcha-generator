<?php

namespace Dkh;

class StringCaptcha extends Captcha
{
    /**
     * @var array defines character set for each difficulty level.
     */
    const CHARACTER_MAP = [
        // Easy
        [
            // Alphabet characters in UPPERCASE (excluding: D, F, G, I, O, Q, R, U)
            'A', 'B', 'C', 'E', 'H', 'J',
            'K', 'L', 'M', 'N', 'P', 'S', 'T',
            'V', 'W', 'X', 'Y', 'Z',
            // Alphabet characters in lowercase (excluding: e, f, i, j, l, o, p, r, u, y, z)
            'a', 'b', 'c', 'd', 'g', 'h',
            'k', 'm', 'n', 'q', 's', 't',
            'v', 'w', 'x'
        ],
        // Normal
        [
            // Digits (excluding: 0, 1)
            '2', '3', '4', '5', '6', '7', '8', '9',
            // Alphabet characters in UPPERCASE (excluding: I, O, Q, U)
            'A', 'B', 'D', 'C', 'E', 'F', 'G', 'H', 'J',
            'K', 'L', 'M', 'N', 'P', 'R', 'S', 'T',
            'V', 'W', 'X', 'Y', 'Z',
            // Alphabet characters in lowercase (excluding: e, i, l, o, u, z)
            'a', 'b', 'c', 'd', 'f', 'g', 'h', 'j',
            'k', 'm', 'n', 'q', 'r', 's', 't',
            'v', 'w', 'x', 'y'
        ],
        // Hard
        [
            // Some non-word characters
            '#', ')', '*', '+', '.', '/', ';', '=', '?', '_',
            // Digits
            '0', '1', '2', '3', '4', '5', '6', '7', '8', '9',
            // Alphabet characters in UPPERCASE
            'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J',
            'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T',
            'U', 'V', 'W', 'X', 'Y', 'Z',
            // Alphabet characters in lowercase
            'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j',
            'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't',
            'u', 'v', 'w', 'x', 'y', 'z'
        ]
    ];

    /**
     * @var array set of characters to generate captcha challenge from.
     * @see StringCaptcha::CHARACTER_MAP
     */
    private $characters;
    /**
     * @var int the last index of the character array.
     */
    private $characters_last_index;

    public function __construct(int $size = 3, int $level = 1)
    {
        parent::__construct($size, $level);
        $this->characters = self::CHARACTER_MAP[$this->level];
        $this->characters_last_index = count($this->characters) - 1;
    }

    public function generate()
    {
        $this->challenge = '';
        for ($i = 0; $i < $this->size; $i++) {
            $this->challenge .= $this->randomCharacter();
        }

        return $this;
    }

    private function randomCharacter(): string
    {
        return $this->characters[mt_rand(0, $this->characters_last_index)];
    }

    public function resolve(): string
    {
        // Though method's return type is set,
        // the return value is implicitly converted here.
        // __toString()
        return (string)$this;
    }

    public static function resolveString(string $string): string
    {
        return $string;
    }
}