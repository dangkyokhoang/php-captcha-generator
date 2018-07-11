<?php

namespace Dkh;


use PHPUnit\Framework\TestCase;

class ExpressionCaptchaTest extends TestCase
{
    /**
     * Since testing needs to do thousands of assertions,
     * captcha generating and verifying are to be tested in this test for efficiency.
     */
    public function testCreationAssertion()
    {
        /**
         * If eval() is enabled, ExpressionCaptcha::resolveString() can be tested
         * Note that disabling eval() will only affect this test,
         *  since ExpressionCaptcha::resolveString() should always work without using eval().
         * @see ExpressionCaptcha::resolveString()
         *
         * @var boolean that tells whether eval() is enabled.
         * */
        $eval_enabled = @eval('return true;');
        // To replace expression operators to equivalent PHP operators
        $expression_operators = [
            ExpressionCaptcha::ADDITION,
            ExpressionCaptcha::SUBTRACTION,
            ExpressionCaptcha::MULTIPLICATION,
            ExpressionCaptcha::DIVISION
        ];
        $php_operators = ['+', '-', '*', '/'];

        for ($i = 0; $i < 1000; $i++) {
            $size = mt_rand(ExpressionCaptcha::MIN_SIZE, 100);
            $level = mt_rand(ExpressionCaptcha::LEVEL_RANGE[0], ExpressionCaptcha::LEVEL_RANGE[1]);
            $captcha = new ExpressionCaptcha($size, $level);

            // Test ExpressionCaptcha::generate()
            $this->assertEquals($captcha, $captcha->generate());
            // __toString()
            $this->assertEquals($size, (strlen($captcha) + 1) / 2);

            // Test ExpressionCaptcha::resolveString()
            $resolved_value = $captcha->resolve();
            // __toString()
            $this->assertEquals($resolved_value, ExpressionCaptcha::resolveString($captcha));
            if ($eval_enabled) {
                // Convert expression's operators to PHP equivalent operators
                $php_expression = str_replace($expression_operators, $php_operators, $captcha);
                $this->assertEquals(eval("return $php_expression;"), $resolved_value);
            }
        }
    }
    /**
     * @method testToImage()
     * @see StringCaptchaTest::testToImage()
     * */
}