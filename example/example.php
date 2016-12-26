<?php

include_once '../vendor/autoload.php';
$css = <<<EOT
div {
  display:block;
  background: transparent;
}
div .black-with-border {
  border: 1px solid black;
  color: black;
  font-weight: bold;
  text-decoration: underline;
  margin: 0;
}

div .black-with-border-and-margin {
  border: 1px solid black;
  color: black;
  font-weight: bold;
  text-decoration: underline;
  margin: 5px 0;
}

h1 {
  font-weight: bold;
  text-decoration: underline;
  padding: 5px;
  color: #d3d3d3;
  margin: 0;
  text-align: left;
}

h2 {
  font-weight: bold;
  text-decoration: underline;
  padding: 5px;
  color: #d3d3d3;
  margin: 0;
  text-align: right;
}

h3 {
  font-weight: bold;
  text-decoration: underline;
  padding: 5px;
  color: #d3d3d3;
  margin: 3px;
  text-align: right;
}

@media only screen and (min-width:16.5em) {
  div .black-with-border {
    border: 1px solid black;
    color: black;
    font-weight: bold;
    text-decoration: underline;
    margin: 0;
  }

  div .black-with-border-and-margin {
    border: 1px solid black;
    color: black;
    font-weight: bold;
    text-decoration: underline;
    margin: 5px 0;
  }
}

@media only screen and (min-width:36em) {
  div .black-with-border {
    border: 1px solid black;
    color: black;
    font-weight: bold;
    text-decoration: underline;
    margin: 0;
  }

  div .black-with-border-and-margin {
    border: 1px solid black;
    color: black;
    font-weight: bold;
    text-decoration: underline;
    margin: 5px 0;
  }
}
EOT;
$optimizer = new Jelle_S\CssOptimizer\CssOptimizer($css, 3);
file_put_contents('style.min.css', $optimizer->renderMinifiedCSS());


$optimizer = new Jelle_S\CssOptimizer\CssOptimizer($css, 3);
file_put_contents('style.scss', $optimizer->renderSCSS());
