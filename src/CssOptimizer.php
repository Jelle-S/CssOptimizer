<?php

namespace Jelle_S\CssOptimizer;

use Jelle_S\CssOptimizer\Tree\CssNode;
use Jelle_S\Util\Combiner\ArrayKeyCombiner;
use Sabberworm\CSS\CSSList\CSSList;
use Sabberworm\CSS\OutputFormat;
use Sabberworm\CSS\Parser;
use Sabberworm\CSS\Property\AtRule;
use Sabberworm\CSS\RuleSet\AtRuleSet;
use Sabberworm\CSS\RuleSet\DeclarationBlock;
use Sabberworm\CSS\RuleSet\RuleSet;
use SebastianBergmann\CodeCoverage\Exception;

/**
 * Optimize css by minifying and combining selectors.
 *
 * @author Jelle Sebreghts
 */
class CssOptimizer {

  /**
   * The css parser.
   *
   * @var \Sabberworm\CSS\Parser
   */
  protected $cssParser;

  /**
   * Selector tree.
   *
   * @var \Jelle_S\CssOptimizer\Tree\CssNode
   */
  protected $selectorTree;

  /**
   * The maximum number of combinations to try for finding intersections.
   *
   * @var int
   */
  protected $combinationLimit;

  /**
   * The threshold. The minimum size of the intersections to search for.
   *
   * @var int
   */
  protected $threshold;

  /**
   * Creates a CssOptimizer.
   *
   * @param string $css
   *   The raw css or path to the css file to optimize.
   * @param int $threshold
   *   The threshold. The minimum size of the intersections to search for.
   * @param int $combinationLimit
   *   The maximum number of combinations to try for finding intersections.
   */
  public function __construct($css, $threshold = 5, $combinationLimit = NULL) {
    $this->threshold = $threshold;
    if ( (!stristr(PHP_OS, 'WIN') || strlen($css)<260) && is_file($css)) {
      $css = file_get_contents($css);
    }
    // Try to keep some sort of sane default based on the amount of data we get.
    if (is_null($combinationLimit)) {
      $combinationLimit = round(PHP_INT_MAX / pow((strlen($css) * 10) / $this->threshold, 3));
    }
    $this->combinationLimit = $combinationLimit;
    $this->cssParser = new Parser($css);
    $this->selectorTree = new CssNode();
  }

  /**
   * Optimizes the given css using the contructor parameters.
   */
  protected function optimize() {
    $this->buildSelectorTree();
    $flattened = $this->asFlattenedArray();
    $arrays_to_optimize = array();
    foreach ($flattened as $key => $flat) {
      if (is_array(reset($flat))) {
        $arrays_to_optimize[$key] = $flat;
      }
      else {
        $arrays_to_optimize['_main'][$key] = $flat;
      }
    }
    $optimized_arrs = [];
    foreach ($arrays_to_optimize as $key => $arrs) {
      $optimized_arrs[$key] = $this->optimizeArrays($arrs);
    }
    if (isset($optimized_arrs['_main'])) {
      $optimized_arrs += $optimized_arrs['_main'];
      unset($optimized_arrs['_main']);
    }
    $css = '';
    foreach ($optimized_arrs as $key => $arr) {
      $css .= $key . '{';
      foreach ($arr as $k => $a) {
        if (is_array($a)) {
          $css .= $k . '{';
          foreach ($a as $prop => $val) {
            $css .= $prop . ':' . $val . ';';
          }
          $css .= '}';
        }
        else {
          $css .= $k . ':' . $a . ';';
        }
      }
      $css .= '}';
    }
    $this->cssParser = new Parser($css);
  }

  /**
   * Helper function to optimize array representing css.
   *
   * @param array $arrs
   *   An array of arrays representing css blocks.
   *
   * @return array
   *   The optimized arrays.
   */
  protected function optimizeArrays($arrs) {
    $combiner = new ArrayKeyCombiner($arrs, $this->threshold, $this->combinationLimit);
    return $combiner->combine();
  }

  /**
   * Get the current css tree as a flattened array
   *
   * @return array
   *   The flattened array.
   */
  protected function asFlattenedArray() {
    return $this->selectorTree->asFlattenedArray();
  }

  /**
   * Render the current css tree.
   *
   * @return string
   *   The current css tree rendered as a css string.
   */
  public function renderMinifiedCSS() {
    $this->optimize();
    $doc = $this->cssParser->parse();
    $doc->createShorthands();
    return $doc->render(OutputFormat::createCompact());
  }

  /**
   * Render the current css tree as SCSS.
   *
   * @return string
   *   The current css tree rendered as a scss string.
   */
  public function renderSCSS() {
    $this->buildSelectorTree();
    return $this->selectorTree->renderSCSS();
  }

  /**
   * Build the css tree for a list.
   *
   * @param \Sabberworm\CSS\CSSList\CSSList $list
   *   The css list to create the tree for.
   * @param \Jelle_S\CssOptimizer\Tree\CssNode $parent
   *   The parent to attach the built tree to.
   *
   * @throws Exception
   *   When we encounter an unsupported css element.
   */
  protected function buildSelectorTree(CSSList $list = NULL, CssNode $parent = NULL) {
    if (is_null($list)) {
      $list = $this->cssParser->parse();
    }
    if (is_null($parent)) {
      $parent = $this->selectorTree;
    }
    foreach ($list->getContents() as $content) {
      switch (TRUE) {
        case $content instanceof CSSList:
          if ($content instanceof AtRule) {
            $child = $parent->find(["@{$content->atRuleName()} {$content->atRuleArgs()}"]);
            $child->setBlock(TRUE);
            $this->buildSelectorTree($content, $child);
          }
          else {
            throw new Exception('Not supported');
          }
          break;
        case $content instanceof RuleSet:
          $this->addRuleSetToSelectorTree($content, $parent);
          break;
      }
    }
  }

  /**
   * Add a css rule set to the css tree.
   *
   * @param \Sabberworm\CSS\RuleSet\RuleSet $ruleset
   *   The rule set to add.
   * @param \Jelle_S\CssOptimizer\Tree\CssNode $parent
   *   The parent to add it to.
   *
   * @throws \Exception
   *   When we encounter an unsupported ruleset.
   */
  protected function addRuleSetToSelectorTree(RuleSet $ruleset, CssNode $parent) {
    if ($ruleset instanceof AtRuleSet) {
      $child = $parent->find(["@{$ruleset->atRuleName()} {$ruleset->atRuleArgs()}"]);
      $child->setBlock(TRUE);
      $child->addData($ruleset);
    }
    else if ($ruleset instanceof DeclarationBlock) {
      $ruleset->expandShorthands();
      foreach ($ruleset->getSelectors() as $selector) {
        // Remove spaces around:
        //   - child selector: '>'
        //   - sibling selector: '~'
        //   - adjacent selector: '+'
        $selector = preg_replace("/\s*([\>\~\+])\s*/", "$1", (string) $selector);
        $child = $parent->find(array_filter(explode(' ', $selector)));
        $child->addData($ruleset);
      }
    }
    else {
      throw new Exception('Not supported');
    }
  }
}
