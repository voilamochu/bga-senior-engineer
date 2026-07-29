<?php
namespace AGR\Cards\D;
use AGR\Managers\Meeples;
use AGR\Core\Notifications;
use AGR\Helpers\Utils;

class D158_BeanCounter extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D158_BeanCounter';
    $this->name = clienttranslate('Bean Counter');
    $this->deck = 'D';
    $this->number = 158;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you use an action space on round spaces 1 to 8, place 1 <FOOD> on this card. Each time this card has 3 <FOOD> on it, move the <FOOD> to your supply.'
      ),
    ];
    $this->players = '4+';
    $this->holder = true;
  }

  public function isListeningTo($event)
  {
    return $this->isActionCardTurnEvent($event, range(1, 8));
  }

  public function onPlayerPlaceFarmer($player, $args)
  {
    return [
      'action' => SPECIAL_EFFECT,
      'args' => [
        'cardId' => $this->id,
        'method' => 'gainFood',
        'args' => [],
      ],
    ];
  }

  public function gainFood()
  {
    $player = $this->getPlayer();
    $created = Meeples::createResourceInLocation(FOOD, $this->id, $this->pId, null, null, 1);
    Notifications::accumulate(Meeples::getMany($created), true);

    // 3 food ?
    $meeples = Meeples::getResourcesOnCard($this->id);
    if($meeples->count() == 3){
      $meeples = Meeples::collectResourcesOnCard($this->getPlayer(), $this->id);
      Notifications::collectResources($player, $meeples);
    }
  }
}
