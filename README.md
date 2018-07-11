# PHP Captcha Generator
### Description
A PHP library for generating Captcha challenges using libgd.  
### Captcha examples
- Easy expression captcha:  
  ![PHP Captcha Generator - Expression - Easy](https://image.ibb.co/bSWhE8/index.png)  
- Hard expression captcha:  
  ![PHP Captcha Generator - Expression - Hard](https://preview.ibb.co/cejpu8/index.png)  
- Easy string captcha:  
  ![PHP Catpcha Generator - String - Easy](https://image.ibb.co/mqXFj8/index.png)  
- Hard string captcha (colored):  
  ![PHP Captcha Generator - String - Hard](https://image.ibb.co/eTV1P8/index.png)
### Repositories
- GitHub: https://github.com/dangkyokhoang/PHP-Captcha-Generator.
- Packagist: https://packagist.org/packages/dkh/captcha-generator.
### Required dependencies
- GD Graphics Library (ext-gd).
# User Guide
## Installation
You can easily get the library installed on your project by running this command.
  ```
  composer require dkh/captcha-generator
  ```
## Implementation
### Captcha types
- `ExpressionCaptcha` expression captcha requires users to do basic arithmetic operations (addition, subtraction, multiplication and division) to solve.
- `StringCaptcha` string captcha only requires users to recognize the characters in the string.
### Create a captcha
To create a captcha, use `new *Captcha($size?, $level?)`.
  ```php
  // Default size: 3 and default difficulty level: 1
  $expression_captcha = new ExpressionCaptcha();
  $string_captcha = new StringCaptcha();
  // Specific size and difficulty level
  $size = 10;
  $level = 2;
  $another_expression_captcha = new ExpressionCaptcha($size, $level);
  ```
### Get captcha's solved value
To get captcha's solved value, call `$captcha->solve()` or `*Captcha::solveString($string)`.  
Store the solved value somewhere, e.g in a session variable, to later verify user's captcha input.
  ```php
  $_SESSION['captcha'] = $captcha->solve();
  // Or in a way that is infrequent, use static method *Captcha::solveString()
  $my_expression = '1+6:3-2*4';
  $_SESSION['my_captcha'] = ExpressionCaptcha::solveString($my_expression);
  ```
### Verify user's captcha input
To verify user's captcha input, compare it with the captcha's previously solved value stored somewhere.
  ```php
  $user_captcha_input = $_POST['captcha'] ?? '';
  $is_matched = $_SESSION['captcha'] === $user_captcha_input;
  // Solve the captcha or die :)
  $is_matched or die();
  ```
### Display the captcha image
To render captcha into image, call `$captcha->render($options?)`, `Captcha::renderString($string, $options?)`. Return value is a PNG image data string encoded with base64.
To dislay the captcha image, use data URL `'data:image/png;base64,' . $base64_image` or save the rendered image somewhere and return the image's path.
  ```php
  $base64_image = $captcha->render();
  echo sprintf('<img src="data:image/png;base64,%s">', $base64_image);
  // Or in a way like this:
  $my_string = 'My custom captcha string';
  $base64_image_to_be_saved = Captcha::renderString($my_string);
  $image_path = 'captcha.png';
  file_put_contents($image_path, base64_decode($base64_image_to_be_saved));
  echo sprintf('<img src="/%s">', $image_path);
  // Image rendered with some options
  $another_base64_image = $captcha->render([
      'height' => 50,
      'fill' => [0, 0, 0, 30],
      // The alpha channel is optional
      'color' => [255, 255, 255]
  ]);
  echo sprintf('<img src="data:image/png;base64,%s">', $another_base64_image);
  ```
### Example implementation of the library
  ```php
  <?php

  require_once 'vendor/autoload.php';

  use Dkh\ExpressionCaptcha;

  session_start();

  // Verify user's captcha input
  if (isset($_POST['captcha']) && isset($_SESSION['captcha'])) {
      $is_matched = $_POST['captcha'] === $_SESSION['captcha'];
  } else {
      $is_matched = null;
  }
  $message = $is_matched !== null ?
      ($is_matched ? 'Captcha matched' : 'Captcha not matched') :
      'Try solving the captcha';

  // Create a captcha
  $captcha = new ExpressionCaptcha();
  // Store captcha's solved value
  $_SESSION['captcha'] = $captcha->solve();
  // Render the captcha into image
  // The return value is a PNG image string encoded with base64
  $base64_image = $captcha->render();

  echo sprintf(
      '<!DOCTYPE html>' .
      '<html>' .
      '<body>' .
      '<form method="POST">' .
      '<img src="data:image/png;base64,%s"><br>' .
      '<input name="captcha" placeholder="Input captcha">' .
      '<input type="submit" value="Submit"><br>' .
      '</form>' .
      '<div>Message: %s</div>' .
      '</body>' .
      '</html>',
      $base64_image,
      $message
  );
  ```
