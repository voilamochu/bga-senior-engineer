<?php
namespace AGR\Cards\A;
use AGR\Managers\PlayerCards;

class A109_SmallTrader extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A109_SmallTrader';
    $this->name = clienttranslate('Small Trader');
    $this->deck = 'A';
    $this->number = 109;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you take a __Major or Minor Improvement__ action, if you play a card from your hand instead of taking a major improvement from the board, you also get 3 <FOOD>.'
      ),
    ];
    $this->players = '1+';
    $this->isArtifexOrBubulcus = true;

    $this->rulings = [
      clienttranslate('This card only triggers if you get a literal __Major or Minor Improvement__ action.'),
      clienttranslate('Does not trigger if you play a minor improvement in any other way, for example using the __Meeting Place__ action space.'),
    ];
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Improvement') &&
      PlayerCards::get($event['cardId'])->getType() == MINOR;
  }

  public function onPlayerAfterImprovement($player, $event)
  {
    if ($event['actionType'] == 'MajorOrMinor') {
      return $this->gainNode([FOOD => 3]);
    }
  }  
}
