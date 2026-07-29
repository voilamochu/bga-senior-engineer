<?php
namespace AGR\Cards\E;
use AGR\Managers\Meeples;

class E68_CherryOrchard extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E68_CherryOrchard';
    $this->name = clienttranslate('Cherry Orchard');
    $this->deck = 'E';
    $this->author = 'inoshishi';
    $this->number = 68;
    $this->category = 'CROPS_-_VEGETABLE';
    $this->desc = [
      clienttranslate(
        'This card is a field on which you can only sow and harvest wood as you would grain. Each time you harvest the last <WOOD> from this card, you also get 1 <VEGETABLE>.'
      ),
    ];
    $this->holder = true;
    $this->field = true;
  }

  public function getFieldDetails()
  {
    return [
      'constraints' => WOOD,
    ];
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Reap') && 
      $this->harvestedWood($event) &&
      (Meeples::getResourcesOnCard($this->id, null, WOOD)->count() == 0);
  }

  public function harvestedWood($event)
  {
    foreach ($event['crops'] as $meeple) {
      if ($meeple['type'] == WOOD && $meeple['originalLocation'] == $this->id)
        return true;
    }
    return false;
  }

  public function onPlayerAfterReap($player, $event)
  {
    return $this->gainNode([VEGETABLE => 1]);
  }
}
