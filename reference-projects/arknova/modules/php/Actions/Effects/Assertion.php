<?php

namespace ARK\Actions\Effects;

use ARK\Managers\Players;
use ARK\Managers\ZooCards;
use ARK\Core\Notifications;
use ARK\Core\Globals;
use ARK\Models\Player;

class Assertion extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_ASSERTION;
  }

  public function getDescription(): string|array
  {
    return clienttranslate('Take 1 unused base project');
  }

  public function isIrreversible(?Player $player = null): bool
  {
    $player = $player ?? Players::getActive();
    $pIds = Globals::getEffectAssertion();
    return !in_array($player->getId(), $pIds) && count($pIds) > 0;
  }

  public function stPreAssertion()
  {
    // JUST FOR THE CHECKPOINT IF NEEDED
  }


  public function argsAssertion()
  {
    $player = Players::getActive();

    return [
      '_private' => [
        'active' => [
          'cardIds' => ZooCards::getInLocation('projectDeck')
            ->merge(ZooCards::getInlocation('previousBase'))
            ->getIds(),
        ],
      ],
    ];
  }

  public function actAssertion($cardId)
  {
    $this->checkAction('actAssertion');
    $card = ZooCards::getSingle($cardId);
    if (!in_array($card->getLocation(), ['projectDeck', 'previousBase'])) {
      throw new \BgaVisibleSystemException('Invalid card to keep. Should not happen');
    }

    $player = Players::getActive();
    $card->setLocation('hand');
    $card->setPId($player->getId());
    Notifications::assertion($player, ZooCards::getSingle($cardId));

    // Log it to make it irreversible for others
    $pIds = Globals::getEffectAssertion();
    if (!in_array($player->getId(), $pIds)) {
      $pIds[] = $player->getId();
    }
    Globals::setEffectAssertion($pIds);

    $this->resolveAction([]);
  }
}
