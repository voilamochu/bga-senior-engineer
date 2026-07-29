<?php
namespace AGR\Cards\A;

class A61_WinnowingFan extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A61_WinnowingFan';
    $this->name = clienttranslate('Winnowing Fan');
    $this->deck = 'A';
    $this->number = 61;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
      'After the field phase of each harvest, you can use a <BAKE>-improvement but only to turn exactly 1 <GRAIN> into <FOOD>. (This is not considered a __Bake Bread__ action.)'
      ),
    ];
    $this->cost = [
      REED => '1',
    ];
    $this->prerequisite = ('Baking Improvement');
    $this->isArtifexOrBubulcus = true;
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    foreach ([MAJOR, MINOR] as $type) {
      foreach ($player->getCards($type, true) as $card) {
        if ($card->canBake()) {
          return parent::isBuyable($player, $ignoreResources, $args);
        }
      }
    }
    return false;
  }

  public function preFeedingGoodsWanted()
  {
    return [GRAIN];
  }

    public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && $event['type'] == 'EndHarvestFieldPhase';
  }

  public function onPlayerEndHarvestFieldPhase($player)
  {

    // Gather all played cards (some bake improvements appear as both MAJOR and MINOR),
    // so dedupe by id to avoid duplicate options.
    $cardsById = [];
    foreach ([MAJOR, MINOR] as $type) {
      foreach ($player->getCards($type, true) as $card) {
        $cardsById[$card->id] = $card;
      }
    }

    $childs = [];

    foreach ($cardsById as $card) {
      // Identify "baking improvements" by their BREAD-trigger exchanges:
      // exactly 1 grain -> food.
      foreach ($card->getExchanges() as $ex) {
        if (empty($ex['triggers']) || !in_array(BREAD, $ex['triggers'])) {
          continue;
        }

        if (!isset($ex['from'][GRAIN]) || $ex['from'][GRAIN] != 1) {
          continue;
        }

        // Must be exactly 1 grain and nothing else.
        if (count($ex['from']) != 1) {
          continue;
        }

        if (!isset($ex['to'][FOOD]) || $ex['to'][FOOD] <= 0) {
          continue;
        }

        $food = $ex['to'][FOOD];

        $childs[] = $this->payGainNode([GRAIN => 1], [FOOD => $food], null, false);
      }
    }

    if (empty($childs)) {
      return null;
    }

    return [
      'type' => NODE_XOR,
      'optional' => true,
      'doableRequiresChild' => true,
      'childs' => $childs,
    ];
  }
}
