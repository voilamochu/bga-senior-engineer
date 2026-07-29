<?php

namespace ARK\Actions\Effects;

use ARK\Managers\Players;
use ARK\Managers\ZooCards;
use ARK\Core\Notifications;
use ARK\Core\Globals;
use ARK\Helpers\Utils;
use ARK\Helpers\Collection;
use ARK\Models\Player;

class Map10Effect extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_MAP10;
  }

  public function getDescription(): string|array
  {
    return clienttranslate('Special Digging 1');
  }

  public function isOptional(): bool
  {
    return true;
  }

  public function argsMap10Effect()
  {
    $player = Players::getActive();
    $canUseEffect = !is_null($player->getNextRescueSlot());

    return [
      'n' => $this->getN(),
      '_private' => [
        'active' => [
          'cards' => $player
            ->getHand()
            ->merge(ZooCards::getPool())
            ->map(function ($card) use ($canUseEffect) {
              return $canUseEffect && $card->getType() == CARD_ANIMAL && !in_array(PET, $card->getCategories());
            }),
        ],
      ],
    ];
  }

  public function actMap10($cardId, $useEffect)
  {
    // Sanity checks
    $this->checkAction('actMap10');
    $cards = $this->argsMap10Effect()['_private']['active']['cards'];
    if (!$cards->has($cardId)) {
      throw new \BgaVisibleSystemException('Invalid card to discard. Should not happen');
    }
    if ($useEffect && !$cards[$cardId]) {
      throw new \BgaVisibleSystemException('You cant use map10 effect with this card. Should not happen');
    }
    $this->checkCanTakeIrreversible();

    $player = Players::getActive();
    $oCard = ZooCards::getSingle($cardId);
    $fromLocation = $oCard->getLocation();

    if ($useEffect) {
      // MARKED ?
      if ($oCard->isMarked()) {
        $this->pushParallelChild($oCard->removeMarkForMoney($player->getId()));
      }

      $oCard->setPId($player->getId());
      $oCard->setState($player->getNextRescueSlot());
      $oCard->setLocation('rescueStation');

      // Emulate a "play animal" event
      $eventData = [
        'animal' => $cardId,
      ];
      $this->checkListeners('Animals', $player, $eventData);
      $this->checkIconsListeners($oCard->getIcons(), $player);
    } else {
      list($discarded, $assigned, $meeples) = ZooCards::discard($cardId);
    }

    // From hand
    if ($fromLocation == 'hand') {
      $newCards = ZooCards::draw($player, 1);
      if ($useEffect) {
        Notifications::diggingMap10($player, 'hand', $oCard, $newCards);
      } else {
        Notifications::digging($player, 'hand', ZooCards::getMany($cardId), $newCards);
      }
    }
    // From display
    else {
      if ($useEffect) {
        Notifications::diggingMap10($player, 'display', $oCard, null);
      } else {
        Notifications::markAssign($assigned, $meeples);
        Notifications::digging($player, 'display', $discarded, null);
      }
      ZooCards::fillPool();
    }

    $this->resolveAction([], true);
  }
}
