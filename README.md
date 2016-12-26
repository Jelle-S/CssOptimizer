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
