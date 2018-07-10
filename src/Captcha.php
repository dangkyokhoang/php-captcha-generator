<?php

namespace Dkh;


abstract class Captcha
{
    const MIN_SIZE = 2;
    const DIFFICULTY_RANGE = [0, 2];

    const DEFAULT_IMAGE_OPTIONS = [
        'height' => 30,
        'color' => [0, 0, 0, 0],
        'background_color' => [255, 255, 255, 127]
    ];

    /**
     * @var string captcha challenge.
     */
    protected $challenge;
    /**
     * @var int captcha challenge's size.
     */
    protected $size;
    /**
     * @var int captcha challenge's difficulty level.
     */
    protected $difficulty;

    /**
     * Captcha constructor.
     * @param int $size captcha challenge's size (optional).
     * @param int $difficulty captcha challenge's difficulty level (optional).
     */
    public function __construct(int $size = 3, int $difficulty = 1)
    {
        $this->challenge = '';
        $this->size = max($size, self::MIN_SIZE);
        $this->difficulty = min(max($difficulty, self::DIFFICULTY_RANGE[0]), self::DIFFICULTY_RANGE[1]);
    }

    public function __toString()
    {
        return $this->challenge;
    }

    /**
     * @param array $options
     * @return string
     * @see Captcha::createImage()
     */
    public function toImage(array $options = [])
    {
        return self::createImage($this->challenge, $options);
    }

    /**
     * Typedef for array $options.
     * @typedef array $options
     * @key int [height] image height (optional).
     * @key int[] [color] RGB[A] font and stroke color(optional).
     * @key int[] [background_color] RGB[A] (optional).
     * @see Captcha::DEFAULT_IMAGE_OPTIONS
     *
     * Create Captcha image.
     * @param string $challenge
     * @param array $options (optional) (see above).
     * @return string base64 PNG image.
     */
    public static function createImage(string $challenge, array $options = [])
    {
        $characters = str_split($challenge);
        $count_characters = count($characters);

        $font = 5;
        $font_width = imagefontwidth($font);
        $font_height = imagefontheight($font);

        // Min image height = 20px
        $height = max($options['height'] ?? self::DEFAULT_IMAGE_OPTIONS['height'], 20);
        // Character position tolerance
        $tolerance = intdiv($height, 10);
        // Character size (source image)
        $src_char_w = $font_width + $tolerance * 2;
        $src_char_h = $font_height + $tolerance * 2;
        // Character size (destination image)
        $dst_char_w = intdiv($font_width * $height, $font_height);
        // Image width
        $width = $dst_char_w * $count_characters;

        $src_img = imagecreatetruecolor($src_char_w * $count_characters, $src_char_h);
        $dst_img = imagecreatetruecolor($width, $height);
        // Turn off color blending when copying image
        imagealphablending($dst_img, false);
        imagesavealpha($dst_img, true);

        $color = $options['color'] ?? self::DEFAULT_IMAGE_OPTIONS['color'];
        $color[3] = isset($color[3]) ? $color[3] : 0;
        $color_id = imagecolorallocatealpha($src_img, $color[0], $color[1], $color[2], $color[3]);

        $bg_color = $options['background_color'] ?? self::DEFAULT_IMAGE_OPTIONS['background_color'];
        $bg_color[3] = isset($bg_color[3]) ? $bg_color[3] : 127;
        $bg_color_id = imagecolorallocatealpha($src_img, $bg_color[0], $bg_color[1], $bg_color[2], $bg_color[3]);
        imagefill($src_img, 0, 0, $bg_color_id);
        imagefill($dst_img, 0, 0, $bg_color_id);

        for ($i = 0; $i < $count_characters; $i++) {
            // The x-pos of the character's content box in the source image
            $src_char_x = $src_char_w * $i;
            // Draw the character inside its content box (with tolerance)
            imagestring($src_img, $font, $src_char_x + $tolerance, $tolerance, $characters[$i], $color_id);

            // Copy the character's box (copied tolerance <= tolerance)
            // to the destination image.
            $box_offset_x = mt_rand(0, $tolerance);
            $box_offset_y = mt_rand(0, $tolerance);
            $box_w = $font_width + ($tolerance - $box_offset_x) + mt_rand(0, $tolerance);
            $box_h = $font_height + ($tolerance - $box_offset_y) + mt_rand(0, $tolerance);
            imagecopyresized(
                $dst_img,
                $src_img,
                $dst_char_w * $i + mt_rand(-$tolerance, $tolerance),
                mt_rand(-$tolerance, $tolerance),
                $src_char_x + $box_offset_x,
                $box_offset_y,
                $dst_char_w,
                $height,
                $box_w,
                $box_h
            );
        }

        imagedestroy($src_img);

        $cell_w = 10;
        $cell_h = 10;
        // For each row of the cell height in height
        for ($y = 0; $y <= $height; $y += $cell_h) {
            // For each column of the cell width in width
            for ($x = 0; $x < $width - $cell_w; $x += $cell_w) {
                // Start x = x +- 2
                $start_x = $x + mt_rand(-2, 2);
                // End x = x + cell width +- 2
                $end_x = $x + $cell_w + mt_rand(-2, 2);
                // Start y = y +- cell height
                $start_y = $y + mt_rand(-$cell_h, $cell_h);
                // End y = y +- cell height
                $end_y = $y + mt_rand(-$cell_h, $cell_h);
                imageline($dst_img, $start_x, $start_y, $end_x, $end_y, $color_id);
            }
        }

        ob_start();
        imagepng($dst_img);
        imagedestroy($dst_img);

        return base64_encode(ob_get_clean());
    }

    /**
     * @return $this
     */
    abstract public function createChallenge();

    /**
     * @return string challenge's resolved value.
     */
    abstract public function getResolvedValue();

    /**
     * @param string $challenge
     * @param string $input_value the [user] input value to test against the challenge.
     * @return bool true if the challenge is resolved to the value, false otherwise.
     */
    abstract public static function test(string $challenge, string $input_value): bool;
}