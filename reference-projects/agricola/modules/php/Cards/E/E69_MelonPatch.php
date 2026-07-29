<?php
namespace AGR\Cards\E;

use AGR\Managers\Meeples;

class E69_MelonPatch extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E69_MelonPatch';
    $this->name = clienttranslate('Melon Patch');
    $this->deck = 'E';
    $this->author = 'azwandahlan';
    $this->number = 69;
    $this->category = 'CROPS_-_VEGETABLE';
    $this->desc = [
      clienttranslate(
        'This card is a field that can only grow vegetables. Each time you harvest the last <VEGETABLE> from this card, you can plow 1 field.'
      ),
    ];
    $this->prerequisite = clienttranslate('2 Occupations');
    $this->occupationPrerequisites = ['min' => 2];
    $this->holder = true;
    $this->field = true;
  }

  public function getFieldDetails()
  {
    return [
      'constraints' => VEGETABLE,
    ];
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Reap') && 
      $this->harvestedVeg($event) &&
      (Meeples::getResourcesOnCard($this->id, null, VEGETABLE)->count() == 0);
  }

  public function harvestedVeg($event)
  {
    foreach ($event['crops'] as $meeple) {
      if ($meeple['type'] == VEGETABLE && $meeple['originalLocation'] == $this->id)
        return true;
    }
    return false;
  }

  public function onPlayerAfterReap($player, $event)
  {
    return [
      'action' => PLOW,
      'cardId' => $this->id,
      'optional' => true,
      'source' => $this->name,
    ];
  }
}
