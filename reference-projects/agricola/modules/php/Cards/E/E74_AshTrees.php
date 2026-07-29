<?php
namespace AGR\Cards\E;

use AGR\Core\Engine;
use AGR\Managers\Fences;

class E74_AshTrees extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E74_AshTrees';
    $this->name = clienttranslate('Ash Trees');
    $this->deck = 'E';
    $this->author = 'beso';
    $this->number = 74;
    $this->category = 'BUILDING_RESOURCES_-_WOOD';
    $this->desc = [
      clienttranslate(
        'When you play this card, immediately place (up to) 5 fences from your supply on it. When you build fences, fences taken from this card cost you nothing.'
      ),
    ];
    $this->prerequisite = clienttranslate('2 Planted Fields');
    $this->holder = true;
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    $plantedFields = $player->board()->countPlantedLogicalFields();
    if ($plantedFields < 2) {
      return false;
    }

    return parent::isBuyable($player, $ignoreResources, $args);
  }

  public function onBuy($player)
  {
    $count = Fences::countAvailable($player->getId()) > 5 ? 5 : Fences::countAvailable($player->getId());
    return
      [
        'action' => SPECIAL_EFFECT,
        'args' => [
          'cardId' => $this->id,
          'method' => 'addFenceFromSupply',
          'args' => [$count],
        ],
      ];
  }

  public function addFenceFromSupply($count)
  {
    $flow = $this->moveResourcesToSpaceNode([FENCE => $count], $this->id);
    Engine::insertAsChild($flow);
  }

  public function isListeningTo($event)
  {
    return $this->isBeforeEvent($event, 'Fencing');
  }

  public function onPlayerBeforeFencing($player, $event)
  {
    $count = Fences::countAvailable($player->getId(), $this->id);
    // todo ignore cases minipasture
    if ($count > 0) {
      return
        [
          'action' => SPECIAL_EFFECT,
          'optional' => true,
          'args' => [
            'cardId' => $this->id,
            'method' => 'chooseFreeFenceCount',
            'args' => [$count]
          ],
        ];
    }
  }

  public function getChooseFreeFenceCountDescription()
  {
    return clienttranslate('Choose how many free fences you want to use from (Ash Trees)');
  }

  public function argsChooseFreeFenceCount($max)
  {
    return [
      'cardId' => $this->id,
      'max' => $max,
      'description' => clienttranslate(
        '${actplayer} must choose how many fences you want to use from (Ash Trees)'
      ),
      'descriptionmyturn' => clienttranslate(
        'Choose how many fences you want to use from (Ash Trees)'
      ),
    ];
  }

  public function actChooseFreeFenceCount($count)
  {
    $this->setExtraDatas('E74Used', $count);
  }
}
