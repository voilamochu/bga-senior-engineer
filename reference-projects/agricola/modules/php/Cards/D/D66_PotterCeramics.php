<?php
namespace AGR\Cards\D;
use AGR\Core\Globals;
use AGR\Core\Engine;
use AGR\Helpers\Utils;
use AGR\Helpers\CardRulings;

class D66_PotterCeramics extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D66_PotterCeramics';
    $this->name = clienttranslate('Potter Ceramics');
    $this->deck = 'D';
    $this->number = 66;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      clienttranslate('Each time before you take a __Bake Bread__ action, you can exchange 1 <CLAY> for 1 <GRAIN>.'),
    ];

    $this->rulings = CardRulings::fromKeys([
      'MUST_BAKE_NORMALLY',
    ]);
  }

  public function onPlayerIsDoable($player, &$args)
  {
    if ($args['isDoable'] && $args['action'] != EXCHANGE) {
      return;
    }

    $ctxArgs = $args['ctx'] == null ? null : (is_array($args['ctx']) ? $args['ctx'] : $args['ctx']->getArgs());
    $incomingClay = is_object($args['ctx']) && $args['ctx']->getCardId() == 'ActionGrainUtilization'
      && $player->hasPlayedCard('A122_PanBaker');
    if (($ctxArgs['trigger'] ?? ANYTIME) == BREAD && $player->canBake() && ($incomingClay || $player->canPayCost([CLAY => 1]))) {
      $args['isDoable'] = true;
    }
  }

  public function isListeningTo($event)
  {
    return $this->isBeforeEvent($event, 'Exchange') && ($event['args']['trigger'] ?? ANYTIME) == BREAD;
  }

  public function onPlayerBeforeExchange($player, $event)
  {
    // An action space's bake must actually be taken: when this conversion is the only route
    // to it (no grain), it can't be declined to end the space use empty (bug 232441)
    $forced = Utils::isActionSpaceId($event['cardId'] ?? null) &&
      ($player->getExchangeResources()[GRAIN] ?? 0) == 0 &&
      $player->canBake() && $player->canPayCost([CLAY => 1]) &&
      !($player->hasPlayedCard('B67_HandTruck') && $player->getFarmersOnAccumulationSpaces() > 0);

    return [
      'type' => NODE_SEQ,
      'pId' => $this->pId,
      'optional' => !$forced,
      'childs' => [
        $this->payGainNode([CLAY => 1], [GRAIN => 1], null, false),
        [
          'action' => SPECIAL_EFFECT,
          'args' => [
            'cardId' => $this->id,
            'method' => 'makeBakeMandatory',
          ],
        ],
      ],
    ];
  }

  public function makeBakeMandatory()
  {
    $parNode = Engine::$tree->getNextUnresolved()->getParent()->getParent();
    $parNode->getNextSibling()->enforceMandatory();
    Engine::save();
  }
}
