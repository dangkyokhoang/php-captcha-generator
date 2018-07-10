<?php

namespace Dkh;


use PHPUnit\Framework\TestCase;

class StringCaptchaTest extends TestCase
{
    /**
     * Since it needs to test thousands of captcha string,
     * challenge creation and assertion methods are to be tested in this test for efficiency.
     */
    public function testCreationAssertion()
    {
        $challenge_patterns = [
            '/^[a-zA-Z]+$/',
            '/^\w+$/',
            '/^.+$/'
        ];

        for ($difficulty = 0; $difficulty <= 2; $difficulty++) {
            for ($i = 0; $i < 1000; $i++) {
                $size = mt_rand(2, 1000);

                $captcha = new StringCaptcha($size, $difficulty);
                /**
                 * @var StringCaptcha the Captcha instance.
                 */
                $challenge = $captcha->createChallenge();

                // createChallenge() must return the captcha instance
                $this->assertEquals($captcha, $challenge);

                // Challenge string length === size
                // __toString() is called
                $this->assertEquals($size, strlen($challenge));

                // __toString() is called
                $this->assertEquals($challenge, $challenge->getResolvedValue());

                // __toString() is called
                $this->assertRegExp($challenge_patterns[$difficulty], $challenge);
            }
        }
    }

    public function testToImage()
    {
        for ($i = 0; $i < 10; $i++) {
            $size = mt_rand(StringCaptcha::MIN_SIZE, 100);
            // Image options
            $options = [
                'height' => mt_rand(20, 50),
                // Was unable to mixed two RGBA colors to write test
                // that's why the color's alpha chanel [3] === 0
                'color' => [mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255), 0],
                'background_color' => [mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 127)]
            ];

            $captcha = new StringCaptcha($size);
            $captcha->createChallenge();

            $base64_img = $captcha->toImage($options);
            $this->assertNotEmpty($base64_img);

            // Test will fail if this produces any error
            $img = imagecreatefromstring(base64_decode($base64_img));
            $width = imagesx($img);
            $height = imagesy($img);
            $min_height = $size * intdiv(imagefontwidth(5) * $options['height'], imagefontheight(5));

            $this->assertGreaterThanOrEqual($min_height, $width);
            $this->assertEquals($options['height'], $height);

            $count_text_or_line_pixels = 0;
            $count_background_pixels = 0;
            for ($x = 0; $x < $width; $x++) {
                $pixel_color_index = imagecolorat($img, $x, $height / 2);
                $pixel_color = [
                    ($pixel_color_index >> 16) & 0xff,
                    ($pixel_color_index >> 8) & 0xff,
                    $pixel_color_index & 0xff,
                    ($pixel_color_index >> 24) & 0xff,
                ];
                if ($pixel_color === $options['color']) {
                    $count_text_or_line_pixels += 1;
                } elseif ($pixel_color === $options['background_color']) {
                    $count_background_pixels += 1;
                } else {
                    break;
                }
            }

            imagedestroy($img);

            $this->assertNotEmpty($count_text_or_line_pixels);
            $this->assertEquals($width, $count_text_or_line_pixels + $count_background_pixels);
        }
    }
}