<?php
namespace AGR\Cards\B;

use AGR\Managers\PlayerCards;

class B65_GrainDepot extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B65_GrainDepot';
    $this->name = clienttranslate('Grain Depot');
    $this->deck = 'B';
    $this->number = 65;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      clienttranslate(
        'If you paid <WOOD>/<CLAY>/<STONE> for this card, place 1 <GRAIN> on each of the next 2/3/4 round spaces. At the start of these rounds, you get the <GRAIN>.'
      ),
    ];
    $this->costs = [[WOOD => 2], [CLAY => 2], [STONE => 2]];
    $this->isArtifexOrBubulcus = true;
  }

  // Always resolve as "choose path first", as it's possible to use a path but not actually pay any of that resources (Wood Expert, Overachiever etc)
  public function actBuy($player, $args = [])
  {
    $args['grainDepotSkipDefaultPay'] = true;
    parent::actBuy($player, $args);
  }

  public function getCosts($player, $args = [])
  {
    if (($args['grainDepotSkipDefaultPay'] ?? false) === true) {
      return NO_COST;
    }

    return parent::getCosts($player, $args);
  }

  protected function getPathCosts($player, $path, $eventData = [])
  {
    $args = $eventData;
    unset($args['grainDepotSkipDefaultPay']);

    $args['card'] = $this;
    $args['costs'] = [
      'trades' => [
        [
          'max' => 1,
          $path => 2,
        ],
      ],
    ];

    PlayerCards::applyEffects($player, 'ComputeCardCosts', $args);
    return $args['costs'];
  }

  protected function getPathFlow($player, $path, $rounds, $eventData = [])
  {
    $costs = $this->getPathCosts($player, $path, $eventData);
    if (!$player->canBuy($costs)) {
      return null;
    }

    return [
      'type' => NODE_SEQ,
      'pId' => $player->getId(),
      'customDescription' => $this->getPathChoiceDescription($rounds),
      'childs' => [
        [
          'action' => PAY,
          'args' => [
            'pId' => $player->getId(),
            'nb' => 1,
            'costs' => $costs,
            'source' => $this->name,
          ],
        ],
        $this->futureMeeplesNode([GRAIN => 1], $rounds, null, null, $player->getId()),
      ],
    ];
  }

  protected function getPathChoiceDescription($rounds)
  {
    return [
      'log' => clienttranslate('Place ${n} <GRAIN> on future spaces'),
      'args' => [
        'n' => $rounds,
      ],
    ];
  }

  protected function onBuyWithData($player, $eventData)
  {
    $flows = array_values(array_filter([
      $this->getPathFlow($player, WOOD, 2, $eventData),
      $this->getPathFlow($player, CLAY, 3, $eventData),
      $this->getPathFlow($player, STONE, 4, $eventData),
    ]));

    if (empty($flows)) {
      throw new \BgaVisibleSystemException('No payment path is available for Grain Depot');
    }

    if (count($flows) == 1) {
      return $flows[0];
    }

    return [
      'type' => NODE_XOR,
      'pId' => $player->getId(),
      'childs' => $flows,
    ];
  }
}
