<?php
namespace AGR\Cards\E;
use AGR\Managers\PlayerCards;

class E165_MasterHuntsman extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E165_MasterHuntsman';
    $this->name = clienttranslate('Master Huntsman');
    $this->deck = 'E';
    $this->author = 'nwoll';
    $this->number = 165;
    $this->category = 'ANIMALS_-_WILD_BOAR';
    $this->desc = [
      clienttranslate('When you play this card and each time you build a major improvement, you get 1 <PIG>.'),
    ];
    $this->players = '4+';
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Improvement') &&
      PlayerCards::get($event['cardId'])->hasType(MAJOR);

  }

  public function onPlayerAfterImprovement($player, $event)
  {
    return $this->gainNode([PIG => 1]);
  }

  public function onBuy($player)
  {
    return $this->gainNode([PIG => 1]);
  }
}
