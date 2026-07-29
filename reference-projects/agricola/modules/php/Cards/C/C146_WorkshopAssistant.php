<?php
namespace AGR\Cards\C;

use AGR\Helpers\UsedText;
use AGR\Core\Engine;
use AGR\Core\Notifications;
use AGR\Managers\Meeples;
use AGR\Helpers\CardRulings;

class C146_WorkshopAssistant extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C146_WorkshopAssistant';
    $this->name = clienttranslate('Workshop Assistant');
    $this->deck = 'C';
    $this->number = 146;
    $this->category = GOODS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Place unique pairs of different building resources on this card, one for each improvement you have built. Each time another player renovates, you may move one such pair to your supply.'
      ),
    ];
    $this->players = '3+';
    $this->holder = true;
    $this->isCorbariusOrDulcinaria = true;

    $this->rulings = array_merge(
      CardRulings::fromKeys([
      'UPDATED_DIGITAL',
      ]),
      [
      clienttranslate('There are six possible unique pairs of different building resources (<WOOD>+<CLAY>, <WOOD>+<REED>, <WOOD>+<STONE>, <CLAY>+<REED>, <CLAY>+<STONE>, <REED>+<STONE>). So you can place at most six pairs of building resources on this card.'),
      ]
    );
    $this->usedText = UsedText::get('PAIRS_TAKEN');
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Renovation', 'opponent');
  }

  public function onBuy($player)
  {
    $n = min(6, $player->countAllImprovements());
    if ($n <= 0) {
      return;
    }

    if ($n >= 6) {
      $this->createPairsByKeys(['WC','WR','WS','CR','CS','RS']);
      return;
    }

    return [
      'action' => SPECIAL_EFFECT,
      'pId' => $player->getId(),
      'args' => [
        'cardId' => $this->id,
        'method' => 'choosePairs',
        'args' => [$n],
      ],
    ];
  }

  public function getChoosePairsDescription($n)
  {
    return clienttranslate('Choose pairs to place on Workshop Assistant');
  }

  public function argsChoosePairs($n)
  {
    return [
      'cardId' => $this->id,
      'method' => 'choosePairs',
      'description' => clienttranslate('${actplayer} must choose resource pairs (Workshop Assistant)'),
      'descriptionmyturn' => clienttranslate('${you} must choose resource pairs to place (Workshop Assistant)'),
      'n' => $n,
      'pairs' => $this->getAllPairsUi(),
    ];
  }

  public function actChoosePairs($chosenKeys, $n)
  {
    if (!is_array($chosenKeys)) {
      throw new \BgaVisibleSystemException('Invalid choice');
    }

    $chosenKeys = array_values(array_unique($chosenKeys));
    if (count($chosenKeys) != $n) {
      throw new \BgaVisibleSystemException('Wrong number of pairs chosen');
    }

    $all = array_column($this->getAllPairsUi(), null, 'key');
    foreach ($chosenKeys as $k) {
      if (!isset($all[$k])) {
        throw new \BgaVisibleSystemException('Invalid pair chosen');
      }
    }

    $this->createPairsByKeys($chosenKeys);
  }

  protected function getAllPairsUi()
  {
    return [
      ['key' => 'WC', 'a' => WOOD,  'b' => CLAY,  'label' => '<WOOD>+<CLAY>'],
      ['key' => 'WR', 'a' => WOOD,  'b' => REED,  'label' => '<WOOD>+<REED>'],
      ['key' => 'WS', 'a' => WOOD,  'b' => STONE, 'label' => '<WOOD>+<STONE>'],
      ['key' => 'CR', 'a' => CLAY,  'b' => REED,  'label' => '<CLAY>+<REED>'],
      ['key' => 'CS', 'a' => CLAY,  'b' => STONE, 'label' => '<CLAY>+<STONE>'],
      ['key' => 'RS', 'a' => REED,  'b' => STONE, 'label' => '<REED>+<STONE>'],
    ];
  }

  protected function createPairsByKeys($keys)
  {
    $map = array_column($this->getAllPairsUi(), null, 'key');

    $created = [];
    foreach (array_values($keys) as $i => $k) {
      $a = $map[$k]['a'];
      $b = $map[$k]['b'];

      // x = pair index; y = vertical ordering inside the pair
      $created = array_merge(
        $created,
        Meeples::createResourceInLocation($a, $this->id, $this->pId, $i, 0, 1, null)
      );
      $created = array_merge(
        $created,
        Meeples::createResourceInLocation($b, $this->id, $this->pId, $i, 1, 1, null)
      );
    }

    Notifications::accumulate(Meeples::getMany($created), true);
  }

  public function onOpponentAfterRenovation($player, $event)
  {
    $pairs = $this->getPairsOnCard();
    if (empty($pairs)) {
      return;
    }

    $owner = $this->getPlayer();
    $ownerId = $owner->getId();

    $bumpUsedNode = [
      'action' => SPECIAL_EFFECT,
      'args' => [
        'cardId' => $this->id,
        'method' => 'bumpUsed',
      ],
    ];

    // If there is exactly 1 pair, take it directly (no SpecialEffect)
    if (count($pairs) == 1) {
      $only = reset($pairs); // array of 2 meeples

      return [
        'type' => NODE_SEQ,
        'pId' => $ownerId,
        'childs' => [
          [
            'type' => NODE_SEQ,
            'optional' => true,
            'forceConfirmation' => true,
            'pId' => $ownerId,
            'childs' => [
              $this->receiveNode($only[0]['id'], true, $owner),
              $this->receiveNode($only[1]['id'], true, $owner),
              $bumpUsedNode,
            ],
          ],
        ],
      ];
    }

    // Otherwise (2+ pairs), keep the SpecialEffect so they can choose
    return [
      'type' => NODE_SEQ,
      'pId' => $ownerId,
      'childs' => [
        [
          'type' => NODE_SEQ,
          'optional' => true,
          'forceConfirmation' => true,
          'pId' => $ownerId,
          'childs' => [
            [
              'action' => SPECIAL_EFFECT,
              'args' => [
                'cardId' => $this->id,
                'method' => 'takePair',
                'args' => [],
              ],
            ],
            $bumpUsedNode,
          ],
        ],
      ],
    ];
  }

  public function bumpUsed()
  {
    $this->incStats('used');
  }

  public function isIndependentBumpUsed()
  {
    return true;
  }

  public function getTakePairDescription()
  {
    return clienttranslate('Take a pair from Workshop Assistant');
  }

  public function argsTakePair()
  {
    $pairs = $this->getPairsOnCardUi();
    if (empty($pairs)) {
      return [];
    }

    return [
      'cardId' => $this->id,
      'method' => 'takePair',
      'description' => clienttranslate('${actplayer} may take a resource pair (Workshop Assistant)'),
      'descriptionmyturn' => clienttranslate('${you} may take a resource pair (Workshop Assistant)'),
      'pairs' => $pairs,
    ];
  }

  public function actTakePair($key)
  {
    if ($key === 'pass') {
      return;
    }

    $pairs = $this->getPairsOnCard();
    if (!isset($pairs[$key]) || count($pairs[$key]) != 2) {
      throw new \BgaVisibleSystemException('Invalid pair');
    }

    $p = $this->getPlayer();
    $flow = [
      'type' => NODE_SEQ,
      'pId' => $p->getId(),
      'childs' => [
        $this->receiveNode($pairs[$key][0]['id'], true, $p),
        $this->receiveNode($pairs[$key][1]['id'], true, $p),
      ],
    ];
    Engine::insertAsChild($flow);
  }

  protected function getPairsOnCard()
  {
    $meeples = Meeples::getInLocation($this->id);
    if (empty($meeples)) {
      return [];
    }

    $groups = [];
    foreach ($meeples as $m) {
      // x identifies the pair/column
      $x = isset($m['x']) ? intval($m['x']) : 0;
      $k = 'p' . $x;
      $groups[$k][] = $m;
    }

    // Only keep complete pairs, and sort within each pair by y
    foreach ($groups as $k => $g) {
      if (count($g) != 2) {
        unset($groups[$k]);
        continue;
      }
      usort($groups[$k], function ($a, $b) {
        $ya = isset($a['y']) ? intval($a['y']) : 0;
        $yb = isset($b['y']) ? intval($b['y']) : 0;
        return $ya <=> $yb;
      });
    }

    ksort($groups);
    return $groups;
  }

  protected function getPairsOnCardUi()
  {
    $groups = $this->getPairsOnCard();
    $out = [];

    foreach ($groups as $k => $g) {
      // label using meeple types; you can prettify if you have helpers
      $a = $g[0]['type'];
      $b = $g[1]['type'];
      $out[] = [
        'key' => $k,
        'label' => $this->formatPairLabel($a, $b),
      ];
    }

    return $out;
  }

  protected function formatPairLabel($a, $b)
  {
    $nameA = $this->resourceTypeToToken($a);
    $nameB = $this->resourceTypeToToken($b);

    return "<{$nameA}>+<{$nameB}>";
  }

  protected function resourceTypeToToken($t)
  {
    switch ($t) {
      case WOOD:  return 'WOOD';
      case CLAY:  return 'CLAY';
      case REED:  return 'REED';
      case STONE: return 'STONE';
      default:    return 'UNKNOWN';
    }
  }

}
