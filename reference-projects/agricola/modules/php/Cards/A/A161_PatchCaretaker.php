<?php
namespace AGR\Cards\A;
use AGR\Managers\Farmers;
use AGR\Managers\ActionCards;

class A161_PatchCaretaker extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A161_PatchCaretaker';
    $this->name = clienttranslate('Patch Caretaker');
    $this->deck = 'A';
    $this->number = 161;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you use an accumulation space while already having used another accumulation space for the same type of good that work phase, you also get 1 <VEGETABLE>.'
      ),
    ];
    $this->players = '4+';
    $this->isArtifexOrBubulcus = true;
  }

    public function isListeningTo($event)
  {
    return $this->isBeforeCollectEvent($event, null);
  }
  
  public function onPlayerPlaceFarmer($player, $event)
  {
    $types = [WOOD, CLAY, STONE, FOOD, SHEEP, PIG, CATTLE];
    foreach ($types as $type) {
      if ($this->isBeforeCollectEvent($event, $type)) {
        $card = $event['actionCardId'];
        $spaces = ActionCards::getAccumulationSpaces($type);
        foreach ($spaces as $space) {
          if (!Farmers::getOnCard($space->getId(), $player->getId())->empty() && $space->getId() != $card) {
            return $this->gainNode([VEGETABLE => 1]);
          }
        }
      }
    }
  }
}
