<?php
namespace AGR\Cards\C;
use AGR\Managers\PlayerCards;

class C80_RockyTerrain extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C80_RockyTerrain';
    $this->name = clienttranslate('Rocky Terrain');
    $this->deck = 'C';
    $this->number = 80;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [clienttranslate('Each time you plow a field (tile or card), you can also buy 1 <STONE> for 1 <FOOD>.')];
    $this->cost = [
      FOOD => 1,
    ];
    $this->isCorbariusOrDulcinaria = true;

    $this->rulings = [
      clienttranslate('Playing field cards counts as plowing a field.'),
    ];
  }
  
  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Plow') ||
      $this->isActionEvent($event, 'Improvement') ||
      $this->isActionEvent($event, 'Occupation');
  }

  public function onPlayerAfterPlow($player, $event)
  {
    return $this->payGainNode([FOOD => 1], [STONE => 1]);
  }
  
  public function onPlayerAfterImprovement($player, $event)
  {
    $card = PlayerCards::get($event['cardId']);
    if ($card->isField()) {
      return $this->payGainNode([FOOD => 1], [STONE => 1]);
    }
  }

  public function onPlayerAfterOccupation($player, $event)
  {
    $card = PlayerCards::get($event['cardId']);
    if ($card->isField()) {
      return $this->payGainNode([FOOD => 1], [STONE => 1]);
    }
  }
}
