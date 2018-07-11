<?php

namespace Dkh;


abstract class Captcha
{
    /**
     * @var int captcha challenge size's minimum value.
     * @see Captcha::$size
     */
    const MIN_SIZE = 2;
    /**
     * @var array defining the difficulty level's value range.
     */
    const LEVEL_RANGE = [0, 2];
    /**
     * Typedef for captcha image option array.
     * @typedef array ImageOptions
     * @key int 'height' image height (optional).
     * @key int[] 'fill' <RgbaColorArray> [A ?? 127] (optional).
     * @key int[] 'color' <RgbaColorArray> [A ?? 0] font and stroke color (optional).
     *
     * Typedef for RGBA color array.
     * @typedef array RgbaColorArray
     * @enum int
     * @key int 0 red component value 0-255.
     * @key int 1 green component value 0-255.
     * @key int 2 blue component value 0-255.
     * @key double 3 real-world alpha component value 0-1,
     *               see the link below for details;
     *               this differentiates from the alpha parameter in imagecolorallocatealpha().
     * @see {@link https://en.wikipedia.org/wiki/Alpha_compositing Alpha compositing}, imagecolorallocatealpha()
     *
     * @var array <ImageOptions> default captcha image options.
     */
    const DEFAULT_IMAGE_OPTIONS = [
        'height' => 30,
        'fill' => [0, 0, 0, 127],
        'color' => [0, 0, 0, 0],
    ];
    /**
     * @var int image's minimum height
     */
    const MIN_IMAGE_HEIGHT = 20;

    /**
     * @var string captcha challenge.
     */
    protected $challenge = '';
    /**
     * Captcha challenge size might be interpreted differently by each type of captcha.
     * @var int captcha challenge size.
     */
    protected $size;
    /**
     * Captcha difficulty level, which is one of the following values:
     *  int 0 - easy,
     *  int 1 - normal (default),
     *  int 2 - hard.
     * @var int captcha challenge difficulty level.
     */
    protected $level;

    /**
     * Captcha constructor.
     * @param int $size (optional).
     * @param int $level (optional).
     * @see Captcha::$size, Captcha::$level, Captcha::MIN_SIZE, Captcha::LEVEL_RANGE
     */
    public function __construct(int $size = 3, int $level = 1)
    {
        $this->size = max($size, self::MIN_SIZE);
        $this->level = min(
            max($level, self::LEVEL_RANGE[0]),
            self::LEVEL_RANGE[1]
        );
    }

    public function __toString()
    {
        return $this->challenge ?: $this->generate()->challenge;
    }

    /**
     * @param array $options
     * @return string rendered PNG image data encoded with base64.
     * @see Captcha::renderString()
     */
    public function render(array $options = []): string
    {
        // Though the parameter type is set,
        // the instance's value is implicitly converted here.
        // __toString()
        return self::renderString((string)$this, $options);
    }

    /**
     * Render string into captcha image.
     * @param string $string
     * @param array $options (optional).
     * @return string rendered PNG image data encoded with base64.
     * @see Captcha::DEFAULT_IMAGE_OPTIONS
     */
    public static function renderString(string $string,
                                        array $options = []): string
    {
        $characters = str_split($string);
        $count_characters = count($characters);
        // Built-in font is used to draw text
        $font = 5;
        // Font width, height (imagefontwidth($font), imagefontheight($font));
        $font_w = 9;
        $font_h = 15;
        // Image height, min = 20.
        $height = max(
            $options['height'] ?? self::DEFAULT_IMAGE_OPTIONS['height'],
            self::MIN_IMAGE_HEIGHT
        );
        // Character position tolerance
        $tolerance = intdiv($height, 10);
        // Character width, height (padding included) (source image)
        $src_cw = $font_w + 2 * $tolerance;
        $src_ch = $font_h + 2 * $tolerance;
        // Character width (destination image)
        $dst_cw = intdiv($font_w * $height, $font_h);
        // Image width
        $width = $dst_cw * $count_characters;

        $src_img = imagecreatetruecolor($count_characters * $src_cw, $src_ch);
        $dst_img = imagecreatetruecolor($width, $height);
        // Turn off color blending when copying image
        imagealphablending($dst_img, false);
        imagesavealpha($dst_img, true);

        $bg_color = $options['fill'] ?? self::DEFAULT_IMAGE_OPTIONS['fill'];
        $bg_color_id = imagecolorallocatealpha(
            $src_img,
            $bg_color[0],
            $bg_color[1],
            $bg_color[2],
            $bg_color[3] ?? self::DEFAULT_IMAGE_OPTIONS['fill'][3]
        );
        imagefill($src_img, 0, 0, $bg_color_id);
        $color = $options['color'] ?? self::DEFAULT_IMAGE_OPTIONS['color'];
        $color_id = imagecolorallocatealpha(
            $src_img,
            $color[0],
            $color[1],
            $color[2],
            $color[3] ?? self::DEFAULT_IMAGE_OPTIONS['color'][3]
        );

        for ($i = 0; $i < $count_characters; $i++) {
            // Offsets x of the character's content area in the source image
            $src_cx = $i * $src_cw;
            // Draw the character inside its content area
            imagestring(
                $src_img,
                $font,
                $src_cx + $tolerance,
                $tolerance,
                $characters[$i],
                $color_id
            );
            // Copy offsets x, y relative to (from) the content area's position
            $x_area = mt_rand(0, $tolerance);
            $y_area = mt_rand(0, $tolerance);
            $copy_w = $font_w + ($tolerance - $x_area) + mt_rand(0, $tolerance);
            $copy_h = $font_h + ($tolerance - $y_area) + mt_rand(0, $tolerance);
            imagecopyresized(
                $dst_img,
                $src_img,
                $i * $dst_cw,
                0,
                $src_cx + $x_area,
                $y_area,
                $dst_cw,
                $height,
                $copy_w,
                $copy_h
            );
        }

        imagedestroy($src_img);

        $step_w = 10;
        $step_h = 10;
        for ($y = 0; $y <= $height; $y += $step_h) {
            for ($x = 0; $x < $width - $step_w; $x += $step_w) {
                // x1 = x +- 2
                $x1 = $x + mt_rand(-2, 2);
                // x2 = next x +- 2
                //    = x1 + step x +- 2
                $x2 = $x + $step_w + mt_rand(-2, 2);
                // y1 = y +- step y
                $y1 = $y + mt_rand(-$step_h, $step_h);
                // y2 = y +- step y
                $y2 = $y + mt_rand(-$step_h, $step_h);

                imageline($dst_img, $x1, $y1, $x2, $y2, $color_id);
            }
        }

        ob_start();

        imagepng($dst_img);
        imagedestroy($dst_img);

        return base64_encode(ob_get_clean());
    }

    /**
     * Generate or regenerate captcha challenge.
     * @return $this
     */
    abstract public function generate();

    /**
     * @return mixed challenge's resolved value.
     */
    abstract public function resolve();

    /**
     * @param string $string
     * @return mixed captcha challenge's resolved value.
     */
    abstract public static function resolveString(string $string);
}