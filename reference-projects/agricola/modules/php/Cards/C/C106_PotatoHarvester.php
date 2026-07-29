<?php
namespace AGR\Cards\C;

class C106_PotatoHarvester extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C106_PotatoHarvester';
    $this->name = clienttranslate('Potato Harvester');
    $this->deck = 'C';
    $this->number = 106;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'When you play this card, you immediately get 3 <FOOD>. For each <VEGETABLE> you get from your fields during the field phase of the harvest, you get 1 additional <FOOD>.'
      ),
    ];
    $this->players = '1+';
  }

  public function onBuy($player)
  {
    return $this->gainNode([FOOD => 3]);
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Reap') && $this->countVegetables($event) > 0;
  }

  public function countVegetables($event)
  {
    $n = 0;
    foreach($event['crops'] as $meeple){
      $n += $meeple['type'] == \VEGETABLE? 1 : 0;
    }
    return $n;
  }

  public function onPlayerAfterReap($player, $event)
  {
    $n = $this->countVegetables($event);
    return $this->gainNode([FOOD => $n]);
  }
}
