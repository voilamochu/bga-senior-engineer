<?php
namespace AGR\Cards\C;
use AGR\Managers\Players;
use AGR\Managers\PlayerCards;

class C9_AutomaticWaterTrough extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C9_AutomaticWaterTrough';
    $this->name = clienttranslate('Automatic Water Trough');
    $this->deck = 'C';
    $this->number = 9;
    $this->category = LIVESTOCK_PROVIDER;
    $this->desc = [
      clienttranslate(
        'If you can accommodate the animal, you can immediately buy 1 <SHEEP>/<PIG>/<CATTLE> for 0/1/2 <FOOD>.'
      ),
    ];
    $this->passing = true;
    $this->cost = [
      WOOD => 1,
    ];
    $this->isCorbariusOrDulcinaria = true;
  }

  public function onBuy($player)
  {
    $map = [SHEEP => 0, PIG => 1, CATTLE => 2];
    $valid = $this->getValidAnimals();
    if ($valid == []) {
      return;
    }
    
    $childs = [];
    foreach ($valid as $type) {
      $cost = $map[$type];
      if ($cost == 0) {
        $childs[] = $this->gainNode([$type => 1]);
      } else {
        $childs[] = $this->payGainNode([FOOD => $map[$type]], [$type => 1], null, false);
      }
    }

    return [
      'type' => NODE_XOR,
      'optional' => true,
      'childs' => $childs,
    ];
  }

  public function getValidAnimals()
  {
    $player = Players::getActive();
    $zones = $player->board()->getAnimalsDropZonesWithAnimals();
    $valid = [];

    foreach ([SHEEP, PIG, CATTLE] as $type) {
      // Search zone of same animal type with an empty slot
      $sameFound = false;
      foreach ($zones as &$zone) {
        if ($zone['type'] == 'card' && method_exists(PlayerCards::get($zone['card_id']), 'canAcceptAnimal')) {
          if (PlayerCards::get($zone['card_id'])->canAcceptAnimal($zone, ['type' => $type])) {
            $sameFound = true;
            $valid[] = $type;
            break;
          }
        } elseif ($zone['animals'] < $zone['capacity'] && $zone[$type] > 0) {
          $sameFound = true;
          $valid[] = $type;
          break;
        }
      }

      // Now search any zone with a free spot
      if (!$sameFound) {
        foreach ($zones as &$zone) {
          if ($zone['animals'] == 0 && in_array($type, $zone['constraints'] ?? ANIMALS)) {
            $valid[] = $type;
            break;
          }
        }
      }
    }

    return $valid;
  }
}
