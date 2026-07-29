<?php
namespace AGR\Cards\E;

use AGR\Helpers\UsedText;
use AGR\Core\Globals;
use AGR\Core\Notifications;

class E58_LunchtimeBeer extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E58_LunchtimeBeer';
    $this->name = clienttranslate('Lunchtime Beer');
    $this->deck = 'E';
    $this->author = 'chris';
    $this->number = 58;
    $this->category = FOOD;
    $this->desc = [
      clienttranslate(
        'At the start of each harvest, you can choose to skip the field and breeding phase of that harvest and get exactly 1 <FOOD> instead.'
      ),
    ];
    $this->usedText = UsedText::get('HARVESTS_SKIPPED');
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) 
      && $event['type'] == 'StartHarvest';
  }

  public function onPlayerStartHarvest($player, $event)
  {
    return [
      'type' => NODE_SEQ,
      'optional' => true,
      'countAsUse' => true,
      'childs' => [
        $this->gainNode([FOOD => 1]),
        [
          'action' => SPECIAL_EFFECT,
          'args' => [
            'cardId' => $this->id,
            'method' => 'passFieldAndBreedingPhase',
          ]
        ]
      ]
    ];
  }

  public function passFieldAndBreedingPhase()
  {
    $player = $this->getPlayer();

    $pass = Globals::getPassFieldAndBreed() ?? [];
    $pass[] = $player->getId();
    Globals::setPassFieldAndBreed($pass);
    Notifications::message(clienttranslate('${player_name} will skip the field and breeding phase of this harvest'), [
      'player_name' => $player->getName(),
    ]);
  }

  public function getPassFieldAndBreedingPhaseDescription()
  {
    return clienttranslate('Skip field and breeding phase');
  }
}
