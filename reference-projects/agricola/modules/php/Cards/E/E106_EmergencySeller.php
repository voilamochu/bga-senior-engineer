<?php

namespace AGR\Cards\E;

use AGR\Core\Engine;
use AGR\Models\Occupation;

class E106_EmergencySeller extends Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E106_EmergencySeller';
    $this->name = clienttranslate('Emergency Seller');
    $this->deck = 'E';
    $this->author = 'nwoll';
    $this->number = 106;
    $this->category = FOOD;
    $this->desc = [
      clienttranslate('When you play this card, you can immediately turn as many building resources into food as you have people:'),
      '<WOOD>/<CLAY> <ARROW> 2 <FOOD>',
      '<REED>/<STONE> <ARROW> 3 <FOOD>'
    ];
    $this->players = '1+';
  }

  public function onBuy($player)
  {
    $reserve = $player->getAllReserveResources();
    if ($reserve[WOOD] + $reserve[CLAY] + $reserve[REED] + $reserve[STONE] === 0) {
      return null;
    }

    return [
      'action' => SPECIAL_EFFECT,
      'optional' => true,
      'args' => [
        'cardId' => $this->id,
        'method' => 'sellResources',
      ],
    ];
  }

  public function getSellResourcesDescription()
  {
    return clienttranslate('Sell building resources');
  }

  public function argsSellResources()
  {
    $player = $this->getPlayer();

    return [
      'cardId' => $this->id,
      'description' => clienttranslate('${actplayer} may sell up to ${nb} building resources'),
      'descriptionmyturn' => clienttranslate('${you} may sell up to ${nb} building resources'),
      'nb' => $player->countFarmers(),
      'reserve' => [
        WOOD => $player->countReserveResource(WOOD),
        CLAY => $player->countReserveResource(CLAY),
        REED => $player->countReserveResource(REED),
        STONE => $player->countReserveResource(STONE),
      ],
    ];
  }

  public function actSellResources($discard)
  {
    if (empty($discard)) {
      return;
    }

    $player = $this->getPlayer();
    if (count($discard) > $player->countFarmers()) {
      throw new \BgaVisibleSystemException('Emergency Seller: selling more than people');
    }

    $pay = [];
    $food = 0;
    foreach ($discard as $res) {
      if (!in_array($res, [WOOD, CLAY, REED, STONE])) {
        throw new \BgaVisibleSystemException('Emergency Seller: invalid resource ' . $res);
      }
      $pay[$res] = ($pay[$res] ?? 0) + 1;
      $food += in_array($res, [REED, STONE]) ? 3 : 2;
    }

    foreach ($pay as $res => $amount) {
      if ($player->countReserveResource($res) < $amount) {
        throw new \BgaVisibleSystemException('Emergency Seller: not enough ' . $res);
      }
    }

    Engine::insertAsChild($this->payGainNode($pay, [FOOD => $food], null, false));
  }
}
