<?php
namespace AGR\Cards\C;

use AGR\Core\Notifications;

class C99_GardenDesigner extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C99_GardenDesigner';
    $this->name = clienttranslate('Garden Designer');
    $this->deck = 'C';
    $this->number = 99;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'At the start of scoring, you can place <FOOD> in empty fields. You get 1/2/3 bonus <SCORE> for each field in which you place 1/4/7 <FOOD>.'
      ),
    ];
    $this->players = '1+';
    $this->isCorbariusOrDulcinaria = true;
    $this->extraVp = true;
    $this->bannedStrong1or2p = true;
    $this->bannedStrong3or4p = true;
  }
  public function onBuy($player)
  {
    $this->setExtraDatas('foodFields', 0);
    return;
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && $event['type'] == 'BeforeEndOfGame';
  }

  public function getExtraVpNode($food, $bonus)
  {
    return [
      'type' => NODE_SEQ,
      'childs' => [
        $this->payGainNode([FOOD => $food], [SCORE => $bonus], null, false),
        [
          'action' => SPECIAL_EFFECT,
          'args' => [
            'cardId' => $this->id,
            'method' => 'createFood',
            'args' => [],
          ]
        ]
      ]
    ];
  }

  public function onPlayerBeforeEndOfGame($player, $event)
  {
    $emptyFields = $player->board()->getEmptyFields();
    if (count($emptyFields) == 0) {
      return;
    }
    $childs = [];
    $exchange = [
      'type' => NODE_XOR,
      'optional' => true,
      'doableRequiresChild' => true,
      'childs' => [
        $this->getExtraVpNode(1, 1),
        $this->getExtraVpNode(4, 2),
        $this->getExtraVpNode(7, 3),
      ]
    ];
    for ($i = 1; $i <= count($emptyFields); $i++) {
      $childs[] = $exchange;
    }
    return [
      'type' => NODE_SEQ,
      'optional' => true,
      'childs' => $childs,
    ];

  }

  public function createFood()
  {
    $player = $this->getPlayer();
    $this->setExtraDatas('foodFields', $this->getExtraDatas('foodFields') + 1);
    Notifications::updateDropZones($player);
    $player->forceReorganizeIfNeeded();
  }
}
