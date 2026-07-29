<?php
namespace AGR\Cards\D;
use AGR\Core\Engine;
use AGR\Helpers\Utils;
use AGR\Managers\PlayerCards;

class D10_StorksNest extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D10_StorksNest';
    $this->name = clienttranslate("Stork's Nest");
    $this->deck = 'D';
    $this->number = 10;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'In the returning home phase of each round, if you have more rooms than people, you can pay 1 <FOOD> to take a __Family Growth__ action.'
      ),
    ];
    $this->cost = [
      REED => 1,
    ];
    $this->prerequisite = clienttranslate('5 Occupations');
    $this->occupationPrerequisites = ['min' => 5];
    $this->bannedWeak1or2p = true;

    $this->rulings = [
      clienttranslate('This effect can only be used once per round.'),
    ];
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && $event['type'] == 'ReturnHome';
  }

  public function onPlayerReturnHome($player, $event)
  {

    if ($player->countRooms() <= $player->countFarmers()) {
      return;
    }

    return [
      'type' => NODE_SEQ,
      'pId' => $this->pId,
      'optional' => true,
      'childs' => [
        [
          'action' => PAY,
          'pId' => $this->pId,
          'args' => [
            'nb' => 1,
            'costs' => Utils::formatCost([FOOD => 1]),
            'source' => clienttranslate('Stork Nest effect'),
            'cardId' => $this->id,
          ],
        ],
        [
          'action' => WISHCHILDREN,
          'cardId' => $this->id,
          'args' => ['constraints' => ['freeRoom'], 'insideHouse' => true],
          'pId' => $this->pId,
          'source' => $this->name,
        ],
        [
          'action' => SPECIAL_EFFECT,
          'args' => [
            'cardId' => $this->id,
            'method' => 'getSwimmingClassBonus',
            'args' => [],
          ],
        ],
      ],
    ];
  }

  public function getSwimmingClassBonus()
  {
    $player = $this->getPlayer();

    if (!$player->hasPlayedCard('A35_SwimmingClass') || !PlayerCards::get('A35_SwimmingClass')->isFlagged()) {
      return;
    }

    $flow = PlayerCards::get('A35_SwimmingClass')->gainNode([SCORE => 2]);
    Engine::insertAsChild($flow);
  }
}
