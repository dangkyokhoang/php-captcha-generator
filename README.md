# PHP Captcha Generator
A PHP library for generating Captcha challenges using libgd.
### Repositories
- GitHub: https://github.com/dangkyokhoang/PHP-Captcha-Generator
- Packagist: https://packagist.org/packages/dkh/captcha-generator
### Required dependencies
- PHP GD Graphics Library (ext-gd).
# User Guide
## Quick start
### Installation
You can easily get the library installed on your project by running this command.
```
composer require dkh/captcha-generator
```
### Implementation
- Create a captcha, e.g an expression captcha.
  ```php
  $captcha = new ExpressionCaptcha();
  ```
- Store the captcha's solved value somewhere to later verify user's captcha input, e.g in a session variable.
  ```php
  $_SESSION['captcha'] = $captcha->solve();
  ```
- Verify user's captcha input.
  ```php
  $is_matched = $_SESSION['captcha'] === $user_captcha_input;
  ```
- Display the captcha image.
  ```php
  $base64_image = $captcha->render();
  echo sprintf('<img src="data:image/png;base64,%s">', $base64_image);
  ```
  Captcha image looks like this:  
  ![PHP Captcha Generator](https://image.ibb.co/bSWhE8/index.png)
- Example implementation of the library.
  <details>
    <summary>Show PHP code.</summary>

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
    $message = $is_matched === null ?
        'Please solve captcha' :
        $is_matched ? 'Captcha matched' : 'Captcha not matched';

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
  </details>
