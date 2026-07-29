<?php

namespace ARK\Actions\Effects;

use ARK\Managers\Players;
use ARK\Managers\ZooCards;
use ARK\Core\Notifications;
use ARK\Core\Globals;
use ARK\Core\Engine;
use ARK\Helpers\Utils;
use ARK\Helpers\FlowConvertor;
use ARK\Actions\Build;
use ARK\Models\Player;

class Glide extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_GLIDE;
  }

  public function getDescription(): array
  {
    return [
      'log' => clienttranslate('Glide ${n}'),
      'args' => [
        'n' => $this->getN(),
      ]
    ];
  }

  public function isOptional(): bool
  {
    return true;
  }

  public function isDoable(Player $player): bool
  {
    return true;
  }

  public function argsGlide()
  {
    $player = Players::getActive();

    return [
      'n' => $this->getN(),
      '_private' => [
        'active' => ['cardIds' => $player->getHand()->getIds()]
      ]
    ];
  }

  public function actGlide($cardIds)
  {
    self::checkAction('actGlide');

    $player = Players::getActive();
    $args = $this->argsGlide();
    if (count($cardIds) > $args['n']) {
      throw new \BgaVisibleSystemException('Too much discarded cards. Should not happen');
    }

    $seaAnimalIcons = 0;
    $cards = ZooCards::getMany($cardIds);
    foreach ($cards as $cardId => $card) {
      if (!in_array($cardId, $args['_private']['active']['cardIds'])) {
        throw new \BgaVisibleSystemException('Invalid card to discard');
      }

      if (in_array($card->getType(), [CARD_ANIMAL, CARD_SPONSOR])) {
        foreach ($card->getCategories() as $cat) {
          if ($cat == SEA_ANIMAL) {
            $seaAnimalIcons++;
          }
        }
      }
    }

    ZooCards::discard($cardIds);
    Notifications::discardCards(
      $player,
      $cards,
      clienttranslate('You discard ${card_names} for a total of ${m} <SEAANIMAL> (Glide effect)'),
      clienttranslate('${player_name} discards ${n} card(s) for a total of ${m} <SEAANIMAL> (Glide effect)'),
      [
        'cards' => $cards->toArray(),
        'n' => $cards->count(),
        'm' => $seaAnimalIcons,
      ]
    );

    for ($i = 1; $i <= $seaAnimalIcons; $i++) {
      $node = [
        'type' => NODE_XOR,
        'stateDescription' => [
          'description' => clienttranslate('Glide ${i}/${m}: ${actplayer} must choose exactly one effect'),
          'descriptionmyturn' => clienttranslate('Glide ${i}/${m}: ${you} must choose exactly one effect'),
          'args' => ['i' => $i, 'm' => $seaAnimalIcons]
        ],
        'childs' => [
          ['action' => GAIN, 'args' => [REPUTATION => 1], 'source' => clienttranslate('Glide effect')],
          ['action' => GAIN, 'args' => [APPEAL => 2], 'source' => clienttranslate('Glide effect')],
          ['action' => BUILD, 'args' => ['free' => true, 'freeBuilding' => KIOSK], 'source' => clienttranslate('Glide effect')]
        ]
      ];
      $this->insertAsChild($node);
    }

    $this->resolveAction([]);
  }
}
