<?php
namespace AGR\Cards\C;
use AGR\Core\Globals;
use AGR\Core\Engine;
use AGR\Managers\Meeples;
use AGR\Managers\PlayerCards;
use AGR\Core\Notifications;
use AGR\Helpers\CardRulings;

class C75_Firewood extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C75_Firewood';
    $this->name = clienttranslate('Firewood');
    $this->deck = 'C';
    $this->number = 75;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'In the returning home phase of each round, place 1 <WOOD> on this card. Each time after you build a Fireplace, Cooking Hearth, or oven, move up to 4 <WOOD> from this card to your supply.'
      ),
    ];
    $this->cost = [
      FOOD => 2,
    ];
    $this->isCorbariusOrDulcinaria = true;
    $this->holder = true;
    $this->rulings = array_merge(
      CardRulings::fromKeys([
      'UPDATED_DIGITAL',
      ]),
      [
      clienttranslate('An oven is defined as an improvement whose name ends with "Oven", which includes __Simple Oven__ (E064).'),
      clienttranslate('__Oriental Fireplace__ (A060) counts as a Fireplace.'),
      ]
    );
  }

  public function isListeningTo($event)
  {
    return ($this->isPlayerEvent($event) && $event['type'] == 'ReturnHome') ||
      ($this->isActionEvent($event, 'Improvement') && Meeples::getResourcesOnCard($this->id, null, WOOD)->count() > 0);
  }

  public function onPlayerAfterImprovement($player, $event)
  {
    $card = PlayerCards::get($event['cardId']);
    $triggers = [
      'Major_Fireplace1',
      'Major_Fireplace2',
      'A60_OrientalFireplace',
      'Major_CookingHearth1',
      'Major_CookingHearth2',
      'Major_ClayOven',
      'Major_StoneOven',
      'E63_IronOven',
      'E64_SimpleOven',
      'D59_EarthOven'
    ];

    if (in_array($card->getId(), $triggers)) {
      $n = Meeples::getResourcesOnCard($this->id, null, WOOD)->count();
      if ($n > 4) {
        $n = 4;
      }

      $childs = [];
      for ($i = 1; $i <= $n; $i++) {
        $childs[] = [
          'action' => SPECIAL_EFFECT,
          'args' => [
            'cardId' => $this->id,
            'method' => 'receiveWood',
            'args' => [$i]
          ]
        ];
      }

      $wolf = $this->checkWolf($player);
      if ($wolf != []) {
        return [
          'type' => NODE_SEQ,
          'optional' => true,
          'countAsUse' => true,
          'childs' => [
            [
              'type' => NODE_XOR,
              'childs' => $childs,
            ],
            $wolf,
          ]
        ];
      }

      return [
        'type' => NODE_XOR,
        'optional' => true,
        'countAsUse' => true,
        'childs' => $childs,
      ];
    }
  }

  public function onPlayerReturnHome($player, $event)
  {
    return [
      'action' => SPECIAL_EFFECT,
      'args' => [
        'cardId' => $this->id,
        'method' => 'addWood',
      ],
    ];
  }

  public function getReceiveWoodDescription($n)
  {
    return [
      'log' => clienttranslate('Receive ${resources_desc} from ${card}'),
      'args' => [
        'resources_desc' => strval($n) . ' <WOOD>',
        'card' => $this->name,
      ],
    ];
  }

  public function receiveWood($n)
  {
    $player = $this->getPlayer();
    $meeples = [];

    for ($i = 0; $i < $n; $i++) {
      $wood = $this->getNextResource();
      $meeples[] = Meeples::receiveResource($player, $wood);
    }

    Notifications::receiveResources($player, $meeples);

    if ($player->hasInHand('A53_Claypipe') && Globals::isWorkPhase()) {
      $player->updateObtainedResources($meeples);
    }
    if ($player->hasPlayedCard('A53_Claypipe')) {
      $event = [];
      $event['meeples'] = $meeples;
      Engine::insertAsChild(PlayerCards::get('A53_Claypipe')->onPlayerAfterObtain($player, $event));
    }    
  }

  public function getAddWoodDescription()
  {
    return [
      'log' => clienttranslate('Add ${resources_desc} to ${card}'),
      'args' => [
        'resources_desc' => '1 <WOOD>',
        'card' => $this->name,
      ],
    ];
  }

  public function addWood()
  {
    $player = $player = $this->getPlayer();  
      
    $created = Meeples::createResourceInLocation(WOOD, $this->id, $player->getId(), null, null, 1);
    Notifications::accumulate(Meeples::getMany($created), true);      
  }

  public function checkWolf($player) {
    $flow = [];

    if ($player->hasPlayedCard('E103_Wolf')) {
      $wolf = PlayerCards::get('E103_Wolf');
      $meeple = $wolf->getNextResource();

      if (($meeple['type'] ?? null) == WOOD) {
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
