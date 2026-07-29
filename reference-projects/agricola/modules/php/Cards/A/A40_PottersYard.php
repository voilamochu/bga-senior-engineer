<?php

namespace AGR\Cards\A;
use AGR\Core\Globals;
use AGR\Core\Engine;
use AGR\Core\Notifications;
use AGR\Managers\Meeples;
use AGR\Managers\PlayerCards;
use AGR\Models\MinorImprovement;

class A40_PottersYard extends MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A40_PottersYard';
    $this->name = clienttranslate("Potter's Yard");
    $this->deck = 'A';
    $this->number = 40;
    $this->category = GOODS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Immediately place 1 <CLAY> on each unused space in your farmyard. Each time you turn a space into a used space, you get the clay and you can immediately exchange it for 2 <FOOD>.'
      ),
    ];
    $this->cost = [
      WOOD => 1,
      REED => 1,
    ];
    $this->prerequisite = clienttranslate('At Most 7 Unused Farmyard Spaces');
    $this->isArtifexOrBubulcus = true;
  }

  public function isBuyable($player, $ignoreResources = false, $args = []): bool
  {
    $freeFarmSpaces = count($player->board()->getFreeZones());

    if ($freeFarmSpaces > 7) {
      return false;
    }

    return parent::isBuyable($player, $ignoreResources, $args);
  }

  public function onBuy($player)
  {
    $freeFarmSpaces = $player->board()->getFreeZones();

    if (count($freeFarmSpaces) > 0) {
      foreach ($freeFarmSpaces as $freeSpace) {
        $ids = (new Meeples)->createResourceInLocation(CLAY, 'board', $player->getId(), $freeSpace['x'], $freeSpace['y']);

        // Assuming createResourceInLocation places exactly 1 clay in the specified location
        $sows[] = [
          'field' => null,
          'seed' => null,
          'crops' => Meeples::getMany($ids)->toArray(),
        ];
      }

      // Notify just once after all placements
      Notifications::sow($player, $sows, true, true);
    }
  }

  public function isListeningTo($event): bool
  {
    return $this->isActionEvent($event, 'Plow') ||
      $this->isActionEvent($event, 'Construct') ||
      $this->isActionEvent($event, 'Fencing') ||
      $this->isActionEvent($event, 'Stables');
  }

  public function onPlayerAfterPlow($player, $event): array
  {
    $x = $event['field']['x'];
    $y = $event['field']['y'];

    $childs = [
      [
        'action' => SPECIAL_EFFECT,
        'args' => [
          'cardId' => $this->id,
          'method' => 'collectClay',
          'args' => [$player, $x, $y]
        ],
      ],
      $this->payGainNode([CLAY => 1], [FOOD => 2], clienttranslate("Potter's Yard effect")),
    ];

    $wolf = $this->checkWolf($player);
    if ($wolf != []) {
      $childs[] = $wolf;
    }

    return [
      'type' => NODE_SEQ,
      'pId' => $this->getPlayer()->getId(),
      'childs' => $childs
    ];
  }

  public function onPlayerAfterConstruct($player, $event): array
  {
    $childs = [];
    foreach ($event['rooms'] as $room) {
      $childs[] = [
        'action' => SPECIAL_EFFECT,
        'args' => [
          'cardId' => $this->id,
          'method' => 'collectClay',
          'args' => [$player, $room['x'], $room['y']]
        ]
      ];
    }

    $clayFoodChoices = [];
    for ($i = count($event['rooms']); $i > 0; $i--) {
      $clayFoodChoices[] = $this->payGainNode([CLAY => $i], [FOOD => 2 * $i], null, false);
    }

    $childs[] = [
      'type' => NODE_XOR,
      'optional' => true,
      'childs' => $clayFoodChoices,
    ];

    $wolf = $this->checkWolf($player);
    if ($wolf != []) {
      $childs[] = $wolf;
    }

    return [
      'type' => NODE_SEQ,
      'pId' => $this->getPlayer()->getId(),
      'childs' => $childs
    ];
  }

  public function onPlayerAfterFencing($player, $event): array
  {
    $pastures = $event['newPastures']['new'];

    $childs = [];
    $numZones = 0;
    foreach ($pastures as $pasture) {
      foreach ($pasture['nodes'] as $node) {
        if ($player->board()->containsStable($node['x'], $node['y'])) {
          continue;
        }
        $numZones += 1;
        $childs[] = [
          'action' => SPECIAL_EFFECT,
          'args' => [
            'cardId' => $this->id,
            'method' => 'collectClay',
            'args' => [$player, $node['x'], $node['y']]
          ]
        ];
      }
    }

    $clayFoodChoices = [];
    for ($i = $numZones; $i > 0; $i--) {
      $clayFoodChoices[] = $this->payGainNode([CLAY => $i], [FOOD => 2 * $i], null, false);
    }

    $childs[] = [
      'type' => NODE_XOR,
      'optional' => true,
      'childs' => $clayFoodChoices,
    ];

    $wolf = $this->checkWolf($player);
    if ($wolf != []) {
      $childs[] = $wolf;
    }

    return [
      'type' => NODE_SEQ,
      'pId' => $this->getPlayer()->getId(),
      'childs' => $childs
    ];
  }

  public function onPlayerAfterStables($player, $event): array
  {
    $marks = $player->board()->getPasturesMarks();
    $childs = [];
    $numZones = 0;
    foreach ($event['stables'] as $stable) {
      if ($marks[$stable['x']][$stable['y']] == INSIDE) {
        continue;
      }
      $numZones += 1;
      $childs[] = [
        'action' => SPECIAL_EFFECT,
        'args' => [
          'cardId' => $this->id,
          'method' => 'collectClay',
          'args' => [$player, $stable['x'], $stable['y']]
        ]
      ];
    }

    $clayFoodChoices = [];
    for ($i = $numZones; $i > 0; $i--) {
      $clayFoodChoices[] = $this->payGainNode([CLAY => $i], [FOOD => 2 * $i], null, false);
    }

    $childs[] = [
      'type' => NODE_XOR,
      'optional' => true,
      'childs' => $clayFoodChoices,
    ];

    $wolf = $this->checkWolf($player);
    if ($wolf != []) {
      $childs[] = $wolf;
    }

    return [
      'type' => NODE_SEQ,
      'pId' => $this->getPlayer()->getId(),
      'childs' => $childs
    ];
  }

  public function collectClay($player, $x, $y)
  {
    $meeples = (new Meeples)->collectClayFromSpace($player, $x, $y);
    Notifications::collectResources($player, $meeples);

    if ($player->hasInHand('A53_Claypipe') && Globals::isWorkPhase()) {
      $player->updateObtainedResources($meeples);
    }
    if ($player->hasPlayedCard('A53_Claypipe')) {
      $event = [];
      $event['meeples'] = $meeples;
      Engine::insertAsChild(PlayerCards::get('A53_Claypipe')->onPlayerAfterObtain($player, $event));
    }
  }

  public function checkWolf($player) {
    $flow = [];

    if ($player->hasPlayedCard('E103_Wolf')) {
      $wolf = PlayerCards::get('E103_Wolf');
      $meeple = $wolf->getNextResource();

      if (($meeple['type'] ?? null) == CLAY) {
        $flow = [
          'type' => NODE_SEQ,
          'optional' => true,
          'childs' => [
            $wolf->unflagCardNode('E103Obtained'),
            [
              'action' => SPECIAL_EFFECT,
              'args' => [
                'cardId' => $wolf->id,
                'method' => 'gainNextResource',
                'args' => [1],
              ],
            ],
            $wolf->gainNode([PIG => 1]),
          ]
        ];
      }
    }

    return $flow;
  }
}
