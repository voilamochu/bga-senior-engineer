<?php

namespace ARK\Actions\Bonuses;

use ARK\Managers\Meeples;
use ARK\Managers\Players;
use ARK\Core\Notifications;
use ARK\Managers\ZooCards;
use ARK\Core\Engine;
use ARK\Helpers\FlowConvertor;
use ARK\Core\Stats;
use ARK\Helpers\Utils;
use ARK\Models\Player;

class WazaSpecial extends \ARK\Models\Action
{
  public function getState(): int
  {
    return \ST_WAZA_SPECIAL;
  }

  public function getDescription(): string|array
  {
    return clienttranslate('Chose focus');
  }

  public function actWazaSpecial($type)
  {
    self::checkAction('actWazaSpecial');
    $player = Players::getActive();
    $this->checkCanTakeIrreversible();

    // Mark the card
    $card = ZooCards::getSingle('S227_WazaSpecialAssignment');
    $card->setExtraDatas('choice', $type);

    // Find 1 animal card in deck of corresponding size
    $allAnimals = ZooCards::getInLocation('deck')
      ->filter(function ($card) use ($type) {
        return $card->getType() == \CARD_ANIMAL &&
          (($type == 'small' && $card->isSmall()) || ($type == 'large' && $card->isLarge()));
      })
      ->getIds();
    $animalId = Utils::rand($allAnimals, 1)[0];
    $card = ZooCards::addToHand($animalId, $player->getId());

    // Notify
    Notifications::wazaSpecial($player, $type, $card);

    $this->resolveAction(['type' => $type], true);
  }
}
