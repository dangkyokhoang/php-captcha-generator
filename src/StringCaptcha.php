<?php

namespace Dkh;

class StringCaptcha extends Captcha
{
    /**
     * @var array defines the character set for each difficulty level.
     */
    const CHARACTER_MAP = [
        // Easy challenge
        [
            // Alphabet characters in UPPERCASE (exclude: F, I, O, Q)
            'A', 'B', 'C', 'D', 'E', 'G', 'H', 'J',
            'K', 'L', 'M', 'N', 'P', 'R', 'S', 'T',
            'U', 'V', 'W', 'X', 'Y', 'Z',
            // Alphabet characters in lowercase (exclude: e, f, i, l, o, p, y, z)
            'a', 'b', 'c', 'd', 'g', 'h', 'j',
            'k', 'm', 'n', 'q', 'r', 's', 't',
            'u', 'v', 'w', 'x'
        ],
        // Normal challenge
        [
            // Digits (exclude: 0)
            '1', '2', '3', '4', '5', '6', '7', '8', '9',
            // Alphabet characters in UPPERCASE (exclude: I, O, Q)
            'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'J',
            'K', 'L', 'M', 'N', 'P', 'R', 'S', 'T',
            'U', 'V', 'W', 'X', 'Y', 'Z',
            // Alphabet characters in lowercase (exclude: e, l, z)
            'a', 'b', 'c', 'd', 'f', 'g', 'h', 'i', 'j',
            'k', 'm', 'n', 'o', 'q', 'r', 's', 't',
            'u', 'v', 'w', 'x', 'y'
        ],
        // Hard challenge
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

    private $character_set;
    private $max_character_set_index;

    public function __construct(int $size = 3, int $difficulty = 1)
    {
        parent::__construct($size, $difficulty);

        $this->character_set = self::CHARACTER_MAP[$this->difficulty];
        $this->max_character_set_index = count($this->character_set) - 1;
    }

    /**
     * @see StringCaptcha::CHARACTER_MAP
     */
    public function createChallenge()
    {
        $this->challenge = '';
        for ($i = 0; $i < $this->size; $i++) {
            $this->challenge .= $this->getRandomCharacter();
        }

        return $this;
    }

    private function getRandomCharacter(): string
    {
        return $this->character_set[mt_rand(0, $this->max_character_set_index)];
    }

    public function getResolvedValue()
    {
        return $this->challenge;
    }

    public static function test(string $challenge, string $input_value): bool
    {
        return $challenge === $input_value;
    }
}