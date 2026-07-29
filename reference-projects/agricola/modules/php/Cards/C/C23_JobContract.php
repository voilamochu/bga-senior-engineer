<?php
namespace AGR\Cards\C;

use AGR\Core\Globals;
use AGR\Managers\ActionCards;
use AGR\Managers\Farmers;
use AGR\Managers\Meeples;
use AGR\Core\Notifications;
use AGR\Helpers\CardRulings;

class C23_JobContract extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C23_JobContract';
    $this->name = clienttranslate('Job Contract');
    $this->deck = 'C';
    $this->number = 23;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'If both are unoccupied, you can use the __Day Laborer__ and the adjacent __Lessons__ action space with a single person (in that order). Afterward, both spaces are considered occupied.'
      ),
    ];
    $this->prerequisite = clienttranslate('No Occupations');
    $this->occupationPrerequisites = ['max' => 0];
    $this->isCorbariusOrDulcinaria = true;
    $this->bannedStrong1or2p = true;

    $this->rulings = CardRulings::fromKeys([
      'JUNIOR_ARTIST_JOB_CONTRACT',
    ]);
  }

  public function isListeningTo($event)
  {
    if ($this->isActionEvent($event, 'PlaceFarmer', 'player', true)) {
      return $event['actionCardType'] == 'DayLaborer';
    }

    return $this->isPlayerEvent($event) && ($event['type'] ?? null) == 'EndOfRound';
  }

  public function onPlayerEndOfRound($player, $event)
  {
    Farmers::cleanupJobContractFake();
  }

  // Keep legacy for turn-based games
  public function isDoable($cId)
  {
    $player = $this->getPlayer();
    $card = ActionCards::get($cId);

    return $card->canBePlayed($player, null, true);
  }

  public function onPlayerImmediatelyAfterPlaceFarmer($player, $event)
  {
    $added = $player->computeModifiedPlacementConstraints();
    foreach ($added as $card) {
      if ($card == 'ActionLessons') {
        return [
          'countAsUse' => true,
          'type' => NODE_SEQ,
          'optional' => true,
          'childs' => [
            [
              'action' => SPECIAL_EFFECT,
              'args' => [
                'cardId' => $this->id,
                'method' => 'placeFakeFarmer',
                'args' => ['ActionLessons'],
              ],
            ],
            $this->useActionSpaceNode('ActionLessons'),
          ]
        ];
      }
    }

    // Keep legacy for turn-based games
    if (!$this->isDoable('ActionLessons')) {
      return;
    }

    return [
      'countAsUse' => true,
      'type' => NODE_SEQ,
      'optional' => true,
      'childs' => [
        [
          'action' => SPECIAL_EFFECT,
          'args' => [
            'cardId' => $this->id,
            'method' => 'placeFakeFarmer',
            'args' => ['ActionLessons'],
          ],
        ],
        $this->useActionSpaceNode('ActionLessons'),
      ]
    ];
  }

  public function placeFakeFarmer($space)
  {
    $player = $this->getPlayer();

    // Track fake farmer so can be removed by Basket Chair/Henpecked Husband/Godly Spouse
    $existing = Globals::getJobContractFake();
    //guard to avoid duplicate, possible with Basket Chair etc
    if (!empty($existing)) {
      $meeples = Meeples::getMany($existing, false);
      if ($meeples->count() > 0) {
        $deleted = Meeples::deleteResources($meeples);
        Notifications::silentDestroy($deleted);
      }
      Globals::setJobContractFake(null);
    }

    $ids = Meeples::createResourceInLocation(FARMER, $space, $player->getId(), null, null, 1, REMOVE);
    $fakeId = $ids[0] ?? null;

    if ($fakeId !== null) {
      Globals::setJobContractFake($fakeId);
      Notifications::accumulate(Meeples::getMany($ids), true);
    }
  }
}
