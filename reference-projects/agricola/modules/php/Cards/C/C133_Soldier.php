<?php
namespace AGR\Cards\C;

use AGR\Core\Globals;

class C133_Soldier extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C133_Soldier';
    $this->name = clienttranslate('Soldier');
    $this->deck = 'C';
    $this->number = 133;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'During scoring, you get 1 bonus <SCORE> for each <STONE> + <WOOD> pair in your supply. You cannot score additional points for the resources scored with this card.'
      ),
    ];
    $this->players = '3+';
    $this->extraVp = true;
    $this->isCorbariusOrDulcinaria = true;
  }

  public function getMaxSetOfResources()
  {
    $player = $this->getPlayer();
    $wood = $player->countReserveResource(WOOD);
    $stone = $player->countReserveResource(STONE);
    $alreadyReserved = Globals::getReservedResourcesForScoring();
    if (array_key_exists($player->getId(), $alreadyReserved)) {
      $cannotUse = $alreadyReserved[$player->getId()];
      $wood -= $cannotUse[WOOD];
      $stone -= $cannotUse[STONE];
    }
    return min([$wood, $stone]);
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && $event['type'] == 'BeforeEndOfGame';
  }

  public function onPlayerBeforeEndOfGame($player, $event)
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
    return clienttranslate('Choose how many pairs of <STONE> + <WOOD> you want to score');
  }

  public function argsBonusScoringEffect()
  {
    return [
      'cardId' => $this->id,
      'max' => $this->getMaxSetOfResources(),
      'description' => clienttranslate(
        '${actplayer} must choose how many pairs of <STONE> + <WOOD> you want to score (Soldier)'
      ),
      'descriptionmyturn' => clienttranslate(
        'Choose how many pairs of <STONE> + <WOOD> you want to score (Soldier)'
      ),
    ];
  }

  public function actBonusScoringEffect($count)
  {
    if ($count == 0) {
      return;
    }
    $pid = $this->getPlayer()->getId();
    $max = $this->getMaxSetOfResources();
    if ($count > $max) {
      throw new \BgaVisibleSystemException('You don\'t have enough pairs of building resources to score');
    }
    $alreadyReserved = Globals::getReservedResourcesForScoring();
    if (array_key_exists($pid, $alreadyReserved)) {
      $cannotUse = &$alreadyReserved[$pid];
      $cannotUse[WOOD] += $count;
      $cannotUse[STONE] += $count;
    } else {
      $alreadyReserved[$pid] = [
        WOOD => $count,
        STONE => $count,
      ];
    }
    Globals::setReservedResourcesForScoring($alreadyReserved);
    Globals::setBonusC133($count);
  }

  public function computeBonusScore()
  {
    $bonus = Globals::getBonusC133();
    if ($bonus > 0) {
      $this->addBonusScoringEntry($bonus);
    }
  }
}