<?php
namespace AGR\Cards\C;

use AGR\Helpers\UsedText;

class C70_LettucePatch extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C70_LettucePatch';
    $this->name = clienttranslate('Lettuce Patch');
    $this->deck = 'C';
    $this->number = 70;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      clienttranslate(
        'This card is a field that can only grow vegetables. You can immediately turn each <VEGETABLE> you harvested from this card into 4 <FOOD>.'
      ),
    ];
    $this->vp = 1;
    $this->prerequisite = clienttranslate('3 Occupations');
    $this->occupationPrerequisites = ['min' => 3];
    $this->holder = true;
    $this->field = true;
    $this->usedText = UsedText::get('VEGETABLE_CONVERTED');
  }

  public function getFieldDetails()
  {
    return [
      'constraints' => VEGETABLE,
    ];
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Reap') && $this->countVegetables($event) > 0;
  }

  public function countVegetables($event)
  {
    $n = 0;
    foreach ($event['crops'] as $meeple) {
      $n += $meeple['type'] == \VEGETABLE && $meeple['originalLocation'] == $this->id ? 1 : 0;
    }
    return $n;
  }

  public function onPlayerAfterReap($player, $event)
  {
    $childs = [];
    $n = $this->countVegetables($event);
    for ($i = 1; $i <= $n; $i++) {
      $childs[] = $this->payGainNode([\VEGETABLE => $i], [FOOD => 4 * $i], $this->name, false);
    }
    return ['type' => \NODE_XOR, 'childs' => $childs, 'source' => $this->name, 'optional' => true, 'countAsUse' => true, 'doableRequiresChild' => true];
  }
}
