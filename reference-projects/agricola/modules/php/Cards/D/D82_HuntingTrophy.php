<?php
namespace AGR\Cards\D;
use AGR\Helpers\Utils;
use AGR\Core\Globals;
use AGR\Core\Notifications;
use AGR\Core\Stats;
use AGR\Core\Engine;

class D82_HuntingTrophy extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D82_HuntingTrophy';
    $this->name = clienttranslate('Hunting Trophy');
    $this->deck = 'D';
    $this->number = 82;
    $this->isCorbariusOrDulcinaria = true;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Improvements built on __House Redevelopment__ cost you 1 building resource of your choice less. Fences built on __Farm Redevelopment__ cost you a total of 3 <WOOD> less.'
      ),
    ];
    $this->vp = 1;
    $this->costText = clienttranslate('Return or Cook 1 Wild Boar');
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    if ($this->pId != $player->getId()) {
      return false;
    }

    $pig = $player->getExchangeResources()[PIG];
    return $pig > 0 || $ignoreResources;
  }

  public function actBuy($player, $args = [])
  {
    if (!$this->isBuyable($player, false, $args)) {
      throw new \BgaVisibleSystemException('Cannot be bought. Should not happen');
    }

    // Update stat
    Stats::setCardPlayed($player->getId(), $this->getCode(), Globals::getTurn());
    self::DB()->update(
      [
        'player_id' => $player->getId(),
        'card_location' => 'inPlay',
      ],
      $this->id
    );
    $this->location = 'inPlay';
    Notifications::buyCard($this, $player);

    $flow = [
      'type' => NODE_XOR,
      'childs' => [
        $this->payNode([PIG => 1]),
        [
          'action' => SPECIAL_EFFECT,
          'args' => [
            'cardId' => $this->id,
            'method' => 'cookPig',
            'args' => [],
          ]
        ]
      ]
    ];

    Engine::insertAsChild($flow);
  }

  public function getCookPigDescription()
  {
    return [
      'log' => clienttranslate('Cook ${resources_desc}'),
      'args' => [
        'resources_desc' => '1 <PIG>',
      ],
    ];
  }

  public function cookPig()
  {
    $this->setExtraDatas('turnUsed', Globals::getTurn());

    $flow = [
      'action' => EXCHANGE,
      'args' => [
        'trigger' => 'huntingTrophy',
        'exchanges' => $this->getExchanges(),
      ],
    ];

    Engine::insertAsChild($flow);
  }

  public function isCookPigDoable()
  {
    return count($this->getExchanges()) > 0;
  }

  public function getExchanges()
  {
    $player = $this->getPlayer();

    $exchanges = [];
    foreach ($player->getPlayedCards() as $card) {
      if ($card->canCook()) {
        foreach ($card->getExchanges() as $exchange) {
          if (array_keys($exchange['from']) == [PIG]) {
            $e = $exchange;
            $e['triggers'] = ['huntingTrophy'];
            $exchanges[] = $e;
          }
        }
      }
    }

    return $exchanges;
  }

  public function onPlayerComputeCardCosts($player, &$args)
  {
    if (($args['actionCardId'] ?? null) != 'ActionHouseRedevelopment') {
      return;
    }

    Utils::addBonusChoices($args['costs'], [[WOOD => -1], [CLAY => -1], [STONE => -1], [REED => -1]], $this->id);
  }

  public function onPlayerComputeCostsFencing($player, &$args)
  {
    if (!empty($args['costs']['constraints'] ?? [])) {
      return;
    }
    if (($args['actionCardId'] ?? null) == 'ActionFarmRedevelopment') {
      Utils::addCost($args['costs'], [
        'max' => 3,
        'nb' => 1,
        'sources' => [$this->id],
      ]);
    }
  }
}
