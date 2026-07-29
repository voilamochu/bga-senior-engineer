<?php

namespace ARK\Actions;

use ARK\Managers\Meeples;
use ARK\Managers\Players;
use ARK\Core\Notifications;
use ARK\Managers\ActionCards;
use ARK\Core\Engine;
use ARK\Core\Globals;
use ARK\Core\Stats;
use ARK\Helpers\Utils;
use ARK\Models\Player;
use ARK\Managers\ZooCards;

class ChooseActionCard extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_CHOOSE_ACTION_CARD;
  }

  public function getDescription(): string|array
  {
    return $this->isHypnosis()
      ? clienttranslate('Choose an opponent action card (Hypnosis)')
      : clienttranslate('Choose an action card');
  }

  public function isOptional(): bool
  {
    if ($this->isMultiplier()) {
      return true;
    }

    // Edge case: a player might be unable to use any of the strength<=3 action cards of the hypnotized player
    if ($this->isHypnosis()) {
      $args = $this->argsChooseActionCard();
      if ($args['cards']->empty() && !$args['canGainXToken']) {
        return true;
      }
    }
    return false;
  }

  public function isHypnosis()
  {
    return $this->getCtxArg('hypnosis') ?? false;
  }

  public function isMultiplier()
  {
    return $this->getCtxArg('multiplier') ?? false;
  }

  public function argsChooseActionCard()
  {
    $player = Players::getActive();
    $isHypnosis = $this->isHypnosis();
    $forcedCardId = $this->getCtxArg('cardId');
    $forcedStrength = $this->getCtxArg('strength');
    $canGainXToken = $this->getCtxArg('canGainXToken') ?? true;

    // What action cards are we talking about
    if ($isHypnosis) {
      $cards = ActionCards::getMany($this->getCtxArg('hypnosisCards'));
    } elseif (!is_null($forcedCardId)) {
      // This case is used for Multiplier and animal effect Action
      $cards = ActionCards::getMany([$forcedCardId]);
    } else {
      $cards = $player->getActionCards();
    }

    $data = [
      'cards' => $cards
        ->map(function ($card) use ($player, $forcedStrength, $canGainXToken) {
          // Forced strength = 0 => must take a xtoken again
          if ($forcedStrength === 0) {
            return [0];
          }

          // Hypnosis => pretend it's the hypnotised card for args computation
          if ($this->isHypnosis()) {
            Globals::setEffectHypnosis($card->getId());
          }
          // Otherwise, cannot take xtoken if a forcedStrength is given
          $strengths = $card->getPlayableStrengths($player);
          if ($player->countXTokens() < 5 && is_null($forcedStrength) && $canGainXToken) {
            $strengths[0] = 0; // Force 0 = gain X Token
          }

          // Hypnosis => reset the global to 0
          if ($this->isHypnosis()) {
            Globals::setEffectHypnosis(0);
          }

          return $strengths;
        })
        ->filter(function ($card) {
          return !empty($card);
        }),
      'strengths' => $cards->map(function ($card) {
        return $card->getCurrentStrength();
      }),
      'xtokens' => $player->countXTokens(),
    ];

    // MAP T1
    if ($player->canUseMap("T1") && $player->getHand()->count() > 0) {
      $data['canUseT1'] = true;
      $data['_private']['active']['cardIds'] = $player->getHand()->getIds();
    }

    if (!is_null($forcedCardId)) {
      $card = ActionCards::getSingle($forcedCardId);
      $data['descSuffix'] = 'action';
      $data['type'] = $card->getType();
      $data['i18n'][] = 'type';
    } elseif ($isHypnosis) {
      $data['descSuffix'] = 'hypnosis';
      $data['pId'] = $this->getCtxArg('hypnosisPId');
    }

    $data['xtoken'] = $player->countXTokens();
    $data['canGainXToken'] = $canGainXToken;
    return $data;
  }

  public function actT1Effect($cardId)
  {
    self::checkAction('actT1Effect');
    $args = $this->getArgs();
    if (!($args['canUseT1'] ?? false)) {
      throw new \BgaVisibleSystemException('You cannot play that card at that strength. Should not happen');
    }
    if (!in_array($cardId, $args['_private']['active']['cardIds'])) {
      throw new \BgaVisibleSystemException('Invalid card to discard. Should not happen');
    }

    $player = Players::getActive();
    Globals::setActiveT1Effect(true);
    Globals::setMapT1Used(true);
    ZooCards::discard($cardId);
    Notifications::discardCards($player, ZooCards::getMany($cardId), clienttranslate('You discard ${card_names} for Map T1 effect'), clienttranslate('${player_name} discards 1 card for Map T1 effect'));

    $this->duplicateAction();
    $this->resolveAction(['mapT1' => $cardId]);
  }

  public function actChooseActionCard($cardId, $strength)
  {
    self::checkAction('actChooseActionCard');
    $player = Players::getActive();
    $args = $this->getArgs();
    $isHypnosis = $this->isHypnosis();

    if (!isset($args['cards'][$cardId])) {
      throw new \BgaVisibleSystemException('Card action not doable. Should not happen');
    }
    if (!array_key_exists($strength, $args['cards'][$cardId])) {
      throw new \BgaVisibleSystemException('You cannot play that card at that strength. Should not happen');
    }

    // Activate the card
    $card = ActionCards::get($cardId);
    $card->setStatus(1);
    if ($isHypnosis) {
      Globals::setEffectHypnosis($cardId);
    }
    Globals::setVenomTriggered(true);
    Globals::setActiveT1Effect(false);

    // Cleanup "once per action"
    Globals::setLandscapeGardener(false);
    Globals::setSponsors2Gained(false);

    // if Strength = 0, gain XToken
    if ($strength == 0) {
      $meeples = [];
      if ($player->countXTokens() >= 5) {
        throw new \BgaVisibleSystemException('You cannot earn more Xtoken. Should not happen');
      }
      $player->incXToken(1);
      Stats::incXTokenGainedInsteadOfAction($player, 1);
    }
    // Otherwise, pay tokens if Needed
    else {
      $tokens = $args['cards'][$cardId][$strength];
      if ($tokens > 0) {
        $player->payXToken($tokens, true, clienttranslate('increasing card strength'));
      }

      // Notify
      Notifications::chooseCard($player, $card, $strength, $tokens);

      // Do action
      $flow = $card->getTaggedFlow($player, $strength);
      $this->insertAsChild($flow);
      // After finishing flow (for MW)
      $afterFlow = $card->getAfterFinishingTaggedFlow($player, $strength);
      if (!empty($afterFlow)) {
        $this->pushAfterFinishingChilds([$afterFlow]);
      }

      $methodName = 'incAction' . $card->getName();
      Stats::$methodName($player);
      Globals::setActiveActionCard([
        'type' => $card->getType(),
        'pId' => $card->getPId(),
        'lvl' => $card->getLevel(),
      ]);
    }

    // VENOM : if the card has a Venom token, tag it as used (for cleanup purpose)
    if (!$isHypnosis && count($card->getMeeplesOnIt(VENOM)) > 0) {
      Globals::setUsedVenom(true);
    }

    // USE MULTIPLIER
    if ($this->isMultiplier()) {
      $meepleId = $this->getCtxArg('meepleId');
      $meeple = Meeples::destroy($meepleId);
      Notifications::useMultiplier($player, $meeple);
      $this->resolveAction(['card' => $cardId, 'strength' => $strength]);
      return;
    }

    // DUPLICATE ACTION IF THERE ARE MULTIPLIERS
    $multipliers = $card->getMeeplesOnIt(MULTIPLIER, ACTIVE);
    if (!$isHypnosis && $multipliers->count() > 0) {
      foreach ($multipliers as $meeple) {
        $this->insertAsChild([
          'action' => CHOOSE_ACTION_CARD,
          'pId' => $player->getId(),
          'args' => ['cardId' => $card->getId(), 'strength' => $strength, 'multiplier' => true, 'meepleId' => $meeple['id']],
        ]);
      }
    }

    // Insert cleanup actionName
    $this->insertAsChild([
      'action' => \CLEANUP,
      'pId' => $player->getId(),
      'args' => ['card' => $cardId, 'hypnosis' => $isHypnosis],
    ]);
    $this->resolveAction(['card' => $cardId, 'strength' => $strength]);
  }
}
