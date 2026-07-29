<?php
namespace AGR\Cards\E;

class E72_ArtichokeField extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E72_ArtichokeField';
    $this->name = clienttranslate('Artichoke Field');
    $this->deck = 'E';
    $this->author = 'minnie';
    $this->number = 72;
    $this->category = 'CROPS_-_GRAIN_AND_VEGETABLE';
    $this->desc = [
      clienttranslate(
        'This card is a field. During the field phase of each harvest, if you harvest at least 1 good from this card, you also get 1 <FOOD>.'
      ),
    ];
    $this->vp = 1;
    $this->cost = [
      WOOD => 1,
    ];
    $this->prerequisite = clienttranslate('2 Occupations');
    $this->occupationPrerequisites = ['min' => 2];
    $this->holder = true;
    $this->field = true;
  }

  public function getFieldDetails()
  {
    return [
      'constraints' => null,
    ];
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Reap') && (($event['trigger'] ?? null) == HARVEST) && 
      $this->harvested($event);
  }

  public function harvested($event)
  {
    foreach ($event['crops'] as $meeple) {
      if ($meeple['originalLocation'] == $this->id)
        return true;
    }
    return false;
  }

  public function onPlayerAfterReap($player, $event)
  {
    return $this->gainNode([FOOD => 1]);
  }
}
