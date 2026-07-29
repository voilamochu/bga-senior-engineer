<?php
namespace AGR\Cards\A;

use AGR\Helpers\UsedText;
use AGR\Managers\ActionCards;
use AGR\Managers\Farmers;
use AGR\Helpers\Utils;

class A151_Minstrel extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A151_Minstrel';
    $this->name = clienttranslate('Minstrel');
    $this->deck = 'A';
    $this->number = 151;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'At the start of each returning home phase, if only one action space card on round space 1 to 4 is unoccupied, you can use that action space.'
      ),
    ];
    $this->players = '4+';
    $this->isArtifexOrBubulcus = true;
    $this->bannedWeak3or4p = true;
    $this->usedText = UsedText::get('BONUS_ACTIONS');
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && 
      $event['type'] == 'StartReturnHome';
  }

  public function onPlayerStartReturnHome($player, $event)
  {
    $unoccupied = [];

    foreach (['ActionFencing', 'ActionGrainUtilization', 'ActionSheepMarket', 'ActionMajorImprovement'] as $space) {
      $card = Utils::getActionCard($space);
      if (!$card->isVisible()) {
        continue;
      }

      if ((Farmers::getOnCard($space)->empty())) {
        $unoccupied[] = $space;
      }
    }

    if (count($unoccupied) != 1) {
      return;
    }
    if (!ActionCards::get($unoccupied[0])->canBePlayed($player, null, true)) {
      return;
    }

    return [
      'type' => NODE_SEQ,
      'optional' => true,
      'countAsUse' => true,
      'childs' => [
        $this->useActionSpaceNode($unoccupied[0]),
      ]
    ];
  }
}
