# CssOptimizer
Optimizes CSS (combines duplicate selectors, extracts duplicate css properties and values, minifies the css).

Render minified CSS:
```php
$optimizer = new Jelle_S\CssOptimizer\CssOptimizer($css);
file_put_contents('style.min.css', $optimizer->renderMinifiedCSS());
```
Render SCSS from your css:
```php
$optimizer = new Jelle_S\CssOptimizer\CssOptimizer($css);
file_put_contents('style.scss', $optimizer->renderSCSS());
```
This code is open source (GPL-3.0), however, if you like this class and you would like to sponsor the developer (me), feel free to [![paypal](https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=SURHFUCG6B3F8).
