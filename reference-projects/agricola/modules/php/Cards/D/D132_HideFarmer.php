<?php
namespace AGR\Cards\D;
use AGR\Core\Engine;

class D132_HideFarmer extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D132_HideFarmer';
    $this->name = clienttranslate('Hide Farmer');
    $this->deck = 'D';
    $this->number = 132;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'During scoring, you can pay 1 <FOOD> each for any number of unused farmyard spaces. You do not lose points for these spaces.'
      ),
    ];
    $this->players = '3+';
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && $event['type'] == 'BeforeEndOfGame';
  }

  public function onPlayerBeforeEndOfGame($player, $event)
  {
    return [
      'action' => SPECIAL_EFFECT,
      'optional' => true,
      'args' => [
        'cardId' => $this->id,
        'method' => 'markSpaces',
      ],
    ];
  }

  public function getMarkSpacesDescription()
  {
    return clienttranslate('Pay food for unused farmyard spaces');
  }

  public function getMaxUsage()
  {
    $player = $this->getPlayer();
    $nFood = $player->countReserveResource(FOOD);
    $nEmpty = count($player->board()->getFreeZones());
    return min($nFood, $nEmpty);
  }

  public function argsMarkSpaces()
  {
    return [
      'cardId' => $this->id,
      'max' => $this->getMaxUsage(),
      'description' => clienttranslate(
        '${actplayer} may pay food to avoid negative scoring for unused farmyard spaces (Hide Farmer)'
      ),
      'descriptionmyturn' => clienttranslate(
        'Select the number of unused farmyard spaces you want to hide (Hide Farmer)'
      ),
    ];
  }

  public function actMarkSpaces($n)
  {
    $max = $this->getMaxUsage();
    if ($n > $max) {
      throw new \BgaVisibleSystemException('You don\'t have enough food to hide that much unused farmyard spaces, or not that much unused farmyard spaces');
    }

    $this->setExtraDatas('hiddenSpaces', $n);
    Engine::insertAsChild($this->payNode([FOOD => $n]));
  }
}
