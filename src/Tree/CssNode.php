<?php

namespace Jelle_S\CssOptimizer\Tree;

use Sabberworm\CSS\RuleSet\RuleSet;
use Tree\Node\Node;

/**
 * Description of CssNode
 *
 * @author drupalpro
 */
class CssNode extends Node {

  /**
   *
   * @var \Sabberworm\CSS\RuleSet\RuleSet
   */
  protected $data;

  protected $isBlock = FALSE;

  public function setBlock($isBlock) {
    $this->isBlock = (bool) $isBlock;
  }

  public function isBlock() {
    return $this->isBlock;
  }

  public function hasData() {
    return !is_null($this->getData());
  }

  public function getData() {
    return $this->data;
  }

  public function addData(RuleSet $data) {
    if (!$this->hasData()) {
      $this->data = $data;
    }
    else {
      foreach ($data->getRules() as $rule) {
        $this->data->addRule($rule);
      }
    }
    $this->data->setRules($this->data->getRulesAssoc());
  }

  public function find(array $selectors, $autocreate = TRUE) {
    $selector = array_shift($selectors);
    $found = FALSE;
    foreach ($this->getChildren() as $child) {
      if ($child->getValue() === $selector) {
        $found = TRUE;
        break;
      }
    }
    if (!$found && $autocreate) {
      $child = new CssNode($selector);
      $this->addChild($child);
    }
    if (!$child) {
      return FALSE;
    }
    return $selectors ? $child->find($selectors, $autocreate) : $child;
  }

  public function render() {
    $css = '';
    if (!$this->isRoot()) {
      $children = $this->getChildren();
      if ($children && $this->isBlock()) {
        $css .= $this->getValue() . '{';
      }
      foreach ($children as $child) {
        if (!$this->isBlock()) {
          $css .= $this->getValue() . ' ';
        }
        $css .= $child->render();
      }
      if ($children && $this->isBlock()) {
         $css .= '}';
      }
      if ($this->hasData()) {
        $cloned_data = clone $this->data;
        if (method_exists($cloned_data, 'createShorthands')) {
          $cloned_data->createShorthands();
        }
        $css .= $this->getValue() . '{';
        foreach ($cloned_data->getRules() as $rule) {
          $css .= $rule->render(\Sabberworm\CSS\OutputFormat::createCompact());
        }
        $css .= '}';
      }
    }
    else {
      foreach ($this->getChildren() as $child) {
        $css .= $child->render();
      }
    }
    return $css;
  }

  public function renderSCSS() {
    $scss = '';
    if (!$this->isRoot()) {
      $scss .= $this->getValue() . '{';
      if ($this->hasData()) {
        $cloned_data = clone $this->data;
        if (method_exists($cloned_data, 'createShorthands')) {
          $cloned_data->createShorthands();
        }
        foreach ($cloned_data->getRules() as $rule) {
          $scss .= $rule->render(\Sabberworm\CSS\OutputFormat::createCompact());
        }
      }
      $children = $this->getChildren();
      foreach ($children as $child) {
        $scss .= $child->renderSCSS();
      }
      $scss .= '}';
    }
    else {
      foreach ($this->getChildren() as $child) {
        $scss .= $child->renderSCSS();
      }
    }
    return $scss;
  }

  public function asFlattenedArray($parentKey = NULL) {
    $arr = [];
    if ($this->isRoot()) {
      foreach ($this->getChildren() as $child) {
        $arr += $child->asFlattenedArray();
      }
    }
    else {
      $key = $parentKey ? $parentKey . ' ' . $this->getValue() : $this->getValue();
      if ($this->hasData()) {
        foreach ($this->getData()->getRulesAssoc() as $rule) {
          $val = $rule->getValue();
          $arr[$key][$rule->getRule()] = is_string($val) ? $val : $val->render(\Sabberworm\CSS\OutputFormat::createCompact());
        }
      }
      $children = $this->getChildren();
      if ($children) {
        foreach ($children as $child) {
          if ($this->isBlock()) {
            if (!isset($arr[$key])) {
              $arr[$key] = [];
            }
            $arr[$key] += $child->asFlattenedArray();
          }
          else {
            $arr += $child->asFlattenedArray($key);
          }
        }
      }
    }
    return $arr;
  }
}
