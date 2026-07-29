<?php
namespace AGR\Cards\D;
use AGR\Core\Stats;
use AGR\Core\Globals;
use AGR\Core\Notifications;
use AGR\Managers\Meeples;
use AGR\Managers\ActionCards;

class D98_Transactor extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D98_Transactor';
    $this->name = clienttranslate('Transactor');
    $this->deck = 'D';
    $this->number = 98;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Immediately before the final harvest at the end of round 14, you can take all the building resources that are left on the entire game board.'
      ),
    ];
    $this->players = '1+';
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && $event['type'] == 'BeforeHarvest' && Globals::getTurn() == 14;
  }

  public function onPlayerBeforeHarvest($player, $event)
  {
    return [
      'optional' => true,
      'action' => SPECIAL_EFFECT,
      'args' => [
        'cardId' => $this->id,
        'method' => 'collectAll',
        'args' => [],
      ],
    ];
  }

  public function collectAll()
  {
    $player = $this->getPlayer();
    $cards = ActionCards::getVisible();
    $collected = [];
    foreach ($cards as $card) {
      $meeples = Meeples::getResourcesOnCard($card->getId())->filter(function ($meeple) {
        return in_array($meeple['type'], [WOOD, CLAY, STONE, REED]);
      });

      foreach ($meeples as $meeple) {
        Meeples::receiveResource($player, $meeple);
        $collected[] = $meeple;

        $statName = 'incBoard' . ucfirst($meeple['type']);
        Stats::$statName($player);
      }
    }

    Notifications::collectResources($player, $collected);
  }

  public function getCollectAllDescription()
  {
    return clienttranslate('Collect all building resources');
  }
}
