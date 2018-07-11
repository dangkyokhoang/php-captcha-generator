<?php

namespace Dkh;


class ExpressionCaptcha extends Captcha
{
    /**
     * @var string addition sign - plus.
     */
    const ADDITION = '+';
    /**
     * @var string representing subtraction sign - minus.
     */
    const SUBTRACTION = '-';
    /**
     * @var string representing multiplication sign.
     */
    const MULTIPLICATION = 'x';
    /**
     * @var string representing division sign - obelus.
     */
    const DIVISION = ':';
    /**
     * @var array defines number range for each difficulty level.
     */
    const NUMBER_RANGE_MAP = [
        // Easy
        [0, 3],
        // Normal
        [1, 6],
        // Hard
        [2, 9]
    ];

    /**
     * Typedef for expression detail array.
     * @typedef array ExpressionDetails
     * @key array 'numbers' numbers in the expression.
     * @key array 'operators' operators in the expression.
     * @key int 'value' expression's value.
     *
     * @var array <ExpressionDetails> of the current expression's details (see above).
     */
    private $expression;
    /**
     * @var int the maximum value of expression's absolute value.
     */
    private $max_absolute_value;
    /**
     * @var array expression's number range.
     */
    private $number_range;

    public function __construct(int $size = 3, int $level = 1)
    {
        parent::__construct($size, $level);
        $this->number_range = self::NUMBER_RANGE_MAP[$this->level];
        $this->max_absolute_value = 10 + $this->level * 10;
    }

    /**
     * Generate a random expression challenge.
     * The size of the challenge is the number of numbers in the expression.
     * @return $this
     */
    public function generate()
    {
        // Create a new expression detail array
        $this->expression = [
            'numbers' => [],
            'operators' => [],
            'value' => 0
        ];

        // The first number in the expression
        $first_number = mt_rand($this->number_range[0], $this->number_range[1]);
        // Group of multiplications and or divisions,
        // A group is added to the expression
        // if it's followed by an addition or subtraction, or
        //    it's at the end of the expression.
        $group = [
            'numbers' => [$first_number],
            'operators' => [],
            'value' => $first_number
        ];
        for ($i = 1; $i < $this->size; $i++) {
            // The next number in the expression
            $number = mt_rand($this->number_range[0], $this->number_range[1]);
            // The next operator in the expression
            $operator = $this->nextOperator($group, $number);

            if ($operator === self::ADDITION ||
                $operator === self::SUBTRACTION) {
                $this->expressionAddGroup($group);
                // Start generating the next group of multiplications and or divisions
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
            $this->challenge .=
                $this->expression['operators'][$i - 1] .
                (string)$this->expression['numbers'][$i];
        }

        return $this;
    }

    /**
     * @param array $group
     * @param int $number
     * @return string random operator.
     */
    private function nextOperator(array $group, int $number): string
    {
        $operators = [];

        if ($this->max_absolute_value >=
            $this->expression['value'] + $group['value'] + $number) {
            $operators[] = self::ADDITION;
        }
        if ($this->max_absolute_value >=
            abs($this->expression['value'] + $group['value'] - $number)) {
            $operators[] = self::SUBTRACTION;
        }
        // Multiplication and division have higher possibilities to be chosen
        if ($this->max_absolute_value >=
            abs($this->expression['value'] + $group['value'] * $number)) {
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
     * @param array $group group of multiplications and or divisions.
     */
    private function expressionAddGroup(array $group)
    {
        // If this is not the first group in the expression,
        // there must an operator before the group.
        if (count($this->expression['numbers']) > 0) {
            $this->expression['operators'][] = $group['value'] > 0 ?
                self::ADDITION :
                self::SUBTRACTION;
        }
        foreach ($group['numbers'] as $number) {
            $this->expression['numbers'][] = $number;
        }
        foreach ($group['operators'] as $operator) {
            $this->expression['operators'][] = $operator;
        }
        $this->expression['value'] += $group['value'];
    }

    public function solve(): int
    {
        return $this->expression ?
            $this->expression['value'] :
            $this->generate()->expression['value'];
    }

    /**
     * Parse and evaluate string expression without using eval().
     * @param string $string
     * @return int|double expression's evaluated value or 0 on error.
     */
    public static function solveString(string $string)
    {
        $operator_pattern = '[';
        foreach ([
                     self::ADDITION,
                     self::SUBTRACTION,
                     self::MULTIPLICATION,
                     self::DIVISION
                 ] as $operator) {
            if (preg_match('/\W/', $operator)) {
                $operator_pattern .= '\\';
            }
            $operator_pattern .= $operator;
        }
        $operator_pattern .= ']';

        if (!preg_match(
            '/^([0-9]+' . $operator_pattern . ')+[0-9]+$/',
            $string
        )) {
            return 0;
        }
        // Extract numbers
        preg_match_all('/[0-9]+/', $string, $numbers);
        $numbers = array_map(function ($number) {
            return (int)$number;
        }, $numbers[0]);
        // Extract operators
        preg_match_all('/' . $operator_pattern . '/', $string, $operators);
        $operators = $operators[0];
        $count_operators = count($operators);

        // Evaluate multiplications and divisions first
        for ($i = 0; $i < $count_operators; $i++) {
            // Skip additions and subtractions
            if ($operators[$i] === self::ADDITION ||
                $operators[$i] === self::SUBTRACTION) {
                continue;
            }
            // The product or the quotient will replace the second factor or the divisor
            if ($operators[$i] === self::MULTIPLICATION) {
                $numbers[$i + 1] = $numbers[$i] * $numbers[$i + 1];
            } else {
                $numbers[$i + 1] = $numbers[$i] / $numbers[$i + 1];
            }
            // And the first factor or the dividend will be replaced by 0
            $numbers[$i] = 0;
            // The operator will be replaced by
            // the operator before the first factor or the dividend, or
            // a plus if the first factor or the dividend is the first number in the expression.
            $operators[$i] = $operators[$i - 1] ?? self::ADDITION;
        }

        $solved_value = $numbers[0];
        for ($i = 0; $i < $count_operators; $i++) {
            $solved_value += $operators[$i] === self::ADDITION ?
                $numbers[$i + 1] :
                -$numbers[$i + 1];
        }

        // int|double $solved_value
        return $solved_value;
    }
}