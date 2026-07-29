<?php
namespace AGR\Cards\E;

use AGR\Managers\Meeples;
use AGR\Core\Engine;

class E148_Lazybones extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E148_Lazybones';
    $this->name = clienttranslate('Lazybones');
    $this->deck = 'E';
    $this->author = 'cunning';
    $this->number = 148;
    $this->category = 'FARMYARD_-_PLACE_FOR_ANIMALS';
    $this->desc = [
      clienttranslate(
        'Place (up to) 1 <STABLE> each on __Grain Seeds__, __Farmland__, __Day Laborer__, and __Farm Expansion__. Build the <STABLE> at no cost when another player uses that action space.'
      ),
    ];
    $this->players = '4+';
  }

  public function onBuy($player)
  {
    if ($player->countStablesInReserve() == 0) {
      return;
    }

    return [
      'action' => SPECIAL_EFFECT,
      'args' => [
        'cardId' => $this->id,
        'method' => 'moveStables',
      ],
    ];
  }

  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'GrainSeeds', 'opponent')
      || $this->isActionCardEvent($event, 'Farmland', 'opponent')
      || $this->isActionCardEvent($event, 'DayLaborer', 'opponent')
      || $this->isActionCardEvent($event, 'FarmExpansion', 'opponent');
  }

  public function onOpponentPlaceFarmer($player, $event)
  {
    $space = $event['actionCardId'];
    $stables = Meeples::getResourcesOnCard($space, null, STABLE);

    if ($stables->empty()) {
      return;
    }

    $node = $this->receiveNode($stables->first()['id'], false, $this->getPlayer());
    $node['args']['checkpoint'] = true;
    return $node;
  }

  public function argsMoveStables()
  {
    return [
      'cardId' => $this->id,
      'description' => clienttranslate('${actplayer} may place stables on action spaces (Lazybones)'),
      'descriptionmyturn' => clienttranslate('${you} may place stables on action spaces (Lazybones)'),
      'spaces' => ['ActionGrainSeeds', 'ActionFarmland', 'ActionDayLaborer', 'ActionFarmExpansion'],
      'max' => $this->getPlayer()->countStablesInReserve(),
    ];
  }

  public function actMoveStables($spaces)
  {
    $flow = $this->moveResourcesToSpaceNode([STABLE => 1], $spaces);
    Engine::insertAsChild($flow);
  }
}
