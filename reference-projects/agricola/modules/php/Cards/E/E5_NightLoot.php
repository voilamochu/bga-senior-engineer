<?php
namespace AGR\Cards\E;

use AGR\Managers\ActionCards;
use AGR\Managers\Meeples;
use AGR\Core\Engine;

class E5_NightLoot extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E5_NightLoot';
    $this->name = clienttranslate('Night Loot');
    $this->deck = 'E';
    $this->author = 'inoshishi';
    $this->number = 5;
    $this->category = 'PASSING_-_BUILDING_RESOURCES_';
    $this->desc = [
      clienttranslate(
        'Immediately remove 2 different building resources total from accumulation spaces and place them in your supply.'
      ),
    ];
    $this->passing = true;
    $this->cost = [
      FOOD => 2,
    ];
  }

  public function onBuy($player)
  {
    $spaces = $this->getSpaces();
    if ($spaces == []) {
      return;
    }

    return [
      'action' => SPECIAL_EFFECT,
      'args' => [
        'cardId' => $this->id,
        'method' => 'selectResources',
        'args' => [],
      ],
    ];
  }

  public function argsSelectResources()
  {
    $options = [];
    foreach ($this->getSpaces() as $space) {
      $seen = [];
      foreach (Meeples::getResourcesOnCard($space->getId()) as $meeple) {
        $type = $meeple['type'];
        if (in_array($type, $seen)) continue;
        $seen[] = $type;
        $options[] = [
          'spaceId' => $space->getId(),
          'spaceName' => $space->getName(),
          'type' => $type,
        ];
      }
    }

    $availableTypes = array_unique(array_column($options, 'type'));

    return [
      'cardId' => $this->id,
      'options' => $options,
      'nb' => count($availableTypes) >= 2 ? 2 : 1,
      'description' => clienttranslate('${actplayer} must select 2 different building resources (Night Loot)'),
      'descriptionmyturn' => clienttranslate('Select ${nb} different building resources (Night Loot)'),
    ];
  }

  public function actSelectResources($selections)
  {
    if (count($selections) < 1 || count($selections) > 2) {
      throw new \BgaVisibleSystemException('Must select 1 or 2 resources. Should not happen');
    }

    $types = [];
    foreach ($selections as $sel) {
      $types[] = $sel['type'];
    }
    if (count($selections) == 2 && $types[0] == $types[1]) {
      throw new \BgaVisibleSystemException('Must select different building resources. Should not happen');
    }

    $childs = [];
    foreach ($selections as $sel) {
      $meeple = Meeples::getResourcesOnCard($sel['spaceId'], null, $sel['type'])->first();
      if (!$meeple) {
        throw new \BgaVisibleSystemException('Resource no longer available. Should not happen');
      }
      $childs[] = $this->receiveNode($meeple['id'], true);
    }

    Engine::insertAsChild([
      'type' => NODE_SEQ,
      'childs' => $childs,
    ]);
  }

  public function getSpaces()
  {
    $valid = [];

    foreach ([WOOD, CLAY, REED, STONE] as $type) {
      $spaces = ActionCards::getAccumulationSpaces($type);
      foreach ($spaces as $space) {
        if (Meeples::getResourcesOnCard($space->getId())->count() >= 1) {
          $valid[] = $space;
        }
      }
    }

    return $valid;
  }

}
