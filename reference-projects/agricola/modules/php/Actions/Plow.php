<?php
namespace AGR\Actions;
use AGR\Managers\PlayerCards;
use AGR\Managers\Players;
use AGR\Core\Notifications;
use AGR\Core\Engine;
use AGR\Helpers\Utils;

class Plow extends \AGR\Models\Action
{
  public function getState()
  {
    return ST_PLOW;
  }

  public function __construct($row)
  {
    parent::__construct($row);
    $this->description = clienttranslate('Plow a field');
  }

  public function isDoable($player, $ignoreResources = false)
  {
    if ($ignoreResources && $this->isAlreadySatisfied()) {
      return true;
    }
    return $player->board()->canPlow() && ($ignoreResources || $player->canBuy($this->getCosts($player)));
  }

  // A bonus plow (eg Mole Plow) can fill the last field, leaving the mandatory plow impossible.
  // To avoid stuck games after cross-player effects like Lazybones, treat it as satisfied if we already plowed this action.
  public function isAlreadySatisfied()
  {
    foreach (Engine::getResolvedActions([PLOW]) as $node) {
      $resArgs = $node->getActionResolutionArgs() ?? [];
      if (!empty($resArgs['field'])) {
        return true;
      }
    }
    return false;
  }

  function argsPlow()
  {
    $player = Players::getActive();
    $source = $this->getCtxArgs()['source'] ?? null;
    $cardId = $this->getCtxArgs()['cardId'] ?? null;
    $unrestricted = $this->getCtxArgs()['unrestricted'] ?? false;
    return [
      'zones' => $this->plowable($player,$unrestricted),
      'source' => $source,
      'cardId' => $cardId,
      'i18n' => ['source'],
      'descSuffix' => is_null($source) ? '' : 'source',
    ];
  }

  function plowable($player,$unrestricted){
    $fields = $player->board()-> getPlowableZones($unrestricted);
    if(\array_key_exists('location',$this->getCtxArgs())){
      Utils::filter($fields, function ($field) {
        return in_array(['x' => $field['x'],'y' => $field['y']], $this->getCtxArgs()['location']);
      });
    }
    return $fields;
  }

  public function getCosts($player)
  {
    $costs = Utils::formatCost([]);
    $this->checkCostModifiers($costs, $player, []);
    return $costs;
  }

  function actPlow($field)
  {
    self::checkAction('actPlow');
    $player = Players::getActive();
    $source = $this->getCtxArgs()['source'] ?? null;
    $cardId = $this->getCtxArgs()['cardId'] ?? ($this->ctx != null ? $this->ctx->getCardId() : null);
    $unrestricted = $this->getCtxArgs()['unrestricted'] ?? false;

    $costs = $this->getCosts($player);
    // Sanity checks on pos
    if (!in_array($field, $player->board()->getPlowableZones($unrestricted))) {
      throw new \BgaVisibleSystemException('You can\'t plow a field here');
    }

    // Add them to board (update $pos variable to add info about the meeple)
    $player->board()->addField($field);

    // Notify
    Notifications::plow($player, $field, $source);

    // Pay and proceed
    $player->pay(1, $costs, clienttranslate('Plow'));

    if (!is_null($cardId)) {
      $cards = PlayerCards::getMany([$cardId], false);
      if ($cards->count() === 1) {
        $card = $cards->first();
        if ($card->getPId() === $player->getId()) {
          $card->incStats('gain', FIELD, 1);
        }
      }
    }

    $this->checkAfterListeners($player, ['field' => $field, 'cardId' => $cardId]);
    $this->resolveAction(['field' => $field]);
  }
}
