<?php

namespace ARK\Actions;

use ARK\Managers\Meeples;
use ARK\Managers\Players;
use ARK\Core\Notifications;
use ARK\Managers\ZooCards;
use ARK\Core\Engine;
use ARK\Helpers\FlowConvertor;
use ARK\Core\Stats;
use ARK\Helpers\Utils;
use ARK\Core\Globals;
use ARK\Helpers\Collection;
use ARK\Models\Player;

class SponsorsDiscardCardGetBonus extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_SPONSORS_DISCARD_CARD_GET_BONUS;
  }

  public function getSuffix(): string
  {
    return $this->getCtxArg('suffix') ?? ($this->getNumber() . "-" . $this->getLevel());
  }

  public function getDescription(): array|string
  {
    $msgs = [
      '3-1' => clienttranslate('Discard 1 Sponsor card to gain <MONEY:4>'),
      '3-2' => clienttranslate('Discard 1 card to gain <MONEY:4> or to <STRENGTH:+2>'),
      '3-2bis' => clienttranslate('Discard 1 card to gain <MONEY:4>'),
      '4-1' => clienttranslate('Discard 1 Sponsor card to snap 1 Sponsor card'),
      '4-2' => clienttranslate('Discard 1 card to play 1 Sponsor card for money'),
    ];

    return $msgs[$this->getSuffix()];
  }

  public function isDoable(Player $player): bool
  {
    $player = Players::getActive();
    return $this->getDiscardableCards($player)->count() > 0;
  }

  public function isOptional(): bool
  {
    return true;
  }

  public function getDiscardableCards(Player $player): Collection
  {
    return $this->getLevel() == 2 ? $player->getHand() : $player->getHand(CARD_SPONSOR);
  }


  public function argsSponsorsDiscardCardGetBonus(): array
  {
    $player = Players::getActive();
    $cards = $this->getDiscardableCards($player);

    return [
      '_private' => [
        'active' => [
          'cardIds' => $cards->getIds(),
        ]
      ],
      'descSuffix' => $this->getSuffix()
    ];
  }

  public function actSponsorsDiscardCardGetBonus($cId, $rewardType)
  {
    self::checkAction('actSponsorsDiscardCardGetBonus');
    $player = Players::getActive();
    $args = $this->getARgs();

    if (!in_array($cId, $args['_private']['active']['cardIds'])) {
      throw new \BgaVisibleSystemException('This card cannot be discarded');
    }

    $suffix = $this->getSuffix();
    if ($rewardType == MONEY || $suffix == '3-2bis') $suffix = "3-1";

    switch ($suffix) {
      case "3-1":
        $this->insertAsChild([
          'action' => GAIN,
          'args' => [MONEY => 4],
          'source' => clienttranslate('Sponsors3'),
        ]);
        $msg = clienttranslate('${player_name} discards 1 card to gain money (Sponsors3 effect)');
        $pmsg = clienttranslate('You discard ${card_names} to gain money (Sponsors3 effect)');
        break;

      case "3-2":
        $found = false;
        foreach ($this->ctx->getParent()->getChilds() as &$node) {
          if ($node->getAction() == SPONSORS && !$node->isActionResolved()) {
            $found = true;
            $args = $node->getArgs();
            $args['strength'] += 2;
            $node->replace(Engine::buildTree([
              'action' => SPONSORS,
              'args' => $args,
            ]));
            Engine::save();
          }
        }
        $msg = clienttranslate('${player_name} discards 1 card to increase Sponsors action\'s strength by 2 (Sponsors3 effect)');
        $pmsg = clienttranslate('You discard ${card_names} to increase Sponsors action\'s strength by 2 (Sponsors3 effect)');
        break;

      case "4-1":
        $this->insertAsChild([
          'action' => SNAPPING,
          'args' => ['n' => 1, 'constraint' => CARD_SPONSOR]
        ]);
        $msg = clienttranslate('${player_name} discards 1 card to snap a Sponsor card (Sponsors4 effect)');
        $pmsg = clienttranslate('You discard ${card_names} to snap a Sponsor card (Sponsors4 effect)');
        break;

      case "4-2":
        $this->insertAsChild([
          'action' => BUY_SPONSOR,
        ]);
        $msg = clienttranslate('${player_name} discards 1 card to play a Sponsor card for money (Sponsors4 effect)');
        $pmsg = clienttranslate('You discard ${card_names} to play a Sponsor card for money (Sponsors4 effect)');
        break;
    }

    $cardIds = [$cId];
    ZooCards::discard($cardIds);
    Notifications::discardCards($player, ZooCards::getMany($cardIds), $pmsg, $msg);
    Stats::incCardsDiscarded($player, count($cardIds));


    $this->resolveAction([]);
  }
}
