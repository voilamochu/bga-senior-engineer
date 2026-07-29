<?php
namespace AGR\Cards\E;
use AGR\Core\Globals;
use AGR\Helpers\Utils;

class E28_Bookmark extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E28_Bookmark';
    $this->name = clienttranslate('Bookmark');
    $this->deck = 'E';
    $this->author = 'beso';
    $this->number = 28;
    $this->category = 'ACTION_-_OCCUPATION';
    $this->desc = [
      clienttranslate(
        'Add 3 to the current round and mark the corresponding round space. At the start of that round, you can play 1 occupation without paying an occupation cost.'
      ),
    ];
    $this->cost = [
      WOOD => 1,
    ];
  }

  public function onBuy($player)
  {
    $this->setExtraDatas('triggerRound',Globals::getTurn()+3);
    $this->setInfobox(clienttranslate('Round ') . $this->getExtraDatas('triggerRound'));
  }

  public function getReceiveFlow($meeple)
  {
    return $this->getReceiveFlowWithCost(
      $meeple,
      Utils::formatCost([FOOD => 999]),
      clienttranslate('Please pass it, it is only the round marker for Bookmark')
    );
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) &&
      $event['type'] == 'StartOfTurn' &&
      Globals::getTurn()== $this->getExtraDatas('triggerRound');
  }

  public function onPlayerStartOfTurn($player, $event)
  {
    return [
      'action' => OCCUPATION,
      'optional' => true,
      'args' => [
        'cost' => [],
        'source' => $this->name,
      ],
    ];
  }
}
