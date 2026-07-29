<?php
namespace AGR\Cards\C;
use AGR\Managers\Players;

class C144_ReedRoofRenovator extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C144_ReedRoofRenovator';
    $this->name = clienttranslate('Reed Roof Renovator');
    $this->deck = 'C';
    $this->number = 144;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time another player renovates, you immediately get 1 <REED> from the general supply. When you play this card in a 3-player game, you immediately get 1 <REED>.'
      ),
    ];
    $this->players = '3+';
  }

  public function onBuy($player)
  {
    if (Players::count() == 3) {
      return $this->gainNode([REED => 1]);
    }
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Renovation', 'opponent', true);
  }

  public function onOpponentImmediatelyAfterRenovation($player, $event)
  {
    $cardOwner = $this->getPlayer();
    return [
      'type' => NODE_SEQ,
      'childs' => [
        [
          'action' => GAIN,
          'args' => [REED => 1, 'pId' => $cardOwner->getId()],
          'source' => $this->name,
        ],
      ],
    ];
  }
}
