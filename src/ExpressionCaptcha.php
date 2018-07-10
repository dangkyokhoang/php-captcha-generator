<?php

namespace Dkh;


class ExpressionCaptcha extends Captcha
{
    const ADDITION = '+';
    const SUBTRACTION = '-';
    const MULTIPLICATION = 'x';
    const DIVISION = ':';
    /**
     * @var array defines the number range for each difficulty level.
     */
    const NUMBER_RANGE_MAP = [
        [0, 5],
        [1, 7],
        [2, 9]
    ];

    /**
     * @var array contains current expression information
     */
    private $expression;
    /**
     * @var int the maximum absolute value of the expression
     */
    private $max_absolute_value;
    private $number_range;

    public function __construct(int $size = 3, int $difficulty = 1)
    {
        parent::__construct($size, $difficulty);

        $this->number_range = self::NUMBER_RANGE_MAP[$this->difficulty];
        $this->max_absolute_value = ($this->difficulty + 1) * 10;
    }

    public function createChallenge()
    {
        $this->expression = [
            'numbers' => [],
            'operators' => [],
            'value' => 0
        ];

        $first_number = mt_rand($this->number_range[0], $this->number_range[1]);
        // Group of multiplications and or divisions
        $group = [
            'numbers' => [$first_number],
            'operators' => [],
            'value' => $first_number
        ];

        // The first number of the expression has been initialized
        for ($i = 1; $i < $this->size; $i++) {
            $number = mt_rand($this->number_range[0], $this->number_range[1]);

            $operator = $this->getRandomOperator($group, $number);

            if ($operator === self::ADDITION || $operator === self::SUBTRACTION) {
                $this->expressionAddGroup($group);

                $group = [
                    'numbers' => [$number],
                    'operators' => [],
                    'value' => $operator === self::ADDITION ? $number : -$number
                ];
            } else {
                $group['numbers'][] = $number;
                $group['operators'][] = $operator;
                if ($operator === self::MULTIPLICATION) {
                    $group['value'] *= $number;
                } else {
                    $group['value'] /= $number;
                }
            }
        }

        $this->expressionAddGroup($group);

        $this->challenge = (string)$this->expression['numbers'][0];
        for ($i = 1; $i < $this->size; $i++) {
            $this->challenge .= $this->expression['operators'][$i - 1] . (string)$this->expression['numbers'][$i];
        }

        return $this;
    }

    /**
     * @param array $group
     * @param int $number
     * @return string operator.
     */
    private function getRandomOperator(array $group, int $number): string
    {
        $operators = [];

        if ($this->max_absolute_value >= $this->expression['value'] + $group['value'] + $number) {
            $operators[] = self::ADDITION;
        }
        if ($this->max_absolute_value >= abs($this->expression['value'] + $group['value'] - $number)) {
            $operators[] = self::SUBTRACTION;
        }
        if ($this->max_absolute_value >= abs($this->expression['value'] + $group['value'] * $number)) {
            // Multiplication and division have higher possibilities to be chosen
            $operators[] = self::MULTIPLICATION;
            $operators[] = self::MULTIPLICATION;
        }
        if ($number !== 0 && $group['value'] % $number === 0) {
            $operators[] = self::DIVISION;
            $operators[] = self::DIVISION;
        }

        return $operators[mt_rand(0, count($operators) - 1)];
    }

    /**
     * @param array $group of multiplications and or divisions.
     */
    private function expressionAddGroup(array $group)
    {
        if (count($this->expression['numbers']) > 0) {
            $this->expression['operators'][] = $group['value'] > 0 ? self::ADDITION : self::SUBTRACTION;
        }
        foreach ($group['numbers'] as $number) {
            $this->expression['numbers'][] = $number;
        }
        foreach ($group['operators'] as $operator) {
            $this->expression['operators'][] = $operator;
        }
        $this->expression['value'] += $group['value'];
    }

    public function getResolvedValue()
    {
        return $this->expression['value'];
    }

    public static function test(string $challenge, string $input_value): bool
    {
        // If the hyphen-minus is in the middle of the set,
        // it must comes after a backslash.
        $operator_pattern = '[' . self::SUBTRACTION . self::ADDITION . self::MULTIPLICATION . self::DIVISION . ']';
        if (!preg_match('/^([0-9]' . $operator_pattern . ')+[0-9]$/', $challenge)) {
            return false;
        }

        // Extract numbers and operators
        preg_match_all('/[0-9]/', $challenge, $numbers);
        $numbers = $numbers[0];
        $count_numbers = count($numbers);
        for ($i = 0; $i < $count_numbers; $i++) {
            $numbers[$i] = (int)$numbers[$i];
        }
        preg_match_all("/$operator_pattern/", $challenge, $operators);
        $operators = $operators[0];

        // Evaluate multiplications and divisions first
        for ($i = 0; $i < $count_numbers - 1; $i++) {
            // Skip additions and subtractions
            if ($operators[$i] === self::ADDITION || $operators[$i] === self::SUBTRACTION) {
                continue;
            }

            // The product or the quotient replaces the second factor or the divisor
            if ($operators[$i] === self::MULTIPLICATION) {
                $numbers[$i + 1] = $numbers[$i] * $numbers[$i + 1];
            } else {
                $numbers[$i + 1] = $numbers[$i] / $numbers[$i + 1];
            }

            // The first factor or the dividend now equals to 0
            $numbers[$i] = 0;

            // The operator is now the operator before or the sign of the first factor or the dividend
            $operators[$i] = $i > 0 ? $operators[$i - 1] : self::ADDITION;
        }

        $resolved_value = $numbers[0];

        for ($i = 1; $i < $count_numbers; $i++) {
            $resolved_value += $operators[$i - 1] === self::ADDITION ? $numbers[$i] : -$numbers[$i];
        }

        return $resolved_value === (int)$input_value;
    }
}