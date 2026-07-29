<?php
namespace AGR\Core\Engine;

/*
 * OrNode: a class that represent an Node with a choice (parallel)
 */
class OrNode extends AbstractNode
{
  public function __construct($infos = [], $childs = [])
  {
    parent::__construct($infos, $childs);
    $this->infos['type'] = NODE_OR;
  }

  /**
   * The description of the node is the sequence of description of its children
   */
  public function getDescriptionSeparator()
  {
    return ' + ';
  }

  /**
   * An OR node is doable if at least one of its child is doable (or if the OR node itself is optional)
   */
  public function isDoable($player, $ignoreResources = false)
  {
    return $this->isOptional() ||
      $this->childsReduceOr(function ($child) use ($player, $ignoreResources) {
        return $child->isDoable($player, $ignoreResources);
      });
  }

  /**
   * An OR node become optional as soon as one child is resolved
   */
  public function isOptional()
  {
    return parent::isOptional() ||
      $this->childsReduceOr(function ($child) {
        return $child->isResolved() && $child->getResolutionArgs() != PASS;
      });
  }

  /**
   * If at least one child was resolved already, other become optional
   */
  public function areChildrenOptional()
  {
    return $this->childsReduceOr(function ($child) {
      return $child->isResolved() && $child->getResolutionArgs() != PASS;
    });
  }

  /**
   * An OR node is resolved either when marked as resolved, either when all children are resolved already
   */
  public function isResolved()
  {
    return parent::isResolved() ||
      $this->childsReduceAnd(function ($child) {
        return $child->isResolved();
      });
  }

  /**
   * Drill Harrow bug. Drill Harrow always makes sow possible.
   * If you take Grain Utilization and bake, you are forced to sow (if you can't you are stuck).
   * Allow the second node to be passed if flag is present.
   */  
  public function getChoices($player = null, $ignoreResources = false)
    {
      $choices = parent::getChoices($player, $ignoreResources);

      if (($this->infos['forcePassAfterOne'] ?? false) &&
          $this->areChildrenOptional() &&
          count($choices) == 1 &&
          !isset($choices[PASS])) {
        $choices[PASS] = [
          'id' => PASS,
          'description' => clienttranslate('Pass'),
          'args' => [],
        ];
      }

      return $choices;
    }
}
