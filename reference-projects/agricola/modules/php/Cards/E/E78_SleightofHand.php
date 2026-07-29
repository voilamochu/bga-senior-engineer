<?php
namespace AGR\Cards\E;
use AGR\Core\Engine;

class E78_SleightofHand extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E78_SleightofHand';
    $this->name = clienttranslate('Sleight of Hand');
    $this->deck = 'E';
    $this->author = 'beso';
    $this->number = 78;
    $this->category = 'BUILDING_RESOURCES_-_REED';
    $this->desc = [
      clienttranslate(
        'When you play this card, you can immediately exchange up to 4 building resources for an equal number of other building resources.'
      ),
    ];
    $this->prerequisite = clienttranslate('3 Occupations');
    $this->occupationPrerequisites = ['min' => 3];
  }

  public function onBuy($player)
  {
    return [
      'action' => SPECIAL_EFFECT,
      'args' => [
        'cardId' => $this->id,
        'method' => 'tradeResources',
      ],
    ];
  }

  public function getTradeResourcesDescription()
  {
    return clienttranslate('Exchange building resources');
  }

  public function argsTradeResources()
  {
    $player = $this->getPlayer();

    $reserve = [WOOD => $player->countReserveResource(WOOD),
                CLAY => $player->countReserveResource(CLAY),
                REED => $player->countReserveResource(REED),
                STONE => $player->countReserveResource(STONE),
               ];

    return [
      'cardId' => $this->id,
      'description' => clienttranslate('${actplayer} may exchange up to 4 building resources'),
      'descriptionmyturn' => clienttranslate('${you} may exchange up to 4 building resources'),
      'reserve' => $reserve,
    ];
  }

  public function actTradeResources($discard, $receive)
  {
    $pay = [];
    $gain = [];

    foreach ($discard as $d) {
      if (array_key_exists($d, $pay)) {
        $pay[$d]++;
      } else {
        $pay[$d] = 1;
      }
    }

    foreach ($receive as $r) {
      if (array_key_exists($r, $gain)) {
        $gain[$r]++;
      } else {
        $gain[$r] = 1;
      }
    }

    $flow = $this->payGainNode($pay, $gain, null, false);
    Engine::insertAsChild($flow);
  }
}
