<?php
namespace AGR\Cards\B;

use AGR\Helpers\UsedText;
use AGR\Core\Notifications;
use AGR\Helpers\CardRulings;
use AGR\Managers\Players;
use AGR\Managers\PlayerCards;

class B146_Illusionist extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B146_Illusionist';
    $this->name = clienttranslate('Illusionist');
    $this->deck = 'B';
    $this->number = 146;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you use a building resource accumulation space, you can discard exactly 1 card from your hand to get 1 additional building resource of the accumulating type.'
      ),
    ];
    $this->players = '3+';
    $this->isArtifexOrBubulcus = true;
    $this->bannedLiving = true;
    $this->rulings = CardRulings::fromKeys([
      'ILLUSIONIST_LANTERN_HOUSE',
    ]);
    $this->usedText = UsedText::get('CARDS_DISCARDED');
  }

  public function isListeningTo($event)
  {
    return $this->isBeforeCollectEvent($event, WOOD)||$this->isBeforeCollectEvent($event, CLAY)||$this->isBeforeCollectEvent($event, REED)||$this->isBeforeCollectEvent($event, STONE);
  }

  public function onPlayerPlaceFarmer($player, $event)
  {
    
    $hand = $player->getHand()->toArray();

    if (count($hand) == 0 || $player->hasPlayedCard('C35_LanternHouse')) {
      return;
    }
    $resource = $this->isBeforeCollectEvent($event, WOOD) ? WOOD : 
                ($this->isBeforeCollectEvent($event, CLAY) ? CLAY :
                ($this->isBeforeCollectEvent($event, REED) ? REED : STONE));
    return [
      'type' => NODE_SEQ,
       'optional' => true,
       'countAsUse' => true,
      'childs' => [
       [
      'action' => SPECIAL_EFFECT,
      'args' => [
        'cardId' => $this->id,
        'method' => 'selectCard',
        'args' => [],
      ],
      ],
      $this->gainNode([$resource=>1])]];

  }

  public function getCards()
  {
    $player = Players::getActive();
    $cards = $player->getHand()->toArray();
    $cardIds = [];

    foreach ($cards as $card) {
      $cardIds[] = $card->getId();
    }

    return $cardIds;
  }

  public function getSelectCardDescription()
  {
    return clienttranslate('Select 1 card in your hand');
  }

  public function argsSelectCard()
  {
    return [
      'cardId' => $this->id,
      'description' => clienttranslate('${actplayer} must choose 1 card in their hand (Illusionist)'),
      'descriptionmyturn' => clienttranslate('Choose 1 card (Illusionist)'),
      'cards' => $this->getCards(),
    ];
  }

  public function actSelectCard($cardId)
  {
    $player = Players::getActive();
    Notifications::destroyCard(PlayerCards::get($cardId), $player);
    PlayerCards::moveToBox($cardId);
  }
}
