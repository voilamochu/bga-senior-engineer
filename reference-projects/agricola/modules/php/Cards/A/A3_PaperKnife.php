<?php
namespace AGR\Cards\A;
use AGR\Managers\Players;
use AGR\Managers\PlayerCards;
use AGR\Core\Engine;

class A3_PaperKnife extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A3_PaperKnife';
    $this->name = clienttranslate('Paper Knife');
    $this->deck = 'A';
    $this->number = 3;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'Select 3 occupations in your hand. Select one of them randomly, which you can play immediately without paying an occupation cost.'
      ),
    ];
    $this->passing = true;
    $this->cost = [
      WOOD => 1,
    ];
    $this->isArtifexOrBubulcus = true;

    $this->rulings = [
      clienttranslate('You cannot play this card if you have 2 or fewer occupations in your hand.'),
    ];
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    $hand = $player->getHand(OCCUPATION)->toArray();

    if (count($hand) < 3) {
      return false;
    }

    return parent::isBuyable($player, $ignoreResources, $args);
  }  

  public function onBuy($player)
  {
    $player = Players::getActive();
    $hand = $player->getHand(OCCUPATION)->toArray();

    if (count($hand) < 3) {
      return;
    }

    return [
      'action' => SPECIAL_EFFECT,
      'args' => [
        'cardId' => $this->id,
        'method' => 'selectOccs',
        'args' => [],
      ],
    ];
  }

  public function getCards()
  {
    $player = Players::getActive();
    $occs = $player->getHand(OCCUPATION)->toArray();
    $cardIds = [];

    foreach ($occs as $occ) {
      $cardIds[] = $occ->getId();
    }

    return $cardIds;
  }

  public function getSelectOccsDescription()
  {
    return clienttranslate('Select 3 occupations in your hand');
  }

  public function argsSelectOccs()
  {
    return [
      'cardId' => $this->id,
      'description' => clienttranslate('${actplayer} must choose 3 occupations in their hand (Paper Knife)'),
      'descriptionmyturn' => clienttranslate('Choose 3 occupations (Paper Knife)'),
      'cards' => $this->getCards(),
    ];
  }

  public function isSelectOccsIrreversible()
  {
    return true;
  }

  public function actSelectOccs($cards)
  {
    $occ = $cards[rand(0, 2)];

    $flow = [
      'type' => NODE_SEQ,
      'optional' => true,
      'childs' => [
        [
          'action' => SPECIAL_EFFECT,
          'args' => [
            'cardId' => $this->id,
            'method' => 'playOcc',
            'args' => [$occ]
          ]
        ]
      ]
    ];
    Engine::insertAsChild($flow);
  }

  public function getPlayOccDescription($cardId)
  {
    return [
      'log' => clienttranslate('Play ${card_name} for free'),
      'args' => [
        'card_name' => PlayerCards::get($cardId)->getName(),
      ],
    ];
  }

  public function playOcc($cardId)
  {
    $pId = Players::getActiveId();

    $flow = [
      'action' => OCCUPATION,
      'pId' => $pId,
      'args' => [
        'allowedCards' => [$cardId],
        'cost' => [],
      ],
    ];

    Engine::insertAsChild($flow);
  }
}
