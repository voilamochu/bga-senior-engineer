<?php
namespace AGR\Cards\D;
use AGR\Core\Notifications;
use AGR\Managers\Meeples;
use AGR\Helpers\Utils;

class D114_SeedTrader extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D114_SeedTrader';
    $this->name = clienttranslate('Seed Trader');
    $this->deck = 'D';
    $this->number = 114;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Place 2 <GRAIN> and 2 <VEGETABLE> on this card. You can buy them at any time. Each <GRAIN> costs 2 <FOOD>; each <VEGETABLE> costs 3 <FOOD>.'
      ),
    ];
    $this->players = '1+';
    $this->holder = true;
  }

  public function onBuy($player)
  {
    $types = [GRAIN, GRAIN, VEGETABLE, VEGETABLE];

    $created = [];
    foreach ($types as $i => $type) {
      $created = array_merge(
        $created,
        Meeples::createResourceInLocation($type, $this->id, $player->getId(), null, null, 1, $i)
      );
    }

    Notifications::accumulate(Meeples::getMany($created), true);
  }

  public function isListeningTo($event)
  {
    return $this->isAnytime($event) && !Meeples::getResourcesOnCard($this->id)->empty();
  }

  public function getNextGrain()
  {
    $meeples = Meeples::getResourcesOnCard($this->id)->filter(function ($meeple) {
      return $meeple['type'] == GRAIN;
    });
    return $meeples->empty() ? null : $meeples->last();
  }

  public function getNextVegetable()
  {
    $meeples = Meeples::getResourcesOnCard($this->id)->filter(function ($meeple) {
      return $meeple['type'] == VEGETABLE;
    });
    return $meeples->empty() ? null : $meeples->last();
  }

  public function onPlayerAtAnytime($player, $event)
  {
    $flows = [];
    $grain = $this->getNextGrain();
    $veg = $this->getNextVegetable();
    if ($grain != null) {
      $flows[] = [
        'type' => NODE_SEQ,
        'childs' => [
          [
            'action' => PAY,
            'args' => [
              'nb' => 1,
              'costs' => Utils::formatCost([FOOD => 2]),
              'source' => $this->name,
            ],
          ],
          $this->receiveNode($grain['id'],true),
        ],
      ];
    }
    if ($veg != null) {
      $flows[] = [
        'type' => NODE_SEQ,
        'childs' => [
          [
            'action' => PAY,
            'args' => [
              'nb' => 1,
              'costs' => Utils::formatCost([FOOD => 3]),
              'source' => $this->name,
            ],
          ],
          $this->receiveNode($veg['id'],true),
        ],
      ];
    }

    return [
      'type' => NODE_XOR,
      'childs' => $flows,
    ];
  }

  public function getGainNextGrainDescription()
  {
    return [
      'log' => clienttranslate('Gain ${resources_desc}'),
      'args' => [
        'resources_desc' => '1 <GRAIN>',
      ],
    ];
  }

  public function gainNextGrain()
  {
    $player = $this->getPlayer();
    $meeple = $this->getNextGrain();
    Meeples::receiveResource($player, $meeple);
    Notifications::receiveResource($player, $meeple);
  }

  public function getGainNextVegetableDescription()
  {
    return [
      'log' => clienttranslate('Gain ${resources_desc}'),
      'args' => [
        'resources_desc' => '1 <VEGETABLE>',
      ],
    ];
  }

  public function gainNextVegetable()
  {
    $player = $this->getPlayer();
    $meeple = $this->getNextVegetable();
    Meeples::receiveResource($player, $meeple);
    Notifications::receiveResource($player, $meeple);
  }
}
