<?php
namespace AGR\Cards\A;

use AGR\Models\Occupation;
use AGR\Core\Globals;
use AGR\Managers\Players;

class A136_DrudgeryReeve extends Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A136_DrudgeryReeve';
    $this->name = clienttranslate('Drudgery Reeve');
    $this->deck = 'A';
    $this->number = 136;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'If there are still 1/3/6/9 complete rounds left to play, you immediately get 1/2/3/4 <WOOD>. During scoring, each player with 1+/2+/3+ building resources of each type gets 1/3/5 bonus <SCORE>.'
      ),
    ];
    $this->players = '3+';
    $this->extraVp = true;
    $this->sharedScoring = true;
    $this->isArtifexOrBubulcus = true;

    $this->rulings = [
      clienttranslate('The resources worth bonus points for the __Joinery__, __Pottery__ or __Basketmaker\'s Workshop__ are spent and do not count towards this card\'s bonus effect.'),
      clienttranslate('These resources do count towards the tiebreaker.'),
    ];
  }

  public function onBuy($player)
  {
    $remainingTurns = 14 - Globals::getTurn();
    $woodMap = [0, 1, 1, 2, 2, 2, 3, 3, 3, 4];
    $toGain = $woodMap[$remainingTurns] ?? 4;

    if ($toGain != 0) {
      return $this->gainNode([WOOD => $toGain]);
    }
  }

  public function isListeningTo($event)
  {
    return $event['type'] == 'BeforeEndOfGame';
  }

  public function getMaxSetOfResources()
  {
    $player = Players::getActive();
    $wood = $player->countReserveResource(WOOD);
    $clay = $player->countReserveResource(CLAY);
    $stone = $player->countReserveResource(STONE);
    $reed = $player->countReserveResource(REED);
    $alreadyReserved = Globals::getReservedResourcesForScoring();
    if (array_key_exists($player->getId(), $alreadyReserved)) {
      $cannotUse = $alreadyReserved[$player->getId()];
      $wood -= $cannotUse[WOOD] ?? 0;
      $clay -= $cannotUse[CLAY] ?? 0;
      $stone -= $cannotUse[STONE] ?? 0;
      $reed -= $cannotUse[REED] ?? 0;
    }
    return min([$wood, $clay, $stone, $reed, 3]);
  }

  public function onBeforeEndOfGame($player, $event)
  {
    $n = $this->getMaxSetOfResources();
    if ($n > 0) {
      return
        [
          'action' => SPECIAL_EFFECT,
          'args' => [
            'cardId' => $this->id,
            'method' => 'bonusScoringEffect',
          ],
        ];
    }
  }

  public function getBonusScoringEffectDescription()
  {
    return clienttranslate('Choose how many sets of building resources to score');
  }

  public function argsBonusScoringEffect()
  {
    return [
      'cardId' => $this->id,
      'max' => $this->getMaxSetOfResources(),
      'description' => clienttranslate(
        '${actplayer} must choose how many sets of building resources to score (Drudgery Reeve)'
      ),
      'descriptionmyturn' => clienttranslate(
        'Choose how many sets of building resources to score (Drudgery Reeve)'
      ),
    ];
  }

  public function actBonusScoringEffect($count)
  {
    $pid = Players::getActive()->getId();
    $max = $this->getMaxSetOfResources();
    if ($count > $max) {
      throw new \BgaVisibleSystemException('You don\'t have enough sets of building resources to score');
    }
    if ($count == 0) {
      return;
    }
    $alreadyReserved = Globals::getReservedResourcesForScoring();
    if (array_key_exists($pid, $alreadyReserved)) {
      $cannotUse = &$alreadyReserved[$pid];
      $cannotUse[WOOD] = ($cannotUse[WOOD] ?? 0) + $count;
      $cannotUse[CLAY] = ($cannotUse[CLAY] ?? 0) + $count;
      $cannotUse[STONE] = ($cannotUse[STONE] ?? 0) + $count;
      $cannotUse[REED] = ($cannotUse[REED] ?? 0) + $count;
    } else {
      $alreadyReserved[$pid] = [
        WOOD => $count,
        CLAY => $count,
        STONE => $count,
        REED => $count,
      ];
    }
    Globals::setReservedResourcesForScoring($alreadyReserved);

    $map = [0, 1, 3, 5];
    $bonus = $map[$count];
    $a136bonus = Globals::getBonusA136();
    $a136bonus[$pid] = $bonus;
    Globals::setBonusA136($a136bonus);
  }

  public function computeBonusScore()
  {
    $a136bonus = Globals::getBonusA136();
    $bonusPlayers = [];

    foreach (Players::getAll() as $player) {
      if (array_key_exists($player->getId(), $a136bonus)) {
        $bonus = $a136bonus[$player->getId()];
        if ($bonus > 0) {
          $bonusPlayers[$player->getId()] = $bonus;
        }
      }
    }

    foreach ($bonusPlayers as $player => $bonus) {
      $this->addBonusScoringEntry($bonus, null, $player);
    }
  }
}