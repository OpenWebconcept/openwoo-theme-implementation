# OpenWOO theme implementation

First, install and activate the [OpenWOO plugin](https://github.com/openWebconcept/plugin-openwoo). This plugin will handle the registration of the custom post type (CPT) and the API REST routes. Ensure the OpenWOO directory is present in your theme. The code within this directory will process submissions when someone submits an OpenWOO via a GravityForms form.

## Set-up

Add the following action to your functions.php file:

```php
use OpenWOO\SubmissionHandler;

\add_action('gform_after_submission', function ($entry, $form) {
    SubmissionHandler::make(array $entry, array $form)->handle();
}, 10, 2);
```

### Additional configuration

Within the SubmissionHandler class, you'll find the isCorrectForm() method. This method ensures that a form has the CSS class 'openwoo', which is used to validate if the current form is intended for creating OpenWOO posts. So make sure you'll configure the css class properly for the used form.

```php
private function isCorrectForm(): bool
{
    return 'openwoo' === $this->form['cssClass'] ?? '';
}
```

## Namepacing

In the OpenWOO directory, namespacing is used to organize and autoload classes efficiently. While some organizations use Composer for this, you can achieve the same without Composer by manually including the files using an autoloader. The autoloader function can be placed in your functions.php file:

```php
spl_autoload_register(function ($class) {
    // Adjust the base directory as needed
    $base_dir = __DIR__ . '/OpenWOO/';
    $class = str_replace('\\', '/', $class);
    $file = $base_dir . $class . '.php';

    if (file_exists($file)) {
        require $file;
    }
});
```
