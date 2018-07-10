<?php

namespace Dkh;


use PHPUnit\Framework\TestCase;

class ExpressionCaptchaTest extends TestCase
{
    /**
     * Set the following constant value to false if eval() is disabled.
     * @var int tells whether to use eval() to test expression results.
     */
    const USE_EVAL_TO_TEST = true;

    /**
     * Since it needs to test thousands of expressions,
     * challenge creation and assertion methods are to be tested in this test for efficiency.
     */
    public function testCreationAssertion()
    {
        for ($difficulty = 0; $difficulty <= 2; $difficulty++) {
            for ($i = 0; $i < 1000; $i++) {
                // Captcha size
                $size = mt_rand(2, 100);

                $captcha = new ExpressionCaptcha($size, $difficulty);
                /**
                 * @var ExpressionCaptcha the Captcha instance.
                 */
                $challenge = $captcha->createChallenge();

                // createChallenge() must return the captcha instance
                $this->assertEquals($captcha, $challenge);

                // Challenge string format: 1+2-3x4x5:6
                // size === number of numbers in the expression
                // string length === size + (number of operators in the expression)
                //               === size + (size - 1)
                //               === 2 * size - 1
                // __toString() is called
                $this->assertEquals(2 * $size - 1, strlen($challenge));

                $value = $challenge->getResolvedValue();

                // Expression's value mustn't exceed the bounds
                $this->assertLessThanOrEqual(($difficulty + 1) * 10, abs($value));

                // Test the test() method
                // __toString() is called
                $this->assertTrue(ExpressionCaptcha::test($challenge, $value));

                // Test the expression's value using eval()
                if (self::USE_EVAL_TO_TEST) {
                    // Convert expression's operators to PHP operators
                    $php_expression = str_replace([
                        ExpressionCaptcha::ADDITION,
                        ExpressionCaptcha::SUBTRACTION,
                        ExpressionCaptcha::MULTIPLICATION,
                        ExpressionCaptcha::DIVISION
                    ], [
                        '+',
                        '-',
                        '*',
                        '/'
                    ], $challenge);

                    $this->assertEquals(eval("return $php_expression;"), $value);
                }
            }
        }
    }

    // See StringCaptchaTest for testToImage() test
}