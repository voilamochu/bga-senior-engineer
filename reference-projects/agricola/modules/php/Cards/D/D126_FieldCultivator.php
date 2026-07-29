<?php
namespace AGR\Cards\D;

use AGR\Core\Engine;
use AGR\Managers\Meeples;
use AGR\Core\Notifications;

class D126_FieldCultivator extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D126_FieldCultivator';
    $this->name = clienttranslate('Field Cultivator');
    $this->deck = 'D';
    $this->number = 126;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Pile 1 <WOOD>, 1 <CLAY>, 1 <REED>, 1 <STONE>, 1 <REED>, 1 <CLAY>, and 1 <WOOD> on this card. Each time you harvest a field tile, you can also take the top good from the pile.'
      ),
    ];
    $this->players = '1+';
    $this->isCorbariusOrDulcinaria = true;
    $this->holder = true;
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Reap') && $this->countFields($event) > 0;
  }

  public function onBuy($player)
  {
    $types = [WOOD, CLAY, REED, STONE, REED, CLAY, WOOD];

    $created = [];
    foreach ($types as $i => $type) {
      $created = array_merge(
        $created,
        Meeples::createResourceInLocation($type, $this->id, $player->getId(), null, null, 1, $i)
      );
    }

    Notifications::accumulate(Meeples::getMany($created), true);
  }

  public function countFields($event)
  {
    $fields = $this->getPlayer()->board()->getHarvestedFieldTilePositions($event['crops'] ?? []);
    return count($fields);
  }

  public function onPlayerAfterReap($player, $event)
  {
    $n = $this->countFields($event);
    
    return [
      'action' => SPECIAL_EFFECT,
      'args' => [
        'cardId' => $this->id,
        'method' => 'getGoods',
        'args' => [$n],
      ]
    ];
  }

  public function getGoods($n)
  {
    $player = $this->getPlayer();
    $goods = array_reverse(Meeples::getResourcesOnCard($this->id)->toArray());

    $children = [];
    $i = 0;
    foreach ($goods as $good) {
      if ($i == $n) {
        break;
      }
      $children[] = $this->receiveNode($good['id'], false, $player);
      $i++;
    }

    if (!empty($children)) {
      Engine::insertAsChild([
        'type' => NODE_SEQ,
        'childs' => $children,
      ]);
    }
  }
}
