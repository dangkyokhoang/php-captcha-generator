<?php

namespace Dkh;


use PHPUnit\Framework\TestCase;

class StringCaptchaTest extends TestCase
{
    /**
     * Since testing needs to do thousands of assertions,
     * captcha generating and verifying are to be tested in this test for efficiency.
     */
    public function testCreationAssertion()
    {
        /**
         * @var array contains the challenge pattern for each difficulty level.
         */
        $challenge_patterns = [
            // Alphabet characters only
            '/^[a-zA-Z]+$/',
            // Word characters
            '/^\w+$/',
            // Any non-linebreak characters
            '/^.+$/'
        ];

        for ($i = 0; $i < 1000; $i++) {
            $size = mt_rand(StringCaptcha::MIN_SIZE, 100);
            $level = mt_rand(StringCaptcha::LEVEL_RANGE[0], StringCaptcha::LEVEL_RANGE[1]);
            $captcha = new StringCaptcha($size, $level);

            // Test StringCaptcha::generate()
            $this->assertEquals($captcha, $captcha->generate());
            // __toString()
            $this->assertEquals($size, strlen($captcha));
            // __toString()
            $this->assertRegExp($challenge_patterns[$level], $captcha);

            // Test StringCaptcha::resolveString()
            $resolved_value = $captcha->resolve();
            // __toString()
            $this->assertEquals($resolved_value, StringCaptcha::resolveString($captcha));
        }
    }

    public function testToImage()
    {
        for ($i = 0; $i < 100; $i++) {
            $size = mt_rand(StringCaptcha::MIN_SIZE, 10);
            // Image options
            $options = [
                'height' => mt_rand(StringCaptcha::MIN_IMAGE_HEIGHT, 50),
                'color' => [mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255), 0],
                'fill' => [mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 127)]
            ];
            $captcha = new StringCaptcha($size);

            $img_base64 = $captcha->render($options);
            $this->assertNotEmpty($img_base64);

            // Test will fail if this produces any error
            $img = imagecreatefromstring(base64_decode($img_base64));
            $width = imagesx($img);
            $height = imagesy($img);

            $this->assertGreaterThanOrEqual(
                $size * intdiv(9 * $options['height'], 15),
                $width
            );
            $this->assertEquals($options['height'], $height);

            $count_text_or_stroke_pixels = 0;
            $count_background_pixels = 0;
            for ($x = 0; $x < $width; $x++) {
                $color_index = imagecolorat($img, $x, $height / 2);
                $color = [
                    ($color_index >> 16) & 0xff,
                    ($color_index >> 8) & 0xff,
                    $color_index & 0xff,
                    ($color_index >> 24) & 0xff
                ];
                if ($color === $options['color']) {
                    $count_text_or_stroke_pixels += 1;
                } elseif ($color === $options['fill']) {
                    $count_background_pixels += 1;
                } else {
                    $this->assertEquals($options['fill'], $color);
                }
            }

            imagedestroy($img);
            $this->assertNotEmpty($count_text_or_stroke_pixels);
            $this->assertEquals($width, $count_text_or_stroke_pixels + $count_background_pixels);
        }
    }
}